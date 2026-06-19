<?php

namespace App\Imports;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class EmailAccountsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;

    public function __construct(private string $defaultType = 'Email')
    {
    }

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $type = ucfirst(strtolower(trim((string) ($row['type'] ?? ''))));
        if (! in_array($type, EmailAccount::TYPES, true)) {
            $type = $this->defaultType;
        }

        $status = ucfirst(strtolower(trim((string) ($row['status'] ?? ''))));
        if (! in_array($status, EmailAccount::STATUSES, true)) {
            $status = 'Active';
        }

        EmailAccount::create([
            'type'        => $type,
            'status'      => $status,
            'name'        => trim((string) ($row['name'] ?? '')),
            'department'  => $row['department'] ?? null,
            'address'     => trim((string) ($row['address'] ?? '')),
            'username'    => $row['username'] ?? null,
            'password'    => isset($row['password']) && $row['password'] !== '' ? (string) $row['password'] : null,
            'remark'      => $row['remark'] ?? null,
            'modified_by' => Auth::user()?->name ?? 'Import',
        ]);

        $this->imported++;
        return null;
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row'       => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors'    => $failure->errors(),
                'values'    => $failure->values(),
            ];
        }
    }

    public function onError(\Throwable $e): void
    {
        $this->failures[] = [
            'row'       => null,
            'attribute' => 'general',
            'errors'    => [$e->getMessage()],
            'values'    => [],
        ];
    }
}
