<?php

namespace App\Exports;

use App\Models\LicenseContract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LicensesContractsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return LicenseContract::orderBy('expire_date')->get();
    }

    public function headings(): array
    {
        return [
            'software_name', 'status', 'renewal_type', 'license_info',
            'last_renewal_date', 'expire_date', 'vendor_name',
            'previous_cost', 'renewal_cost', 'currency', 'remarks',
        ];
    }

    public function map($item): array
    {
        return [
            $item->software_name,
            $item->status,
            $item->renewal_type,
            $item->license_info,
            optional($item->last_renewal_date)->format('Y-m-d'),
            $item->expire_permanent ? 'Permanent' : optional($item->expire_date)->format('Y-m-d'),
            $item->vendor_name,
            $item->previous_cost,
            $item->renewal_cost,
            $item->currency,
            $item->remarks,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
