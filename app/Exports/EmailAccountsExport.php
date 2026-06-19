<?php

namespace App\Exports;

use App\Models\EmailAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailAccountsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private ?string $type = null)
    {
    }

    public function collection()
    {
        $query = EmailAccount::orderBy('id');

        if ($this->type !== null) {
            $query->where('type', $this->type);
        }

        return $query->get();
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
