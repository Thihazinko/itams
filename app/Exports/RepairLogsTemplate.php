<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RepairLogsTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        // "no" is generated on export; importing ignores it.
        return ['date', 'employee', 'department', 'pc_id', 'repair_process', 'status', 'remark'];
    }

    public function array(): array
    {
        return [
            [
                '2026-06-25', 'Sample Employee', 'IT Development', 'PC-001',
                'Replaced faulty RAM module and reseated SSD.', 'In Progress',
                'Delete this row before importing',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
