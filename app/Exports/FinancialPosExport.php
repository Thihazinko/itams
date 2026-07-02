<?php

namespace App\Exports;

use App\Models\FinancialPo;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialPosExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query(): Builder
    {
        return FinancialPo::query()
            ->withSum('receipts as receipts_total', 'paid_amount')
            ->orderBy('po_date');
    }

    public function headings(): array
    {
        return [
            'po_number', 'po_date', 'subject', 'vendor_name', 'category',
            'currency', 'total_amount', 'received_total', 'remaining',
            'source', 'notes',
        ];
    }

    public function map($po): array
    {
        $received = (float) ($po->receipts_total ?? 0);

        return [
            $po->po_number,
            optional($po->po_date)->format('Y-m-d'),
            $po->subject,
            $po->vendor_name,
            $po->category,
            $po->currency,
            (float) $po->total_amount,
            $received,
            (float) $po->total_amount - $received,
            \App\Models\FinancialPo::SOURCES[$po->source]['label'] ?? $po->source,
            $po->notes,
        ];
    }

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
