<?php

namespace App\Exports;

use App\Models\Device;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DevicesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Device::orderBy('id')->get();
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
