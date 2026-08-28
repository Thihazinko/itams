<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use App\Models\TaskDailyEntry;
use App\Models\TaskItem;
use App\Models\User;
use App\Exports\TaskManagementMonthlyExport;
use App\Exports\TaskSummaryExport;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TaskManagementController extends Controller
{
    private const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    /**
     * Daily task sheet: for one member and one date, the eight fixed working-hour
     * slots, each recording the category / task worked plus optional detail.
     */
    public function index(Request $request)
    {
        $viewer = $request->user();
        $date   = $this->resolveDate($request);

        [$target, $canEdit] = $this->resolveTarget($request, $viewer);

        $categories = TaskCategory::with('items:id,task_category_id,name,sort_order')
            ->orderBy('sort_order')->orderBy('id')->get();

        // Existing rows for this member/day (in row order). A brand-new day starts
        // from the standard hourly rows as a convenience — all rows are then freely
        // editable, removable, and added to by hand.
        $existing = TaskDailyEntry::where('user_id', $target->id)
            ->whereDate('work_date', $date)
            ->orderBy('slot')->get();

        $rows = $existing->isEmpty()
            ? $this->defaultRows()
            : $existing->map(fn ($e) => $this->rowFrom($e))->values()->all();

        // Month shown by the calendar picker: an explicit ?cal=YYYY-MM (from the
        // prev/next arrows) or, by default, the month of the selected date.
        try {
            $calMonth = $request->filled('cal')
                ? Carbon::createFromFormat('Y-m', $request->get('cal'))->startOfMonth()
                : $date->copy()->startOfMonth();
        } catch (\Throwable) {
            $calMonth = $date->copy()->startOfMonth();
        }

        // Days in that month that already have entries — marked on the calendar.
        $loggedDates = TaskDailyEntry::query()
            ->where('user_id', $target->id)
            ->whereYear('work_date', $calMonth->year)
            ->whereMonth('work_date', $calMonth->month)
            ->selectRaw('DISTINCT DATE(work_date) as d')
            ->pluck('d')->all();

        return view('task_management.index', [
            'date'            => $date,
            'calMonth'        => $calMonth,
            'loggedDates'     => $loggedDates,
            'rows'            => $rows,
            'categories'      => $categories,
            'tasksByCategory' => $this->tasksByCategory($categories),
            'workTypes'       => TaskDailyEntry::WORK_TYPES,
            'studyTypes'      => TaskDailyEntry::STUDY_TYPES,
            'target'          => $target,
            'isAdmin'         => $viewer->isAdmin(),
            'members'         => $viewer->isAdmin() ? User::taskMembers()->get() : collect(),
            'canEdit'         => $canEdit,
        ]);
    }

    /** The standard hourly rows used to pre-fill a fresh day. */
    private function defaultRows(): array
    {
        return array_map(
            fn ($t) => $this->blankRow($t[0], $t[1]),
            array_values(TaskDailyEntry::SLOT_TIMES)
        );
    }

    private function blankRow(string $start = '', string $end = ''): array
    {
        return [
            'start_time' => $start, 'end_time' => $end,
            'task_category_id' => null, 'task_item_id' => null,
            'project_name' => '', 'expense_name' => '',
            'work_type' => '', 'study_type' => '', 'task_detail' => '',
        ];
    }

    private function rowFrom(TaskDailyEntry $e): array
    {
        return [
            'start_time'       => $e->start_time ? substr((string) $e->start_time, 0, 5) : '',
            'end_time'         => $e->end_time ? substr((string) $e->end_time, 0, 5) : '',
            'task_category_id' => $e->task_category_id,
            'task_item_id'     => $e->task_item_id,
            'project_name'     => $e->project_name ?? '',
            'expense_name'     => $e->expense_name ?? '',
            'work_type'        => $e->work_type ?? '',
            'study_type'       => $e->study_type ?? '',
            'task_detail'      => $e->task_detail ?? '',
        ];
    }

    /** Persist one member's day: eight slots, upserting filled ones, clearing empty. */
    public function save(Request $request)
    {
        $viewer = $request->user();
        [$target, $canEdit] = $this->resolveTarget($request, $viewer);

        abort_unless($canEdit, 403, 'You cannot edit this daily sheet.');

        $data = $request->validate([
            'date'                     => 'required|date',
            'slots'                    => 'array|max:50',
            'slots.*.start_time'       => 'nullable|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'slots.*.end_time'         => 'nullable|regex:/^\d{1,2}:\d{2}(:\d{2})?$/',
            'slots.*.task_category_id' => 'nullable|exists:task_categories,id',
            'slots.*.task_item_id'     => 'nullable|exists:task_items,id',
            'slots.*.project_name'     => 'nullable|string|max:255',
            'slots.*.expense_name'     => 'nullable|string|max:255',
            'slots.*.work_type'        => 'nullable|in:Regular,Temporary',
            'slots.*.study_type'       => 'nullable|in:Work,Study',
            'slots.*.task_detail'      => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $name = $viewer->name;

        // task_item_id => task_category_id, so a chosen task always sets its category.
        $taskCategory = TaskItem::pluck('task_category_id', 'id');

        DB::transaction(function () use ($data, $target, $date, $name, $taskCategory) {
            // Rows are user-managed (added/removed by hand), so rebuild the whole
            // day from what was submitted, numbering rows by their order.
            TaskDailyEntry::where('user_id', $target->id)->whereDate('work_date', $date)->delete();

            $order = 0;
            foreach (($data['slots'] ?? []) as $row) {
                $taskId = $row['task_item_id'] ?? null;
                $catId  = $taskId ? ($taskCategory[$taskId] ?? null) : ($row['task_category_id'] ?? null);

                // A row counts as filled only when it has real content; the times
                // alone (which pre-fill to a default window) don't make a row.
                $content = [
                    'task_category_id' => $catId,
                    'task_item_id'     => $taskId,
                    'project_name'     => $row['project_name'] ?? null,
                    'expense_name'     => $row['expense_name'] ?? null,
                    'work_type'        => $row['work_type'] ?? null,
                    'study_type'       => $row['study_type'] ?? null,
                    'task_detail'      => $row['task_detail'] ?? null,
                ];
                if (collect($content)->every(fn ($v) => $v === null || $v === '')) {
                    continue;
                }

                TaskDailyEntry::create($content + [
                    'user_id'     => $target->id,
                    'work_date'   => $date,
                    'slot'        => ++$order,
                    'start_time'  => $row['start_time'] ?? null,
                    'end_time'    => $row['end_time'] ?? null,
                    'created_by'  => $name,
                    'modified_by' => $name,
                ]);
            }
        });

        ActivityLogger::log(
            action: 'updated',
            description: "Saved daily task sheet for {$target->name} on " . Carbon::parse($date)->format('j M Y'),
        );

        return redirect()
            ->route('task-management.index', array_filter(['date' => $date, 'member' => $target->id === $viewer->id ? null : $target->id]))
            ->with('success', 'Daily task sheet saved for ' . Carbon::parse($date)->format('j M Y') . '.');
    }

    /**
     * Monthly list: every task row one member logged across the selected month,
     * flattened into a single date-ordered table for scanning / review.
     */
    public function monthly(Request $request)
    {
        $viewer = $request->user();
        [$target, $canEdit] = $this->resolveTarget($request, $viewer);

        $year  = (int) ($request->get('year') ?: now()->year);
        $month = (int) ($request->get('month') ?: now()->month);
        $month = max(1, min(12, $month));

        // Optional custom day range (e.g. 11 Jan – 10 Feb). When both ends are
        // valid it spans months and overrides the month/year picker; otherwise
        // the list falls back to the selected month.
        [$from, $to] = $this->resolveRange($request);
        $isRange = $from && $to;

        $entries = TaskDailyEntry::query()
            ->with(['category:id,name,sort_order', 'task:id,name'])
            ->where('user_id', $target->id)
            ->when($isRange,
                fn ($q) => $q->whereBetween('work_date', [$from->toDateString(), $to->toDateString()]),
                fn ($q) => $q->whereYear('work_date', $year)->whereMonth('work_date', $month),
            )
            ->orderBy('work_date')->orderBy('slot')
            ->get();

        // Bucket the month's entries under their category, keeping each category's
        // rows in date order and tallying per-category and overall man-hours.
        $total  = 0.0;
        $groups = [];
        foreach ($entries as $e) {
            $hours  = $e->hours();
            $total += $hours;

            $catId = $e->task_category_id ?? 0;
            if (! isset($groups[$catId])) {
                $groups[$catId] = [
                    'name'  => $e->category?->name ?? '(No category)',
                    'sort'  => $e->category?->sort_order ?? PHP_INT_MAX,
                    'total' => 0.0,
                    'rows'  => [],
                ];
            }
            $groups[$catId]['total'] += $hours;
            $groups[$catId]['rows'][] = [
                'date'        => $e->work_date,
                'start_time'  => $e->start_time ? substr((string) $e->start_time, 0, 5) : '',
                'end_time'    => $e->end_time ? substr((string) $e->end_time, 0, 5) : '',
                'hours'       => $hours,
                'task'        => $e->task?->name ?? '',
                'project'     => $e->project_name ?? '',
                'expense'     => $e->expense_name ?? '',
                'work_type'   => $e->work_type ?? '',
                'study_type'  => $e->study_type ?? '',
                'detail'      => $e->task_detail ?? '',
            ];
        }

        // Order categories by their configured sort (the "(No category)" bucket,
        // if any, falls to the end via PHP_INT_MAX).
        $groups = collect($groups)->sortBy([['sort', 'asc'], ['name', 'asc']])->values();

        return view('task_management.monthly', [
            'year'       => $year,
            'month'      => $month,
            'years'      => $this->yearOptions($year),
            'months'     => self::MONTHS,
            'from'       => $from,
            'to'         => $to,
            'isRange'    => $isRange,
            'groups'     => $groups,
            'total'      => $total,
            'entryCount' => $entries->count(),
            'daysLogged' => $entries->pluck('work_date')->map(fn ($d) => $d->toDateString())->unique()->count(),
            'target'     => $target,
            'isAdmin'    => $viewer->isAdmin(),
            'members'    => $viewer->isAdmin() ? User::taskMembers()->get() : collect(),
        ]);
    }

    /**
     * Export one month of Daily Task entries to Excel. Scope "all" (admins only)
     * writes one worksheet per member; otherwise a single member's sheet — the
     * viewer themselves, or, for an admin, the ?member= they're viewing.
     */
    public function export(Request $request)
    {
        $viewer = $request->user();

        $year  = (int) ($request->get('year') ?: now()->year);
        $month = (int) ($request->get('month') ?: now()->month);
        $month = max(1, min(12, $month));

        // Mirror the Monthly List: a custom day range overrides the month/year.
        [$from, $to] = $this->resolveRange($request);
        $isRange = $from && $to;
        $period  = $isRange
            ? $from->format('Ymd') . '-' . $to->format('Ymd')
            : sprintf('%04d-%02d', $year, $month);

        if ($request->get('scope') === 'all') {
            abort_unless($viewer->isAdmin(), 403, 'Only admins can export all members.');
            $members  = $this->exportMembers($year, $month, $from, $to);
            $fileName = 'Daily-Task-All-Members-' . $period . '.xlsx';
        } else {
            [$target] = $this->resolveTarget($request, $viewer);
            $members  = collect([$target]);
            $fileName = 'Daily-Task-' . Str::slug($target->name) . '-' . $period . '.xlsx';
        }

        $periodLabel = $isRange
            ? $from->format('j M Y') . ' – ' . $to->format('j M Y')
            : Carbon::createFromDate($year, $month, 1)->format('F Y');

        ActivityLogger::log(
            action: 'exported',
            description: 'Exported Daily Task ' . ($request->get('scope') === 'all' ? 'for all members' : "for {$members->first()?->name}")
                . ' — ' . $periodLabel,
        );

        return Excel::download(new TaskManagementMonthlyExport($members, $year, $month, $from, $to), $fileName);
    }

    /** Members for an all-members export: those granted the module plus anyone
     *  who actually logged hours in the period (e.g. an admin), by name. */
    private function exportMembers(int $year, int $month, ?Carbon $from = null, ?Carbon $to = null)
    {
        $members = User::taskMembers()->get();

        $loggedIds = TaskDailyEntry::query()
            ->when($from && $to,
                fn ($q) => $q->whereBetween('work_date', [$from->toDateString(), $to->toDateString()]),
                fn ($q) => $q->whereYear('work_date', $year)->whereMonth('work_date', $month),
            )
            ->distinct()->pluck('user_id')->map(fn ($id) => (int) $id);

        $missing = $loggedIds->diff($members->pluck('id'));
        if ($missing->isNotEmpty()) {
            $members = $members->concat(User::whereIn('id', $missing)->get());
        }

        return $members->sortBy('name')->values();
    }

    /**
     * Monthly summary: total man-hours (one per filled slot) rolled up per member,
     * broken down by category and task, for the selected month.
     */
    public function summary(Request $request)
    {
        $year  = (int) ($request->get('year') ?: now()->year);
        $month = (int) ($request->get('month') ?: now()->month);
        $month = max(1, min(12, $month));

        $categories = TaskCategory::with('items:id,task_category_id,name,sort_order')
            ->orderBy('sort_order')->orderBy('id')->get();

        // Each entry contributes man-hours equal to its start→end span (default 1h).
        $rows = TaskDailyEntry::query()
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->whereNotNull('task_category_id')
            ->orderBy('work_date')->orderBy('slot')
            ->get(['user_id', 'task_category_id', 'task_item_id', 'start_time', 'end_time']);

        $catMember  = [];  // [categoryId][userId] => hours
        $taskMember = [];  // [taskItemId][userId] => hours
        $catTotal   = [];  // [categoryId] => hours
        $memberTotal = []; // [userId] => hours
        $grand = 0.0;

        foreach ($rows as $r) {
            $hours = TaskDailyEntry::hoursBetween($r->start_time, $r->end_time);
            $catMember[$r->task_category_id][$r->user_id] = ($catMember[$r->task_category_id][$r->user_id] ?? 0) + $hours;
            if ($r->task_item_id) {
                $taskMember[$r->task_item_id][$r->user_id] = ($taskMember[$r->task_item_id][$r->user_id] ?? 0) + $hours;
            }
            $catTotal[$r->task_category_id] = ($catTotal[$r->task_category_id] ?? 0) + $hours;
            $memberTotal[$r->user_id] = ($memberTotal[$r->user_id] ?? 0) + $hours;
            $grand += $hours;
        }

        // Columns cover every member included in Daily Task: those granted the
        // module plus anyone who actually logged hours this month (e.g. an admin).
        $members = User::taskMembers()->get();
        $missingIds = collect(array_keys($memberTotal))->map(fn ($id) => (int) $id)
            ->diff($members->pluck('id'));
        if ($missingIds->isNotEmpty()) {
            $members = $members->concat(User::whereIn('id', $missingIds)->get())
                ->sortBy('name')->values();
        }

        return view('task_management.summary', [
            'year'        => $year,
            'month'       => $month,
            'years'       => $this->yearOptions($year),
            'months'      => self::MONTHS,
            'categories'  => $categories,
            'members'     => $members,
            'catMember'   => $catMember,
            'taskMember'  => $taskMember,
            'catTotal'    => $catTotal,
            'memberTotal' => $memberTotal,
            'grand'       => $grand,
        ]);
    }

    /**
     * Export the Monthly Summary to Excel: two worksheets mirroring the on-screen
     * "Plan vs achieved by category" and "Category & task breakdown" tables.
     */
    public function exportSummary(Request $request)
    {
        $year  = (int) ($request->get('year') ?: now()->year);
        $month = (int) ($request->get('month') ?: now()->month);
        $month = max(1, min(12, $month));

        ActivityLogger::log(
            action: 'exported',
            description: 'Exported Task Management monthly summary — ' . Carbon::createFromDate($year, $month, 1)->format('F Y'),
        );

        return Excel::download(
            new TaskSummaryExport($year, $month),
            'Task-Summary-' . sprintf('%04d-%02d', $year, $month) . '.xlsx'
        );
    }

    /** Reference-data management: the list of categories and their tasks. */
    public function tasks(Request $request)
    {
        $categories = TaskCategory::with('items')->orderBy('sort_order')->orderBy('id')->get();

        return view('task_management.tasks', [
            'categories' => $categories,
            'canEdit'    => $request->user()->canEdit('task_management'),
        ]);
    }

    /** Which member's sheet, and whether the viewer may edit it. */
    private function resolveTarget(Request $request, User $viewer): array
    {
        $target = $viewer;

        // Admins may open any member's sheet via ?member=; others always see their own.
        if ($viewer->isAdmin() && $request->filled('member')) {
            $member = User::taskMembers()->whereKey($request->get('member'))->first();
            if ($member) {
                $target = $member;
            }
        }

        $isSelf  = $target->id === $viewer->id;
        $canEdit = $viewer->isAdmin() || ($isSelf && $viewer->canEdit('task_daily'));

        return [$target, $canEdit];
    }

    /**
     * Optional [from, to] day range from ?from=&to=. Returns [null, null] unless
     * both parse as dates; reversed ends are swapped so the range is always valid.
     */
    private function resolveRange(Request $request): array
    {
        if (! $request->filled('from') || ! $request->filled('to')) {
            return [null, null];
        }

        try {
            $from = Carbon::parse($request->get('from'))->startOfDay();
            $to   = Carbon::parse($request->get('to'))->startOfDay();
        } catch (\Throwable) {
            return [null, null];
        }

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }

    /** Selected date, defaulting to today; bad input falls back to today. */
    private function resolveDate(Request $request): Carbon
    {
        try {
            return $request->filled('date')
                ? Carbon::parse($request->get('date'))->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    /** [categoryId => [['id'=>, 'name'=>], ...]] for the dependent task dropdowns. */
    private function tasksByCategory($categories): array
    {
        $map = [];
        foreach ($categories as $cat) {
            $map[$cat->id] = $cat->items->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->values()->all();
        }

        return $map;
    }

    /** Years that carry daily entries, plus the selected/current year, newest first. */
    private function yearOptions(int $selected): array
    {
        $years = TaskDailyEntry::query()
            ->selectRaw('DISTINCT YEAR(work_date) as y')->pluck('y')
            ->map(fn ($y) => (int) $y)->all();

        foreach ([$selected, (int) now()->year] as $y) {
            if (! in_array($y, $years, true)) {
                $years[] = $y;
            }
        }
        rsort($years);

        return $years;
    }
}
