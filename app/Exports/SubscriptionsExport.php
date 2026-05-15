<?php

namespace App\Exports;

use App\Models\Subscription;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriptionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Subscription::orderBy('expire_date')->get();
    }

    public function headings(): array
    {
        return [
            'service_type', 'project_name', 'subscription_name', 'vendor_name', 'status',
            'period', 'previous_cost', 'expire_date', 'renewal_cost', 'currency',
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
            $s->renewal_cost,
            $s->currency,
            $s->renewal_type,
            $s->renewal_status,
            $s->remarks,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
