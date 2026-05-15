<?php

namespace App\Imports;

use App\Models\PcAsset;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class PcAssetsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;
    public int $skipped = 0;

    /** @var array<string,true>|null Lower-cased existing computer_ids. */
    private ?array $existingIds = null;

    private function existingIds(): array
    {
        return $this->existingIds ??= PcAsset::query()
            ->pluck('computer_id')
            ->mapWithKeys(fn ($id) => [strtolower(trim((string) $id)) => true])
            ->all();
    }

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $computerId = trim((string) ($row['computer_id'] ?? ''));
        if ($computerId !== '' && isset($this->existingIds()[strtolower($computerId)])) {
            $this->skipped++;
            return null;
        }
        if ($computerId !== '') {
            $this->existingIds[strtolower($computerId)] = true;
        }

        return new PcAsset([
            'computer_id' => $row['computer_id'] ?? null,
            'hostname' => $row['hostname'] ?? null,
            'employee_name' => $row['employee_name'] ?? null,
            'status' => $row['status'] ?? 'Free',
            'department' => $row['department'] ?? null,
            'location' => $row['location'] ?? 'Office',
            'brand' => $row['brand'] ?? null,
            'model' => $row['model'] ?? null,
            'serial_number' => $row['serial_number'] ?? null,
            'cpu' => $row['cpu'] ?? null,
            'ram' => $row['ram'] ?? null,
            'ssd' => $row['ssd'] ?? null,
            'hdd' => $row['hdd'] ?? null,
            'display' => $row['display'] ?? null,
            'operating_system' => $row['operating_system'] ?? null,
            'admin_password' => isset($row['admin_password']) && $row['admin_password'] !== '' ? (string) $row['admin_password'] : null,
            'username' => isset($row['username']) && $row['username'] !== '' ? (string) $row['username'] : null,
            'password' => isset($row['password']) && $row['password'] !== '' ? (string) $row['password'] : null,
            'purchased_date' => $this->parseDate($row['purchased_date'] ?? null),
            'warranty_period' => $row['warranty_period'] ?? null,
            'remarks' => $row['remarks'] ?? null,
            'modified_by' => Auth::user()?->name ?? 'Import',
        ]);
    }

    private function parseDate($value): ?string
    {
        if (! $value) return null;

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'computer_id' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'status' => 'nullable|in:Free,Active,Damage,Retirement,Low Performance',
            'department' => ['nullable', \Illuminate\Validation\Rule::in(\App\Models\PcAsset::DEPARTMENTS)],
            'location' => 'nullable|in:Office,WFH',
        ];
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    public function onError(\Throwable $e): void
    {
        $this->failures[] = [
            'row' => null,
            'attribute' => 'general',
            'errors' => [$e->getMessage()],
            'values' => [],
        ];
    }
}
