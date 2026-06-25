<?php

namespace App\Exports;

use App\Models\PcAsset;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PcAssetsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query(): Builder
    {
        return PcAsset::query()->orderBy('computer_id');
    }

    public function headings(): array
    {
        return [
            'computer_id', 'hostname', 'employee_name', 'status', 'department',
            'location', 'brand', 'model', 'serial_number', 'cpu', 'ram', 'ssd',
            'hdd', 'display', 'operating_system', 'license_key', 'admin_password', 'username',
            'password', 'purchased_date', 'expire_date', 'warranty_period', 'remarks',
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
            $asset->license_key,
            $asset->admin_password,
            $asset->username,
            $asset->password,
            optional($asset->purchased_date)->format('Y-m-d'),
            $asset->expire_permanent ? 'Permanent' : optional($asset->expire_date)->format('Y-m-d'),
            $asset->warranty_period,
            $asset->remarks,
        ];
    }

    // Stream rows in batches instead of loading the whole table into memory.
    public function chunkSize(): int
    {
        return 500;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
