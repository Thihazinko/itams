<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeviceRepairLogsTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        // "no" is generated on export; importing ignores it. Match "device" to a
        // device by its serial number or item name in Device Master.
        return ['date', 'employee', 'department', 'device', 'repair_process', 'status', 'remark'];
    }

    public function array(): array
    {
        return [
            [
                '2026-06-25', 'Sample Employee', 'IT Development', 'SN12345',
                'Replaced power adapter and tested.', 'In Progress',
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
