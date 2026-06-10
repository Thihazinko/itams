<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LicensesContractsTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'software_name', 'status', 'renewal_type', 'license_info',
            'last_renewal_date', 'start_using_date', 'expire_date', 'vendor_name',
            'previous_cost', 'renewal_cost', 'currency', 'remarks',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Microsoft 365 Business Standard', 'Active', 'Yearly', 'KEY-XXXX-YYYY-ZZZZ / Invoice INV-2024-001',
                '2025-01-15', '2024-02-01', '2026-01-15', 'Microsoft',
                850000.00, 880000.00, 'MMK', 'Delete this row before importing',
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
