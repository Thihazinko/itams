<?php

namespace App\Exports;

use App\Models\EmailAccount;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailAccountsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function __construct(private ?string $type = null)
    {
    }

    public function query(): Builder
    {
        $query = EmailAccount::query()->orderBy('id');

        if ($this->type !== null) {
            $query->where('type', $this->type);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['type', 'status', 'name', 'department', 'address', 'username', 'password', 'remark'];
    }

    public function map($a): array
    {
        return [
            $a->type,
            $a->status,
            $a->name,
            $a->department,
            $a->address,
            $a->username,
            $a->password, // decrypted via the model cast
            $a->remark,
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
