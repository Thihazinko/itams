<?php

namespace App\Exports;

use App\Models\TaskCategory;
use App\Models\TaskDailyEntry;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The Monthly Summary workbook: two worksheets that reproduce the on-screen
 * tables — "Plan vs achieved by category" and "Category & task breakdown" — for
 * a given month. The man-hour aggregation mirrors TaskManagementController@summary.
 */
class TaskSummaryExport implements WithMultipleSheets
{
    public function __construct(
        protected int $year,
        protected int $month,
    ) {
    }

    public function sheets(): array
    {
        $categories = TaskCategory::with('items:id,task_category_id,name,sort_order')
            ->orderBy('sort_order')->orderBy('id')->get();

        // Each entry contributes man-hours equal to its start→end span (default 1h).
        $rows = TaskDailyEntry::query()
            ->whereYear('work_date', $this->year)
            ->whereMonth('work_date', $this->month)
            ->whereNotNull('task_category_id')
            ->orderBy('work_date')->orderBy('slot')
            ->get(['user_id', 'task_category_id', 'task_item_id', 'start_time', 'end_time']);

        $catMember   = [];
        $taskMember  = [];
        $catTotal    = [];
        $memberTotal = [];
        $grand       = 0.0;

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

        // Columns cover every member granted the module plus anyone who logged
        // hours this month (e.g. an admin) — matching the on-screen summary.
        $members = User::taskMembers()->get();
        $missing = collect(array_keys($memberTotal))->map(fn ($id) => (int) $id)
            ->diff($members->pluck('id'));
        if ($missing->isNotEmpty()) {
            $members = $members->concat(User::whereIn('id', $missing)->get())
                ->sortBy('name')->values();
        }

        return [
            new TaskSummaryPlanSheet($categories, $catTotal),
            new TaskSummaryBreakdownSheet($categories, $members, $catMember, $taskMember, $catTotal, $memberTotal, $grand),
        ];
    }
}
