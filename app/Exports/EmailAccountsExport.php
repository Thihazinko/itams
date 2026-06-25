<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesExcelValues;
use App\Models\EmailAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailAccountsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use SanitizesExcelValues;

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
            $this->clean($a->type),
            $this->clean($a->status),
            $this->clean($a->name),
            $this->clean($a->department),
            $this->clean($a->address),
            $this->clean($a->username),
            $this->clean($a->password), // decrypted via the model cast
            $this->clean($a->remark),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
