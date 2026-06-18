<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DevicesTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'item_name', 'category', 'serial_number', 'location', 'qty', 'status',
            'description', 'vendor', 'purchased_date', 'warranty',
            'delivery_date', 'delivery_location', 'remark',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Logitech MX Master 3', 'Peripheral', 'SN-SAMPLE-001', 'Server Room A', 1, 'Active',
                'Wireless mouse for the senior dev workstation.', 'PC World',
                '2024-08-12', '1 year',
                '2024-08-15', 'Office HQ - Level 3', 'Delete this row before importing',
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
