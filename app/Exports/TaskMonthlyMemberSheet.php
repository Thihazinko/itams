<?php

namespace App\Exports;

use App\Models\TaskDailyEntry;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One member's Daily Task entries for a single month, grouped by category — the
 * same layout as the "Entries by category" table on the Monthly List page: a
 * header row per category (count + subtotal hours), its entries, then a Total
 * man-hours line.
 */
class TaskMonthlyMemberSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /** 1-based sheet rows that are category headers (styled as group bars). */
    private array $categoryRows = [];

    /** 1-based sheet row carrying the grand total. */
    private int $totalRow = 0;

    public function __construct(
        protected User $member,
        protected int $year,
        protected int $month,
        protected string $sheetTitle,
        protected ?Carbon $from = null,
        protected ?Carbon $to = null,
    ) {
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function headings(): array
    {
        return [
            'No', 'Date', 'Hours', 'Task', 'Project', 'Expense',
            'Regular / Temp.', 'Work / Study', 'Task Detail',
        ];
    }

    public function array(): array
    {
        $entries = TaskDailyEntry::query()
            ->with(['category:id,name,sort_order', 'task:id,name'])
            ->where('user_id', $this->member->id)
            ->when($this->from && $this->to,
                fn ($q) => $q->whereBetween('work_date', [$this->from->toDateString(), $this->to->toDateString()]),
                fn ($q) => $q->whereYear('work_date', $this->year)->whereMonth('work_date', $this->month),
            )
            ->orderBy('work_date')->orderBy('slot')
            ->get();

        // Bucket entries under their category, in date order, mirroring the page.
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
                'date'       => optional($e->work_date)->format('j M (D)') ?: '',
                'hours'      => $this->hoursLabel($e, $hours),
                'task'       => $e->task?->name ?? '',
                'project'    => $e->project_name ?? '',
                'expense'    => $e->expense_name ?? '',
                'work_type'  => $e->work_type ?? '',
                'study_type' => $e->study_type ?? '',
                'detail'     => $e->task_detail ?? '',
            ];
        }

        $groups = collect($groups)->sortBy([['sort', 'asc'], ['name', 'asc']])->values();

        $rows    = [];
        $sheetNo = 1; // row 1 is the heading row; data starts at row 2
        foreach ($groups as $g) {
            $sheetNo++;
            $this->categoryRows[] = $sheetNo;
            $count = count($g['rows']);
            $rows[] = [
                $g['name'], '',
                $count . ' ' . ($count === 1 ? 'entry' : 'entries') . ' · ' . $this->fmt($g['total']) . 'h',
                '', '', '', '', '', '',
            ];

            foreach ($g['rows'] as $i => $r) {
                $sheetNo++;
                $rows[] = [
                    $i + 1,
                    $r['date'],
                    $r['hours'],
                    $r['task'],
                    $r['project'],
                    $r['expense'],
                    $r['work_type'],
                    $r['study_type'],
                    $r['detail'],
                ];
            }
        }

        // Grand total — label in the first column, hours under the Hours column.
        $sheetNo++;
        $this->totalRow = $sheetNo;
        $rows[] = ['Total man-hours', '', $this->fmt($total) . 'h', '', '', '', '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];

        // Category header bars — bold on a light-blue fill, like the page.
        foreach ($this->categoryRows as $r) {
            $styles[$r] = ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DCE7FB']]];
        }

        if ($this->totalRow > 0) {
            $styles[$this->totalRow] = ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]];
        }

        return $styles;
    }

    /** The Hours cell as shown on the page: time span plus the hour count. */
    private function hoursLabel(TaskDailyEntry $e, float $hours): string
    {
        $start = $e->start_time ? substr((string) $e->start_time, 0, 5) : '';
        $end   = $e->end_time ? substr((string) $e->end_time, 0, 5) : '';

        if ($start !== '' || $end !== '') {
            return ($start ?: '—') . ' to ' . ($end ?: '—') . ' (' . $this->fmt($hours) . 'h)';
        }

        return $this->fmt($hours) . 'h';
    }

    /** Trim trailing zeros: 1.00 → "1", 1.50 → "1.5". */
    private function fmt(float $v): string
    {
        $s = number_format($v, 2, '.', '');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}
