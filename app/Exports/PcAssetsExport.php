<?php

namespace App\Exports;

use App\Models\PcAsset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PcAssetsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return PcAsset::orderBy('computer_id')->get();
    }

    public function headings(): array
    {
        return [
            'computer_id', 'hostname', 'employee_name', 'status', 'department',
            'location', 'brand', 'model', 'serial_number', 'cpu', 'ram', 'ssd',
            'hdd', 'display', 'operating_system', 'admin_password', 'username',
            'password', 'purchased_date', 'warranty_period', 'remarks',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->computer_id,
            $asset->hostname,
            $asset->employee_name,
            $asset->status,
            $asset->department,
            $asset->location,
            $asset->brand,
            $asset->model,
            $asset->serial_number,
            $asset->cpu,
            $asset->ram,
            $asset->ssd,
            $asset->hdd,
            $asset->display,
            $asset->operating_system,
            $asset->admin_password,
            $asset->username,
            $asset->password,
            optional($asset->purchased_date)->format('Y-m-d'),
            $asset->warranty_period,
            $asset->remarks,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
