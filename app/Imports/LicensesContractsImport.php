<?php

namespace App\Imports;

use App\Models\LicenseContract;
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

class LicensesContractsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;
    public int $skipped = 0;

    /** @var array<string,true>|null Lower-cased "software|vendor" keys of existing rows. */
    private ?array $existingKeys = null;

    private function makeKey(?string $software, ?string $vendor): string
    {
        return strtolower(trim((string) $software) . '|' . trim((string) $vendor));
    }

    private function existingKeys(): array
    {
        return $this->existingKeys ??= LicenseContract::query()
            ->get(['software_name', 'vendor_name'])
            ->mapWithKeys(fn ($l) => [$this->makeKey($l->software_name, $l->vendor_name) => true])
            ->all();
    }

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $key = $this->makeKey($row['software_name'] ?? null, $row['vendor_name'] ?? null);
        if (isset($this->existingKeys()[$key])) {
            $this->skipped++;
            return null;
        }
        $this->existingKeys[$key] = true;

        return new LicenseContract([
            'software_name' => $row['software_name'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'renewal_type' => $row['renewal_type'] ?? 'Yearly',
            'license_info' => $row['license_info'] ?? null,
            'last_renewal_date' => $this->parseDate($row['last_renewal_date'] ?? null),
            'expire_date' => $this->parseDate($row['expire_date'] ?? null),
            'vendor_name' => $row['vendor_name'] ?? null,
            'previous_cost' => isset($row['previous_cost']) && $row['previous_cost'] !== '' ? (float) $row['previous_cost'] : null,
            'renewal_cost' => isset($row['renewal_cost']) && $row['renewal_cost'] !== '' ? (float) $row['renewal_cost'] : null,
            'currency' => $row['currency'] ?? 'MMK',
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
            'software_name' => 'required|string|max:255',
            'status' => 'nullable|in:Active,Pending,Expired,Terminated',
            'renewal_type' => 'nullable|in:Yearly,Monthly,Pay as you go,One Time',
            'expire_date' => 'required',
            'currency' => 'nullable|in:MMK,JPY,USD',
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
