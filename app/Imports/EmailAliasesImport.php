<?php

namespace App\Imports;

use App\Models\EmailAlias;
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

class EmailAliasesImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;
    public int $skipped = 0;

    /** Lower-cased main_email values already handled, to skip duplicates within the same file. */
    private array $seenMain = [];

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $mainEmail = trim((string) ($row['main_email'] ?? ''));
        $mainKey = strtolower($mainEmail);

        // Skip a main email that's already been imported in this file or already
        // exists in the database, instead of creating a duplicate alias.
        if ($mainKey !== '' && (isset($this->seenMain[$mainKey])
            || EmailAlias::whereRaw('LOWER(main_email) = ?', [$mainKey])->exists())) {
            $this->skipped++;
            return null;
        }
        $this->seenMain[$mainKey] = true;

        $alias = EmailAlias::create([
            'main_email'  => $mainEmail,
            'remark'      => $row['remark'] ?? null,
            'modified_by' => Auth::user()?->name ?? 'Import',
        ]);

        // Members come in as a single cell, separated by comma, semicolon, or newline.
        $raw = (string) ($row['members'] ?? '');
        $addresses = preg_split('/[,;\r\n]+/', $raw) ?: [];
        $seen = [];
        foreach ($addresses as $address) {
            $address = trim($address);
            $key = strtolower($address);
            if ($address === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $alias->members()->create(['address' => $address]);
        }

        $this->imported++;
        return null;
    }

    public function rules(): array
    {
        return [
            'main_email' => 'required|string|max:255',
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
