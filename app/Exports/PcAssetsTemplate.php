<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PcAssetsTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'computer_id', 'hostname', 'employee_name', 'status', 'department',
            'location', 'brand', 'model', 'serial_number', 'cpu', 'ram', 'ssd',
            'hdd', 'display', 'operating_system', 'license_key', 'admin_password', 'username',
            'password', 'purchased_date', 'expire_date', 'warranty_period', 'remarks',
        ];
    }

    public function array(): array
    {
        return [
            [
                'PC-SAMPLE-01', 'IT-WS99', 'Sample Employee', 'Active', 'IT Development',
                'Office', 'Dell', 'Latitude 5430', 'SN0000', 'Intel Core i7-1265U', '16GB DDR4', '512GB NVMe',
                '1TB HDD', '14" FHD', 'Windows 11 Pro', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', 'sample-admin-pw', 'jdoe',
                'sample-user-pw', '2024-01-15', '2027-01-15', '3 years', 'Delete this row before importing',
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
