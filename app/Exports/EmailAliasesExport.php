<?php

namespace App\Exports;

use App\Models\EmailAlias;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailAliasesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query(): Builder
    {
        return EmailAlias::query()->with('members')->orderBy('id');
    }

    public function headings(): array
    {
        return ['main_email', 'members', 'remark'];
    }

    public function map($alias): array
    {
        return [
            $alias->main_email,
            $alias->members->pluck('address')->implode(', '),
            $alias->remark,
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
