<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * "Category & task breakdown" as a worksheet — the same table shown on the
 * Monthly Summary page: one row per task with a man-hours column per member and
 * a row total, grouped under each category (the category cell is merged across
 * its task rows, mirroring the on-screen rowspan), with a Total man-hours row.
 */
class TaskSummaryBreakdownSheet implements FromArray, WithHeadings, WithStyles, WithTitle, WithEvents
{
    /** [ [startDataIdx, endDataIdx], ... ] category groups to merge (0-based). */
    private array $categoryMerges = [];

    private int $totalRow = 0;

    public function __construct(
        protected Collection $categories,
        protected Collection $members,
        protected array $catMember,
        protected array $taskMember,
        protected array $catTotal,
        protected array $memberTotal,
        protected float $grand,
    ) {
    }

    public function title(): string
    {
        return 'Category & Task Breakdown';
    }

    public function headings(): array
    {
        $headings = ['Category', 'Task'];
        foreach ($this->members as $member) {
            $headings[] = $member->name;
        }
        $headings[] = 'Total';

        return $headings;
    }

    public function array(): array
    {
        $rows = [];
        $idx  = 0; // 0-based index into $rows (sheet row = $idx + 2)

        foreach ($this->categories as $category) {
            $cTotal = $this->catTotal[$category->id] ?? 0;
            if ($cTotal <= 0) {
                continue;
            }

            // Task rows with hours, then a "(No specific task)" remainder row for
            // category hours not tied to a task — identical to the page's logic.
            $taskRows = [];
            foreach ($category->items as $task) {
                $tRow = $this->taskMember[$task->id] ?? [];
                if (array_sum($tRow) > 0) {
                    $taskRows[] = ['name' => $task->name, 'per' => $tRow, 'total' => array_sum($tRow)];
                }
            }
            $remPer = [];
            foreach ($this->members as $m) {
                $catH  = $this->catMember[$category->id][$m->id] ?? 0;
                $taskH = 0;
                foreach ($taskRows as $tr) {
                    $taskH += $tr['per'][$m->id] ?? 0;
                }
                $rem = $catH - $taskH;
                if ($rem > 0.001) {
                    $remPer[$m->id] = $rem;
                }
            }
            if (! empty($remPer)) {
                $taskRows[] = ['name' => '(No specific task)', 'per' => $remPer, 'total' => array_sum($remPer)];
            }

            $start = $idx;

            foreach ($taskRows as $i => $tr) {
                $row = [
                    $i === 0 ? $category->name : '',
                    $tr['name'],
                ];
                foreach ($this->members as $m) {
                    $row[] = isset($tr['per'][$m->id]) ? round($tr['per'][$m->id], 2) : '—';
                }
                $row[] = round($tr['total'], 2);

                $rows[] = $row;
                $idx++;
            }

            if ($idx - 1 > $start) {
                $this->categoryMerges[] = [$start, $idx - 1];
            }
        }

        // Total man-hours row (label spans Category + Task, merged in events).
        $totalRow = ['Total man-hours', ''];
        foreach ($this->members as $m) {
            $totalRow[] = round($this->memberTotal[$m->id] ?? 0, 2);
        }
        $totalRow[] = round($this->grand, 2);

        $this->totalRow = $idx + 2;
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1              => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
            $this->totalRow => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge each category's cell down its task rows (mirrors rowspan).
                foreach ($this->categoryMerges as [$s, $e]) {
                    $sheet->mergeCells('A' . ($s + 2) . ':A' . ($e + 2));
                }

                // Category cell aligned to the top of its merged range.
                if ($this->totalRow > 1) {
                    $sheet->getStyle('A2:A' . $this->totalRow)->getAlignment()->setVertical('top');

                    // Total label spans Category + Task, like the page's colspan=2.
                    $sheet->mergeCells('A' . $this->totalRow . ':B' . $this->totalRow);
                }
            },
        ];
    }
}
