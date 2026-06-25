<?php

namespace App\Exports;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriptionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query(): Builder
    {
        return Subscription::query()->orderBy('expire_date');
    }

    public function headings(): array
    {
        return [
            'service_type', 'project_name', 'subscription_name', 'vendor_name', 'status',
            'period', 'previous_cost', 'expire_date', 'start_using_date', 'renewal_cost', 'currency',
            'renewal_type', 'renewal_status', 'remarks',
        ];
    }

    public function map($s): array
    {
        return [
            $s->service_type,
            $s->project_name,
            $s->subscription_name,
            $s->vendor_name,
            $s->status,
            $s->period,
            $s->previous_cost,
            optional($s->expire_date)->format('Y-m-d'),
            optional($s->start_using_date)->format('Y-m-d'),
            $s->renewal_cost,
            $s->currency,
            $s->renewal_type,
            $s->renewal_status,
            $s->remarks,
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
