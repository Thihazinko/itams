<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * "Plan vs achieved by category" as a worksheet — the same table shown on the
 * Monthly Summary page: planned target (from Manage Tasks) against logged hours,
 * their difference, and progress toward plan, with a Total row.
 */
class TaskSummaryPlanSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private int $totalRow = 0;

    public function __construct(
        protected Collection $categories,
        protected array $catTotal,
    ) {
    }

    public function title(): string
    {
        return 'Plan vs Achieved';
    }

    public function headings(): array
    {
        return ['Category', 'Plan', 'Achieved', 'Difference', 'Progress'];
    }

    public function array(): array
    {
        $rows    = [];
        $totPlan = 0.0;
        $totAch  = 0.0;

        foreach ($this->categories as $category) {
            $plan = (float) $category->plan_hours;
            $ach  = (float) ($this->catTotal[$category->id] ?? 0);
            if ($plan <= 0 && $ach <= 0) {
                continue;
            }

            $totPlan += $plan;
            $totAch  += $ach;
            $diff = $ach - $plan;

            $rows[] = [
                $category->name,
                $plan > 0 ? round($plan, 2) : '—',
                round($ach, 2),
                $this->diffLabel($plan, $diff),
                $plan > 0 ? round($ach / $plan * 100) . '%' : '—',
            ];
        }

        $this->totalRow = count($rows) + 2; // +1 heading row, +1 for this total row
        $td = $totAch - $totPlan;
        $rows[] = [
            'Total',
            round($totPlan, 2),
            round($totAch, 2),
            $this->diffLabel($totPlan, $td),
            $totPlan > 0 ? round($totAch / $totPlan * 100) . '% of plan' : '—',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1              => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
            $this->totalRow => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }

    /** Signed difference like the page: "+2", "-1.5", or "—" when there's no plan. */
    private function diffLabel(float $plan, float $diff): string
    {
        if ($plan <= 0) {
            return '—';
        }

        return ($diff >= 0 ? '+' : '') . $this->fmt($diff);
    }

    private function fmt(float $v): string
    {
        $s = number_format($v, 2, '.', '');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}
