<?php

namespace App\Exports;

use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DevicesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query(): Builder
    {
        return Device::query()->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'item_name', 'category', 'serial_number', 'location', 'qty', 'status',
            'description', 'vendor', 'purchased_date', 'warranty',
            'delivery_date', 'delivery_location', 'remark',
        ];
    }

    public function map($d): array
    {
        return [
            $d->item_name,
            $d->category,
            $d->serial_number,
            $d->location,
            $d->qty,
            $d->status,
            $d->description,
            $d->vendor,
            optional($d->purchased_date)->format('Y-m-d'),
            $d->warranty,
            optional($d->delivery_date)->format('Y-m-d'),
            $d->delivery_location,
            $d->remark,
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
