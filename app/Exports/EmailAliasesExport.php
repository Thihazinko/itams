<?php

namespace App\Exports;

use App\Models\EmailAlias;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailAliasesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return EmailAlias::with('members')->orderBy('id')->get();
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
