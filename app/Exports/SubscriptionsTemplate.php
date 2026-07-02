<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriptionsTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'service_type', 'project_name', 'subscription_name', 'vendor_name', 'status',
            'period', 'previous_cost', 'expire_date', 'previous_renewal_date', 'start_using_date', 'renewal_cost', 'currency',
            'renewal_type', 'renewal_status', 'remarks',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Domain', 'Sample Project', 'example.com', 'GoDaddy', 'Active',
                '1 Year', '15.00', '2027-01-01', '2026-01-01', '2025-01-01', '18.00', 'USD',
                'Yearly', 'Pending', 'Delete this row before importing',
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
