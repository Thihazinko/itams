<?php

namespace App\Imports;

use App\Models\Subscription;
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

class SubscriptionsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;
    public int $skipped = 0;

    /** @var array<string,true>|null Lower-cased "service|project|name" keys of existing rows. */
    private ?array $existingKeys = null;

    private function makeKey(?string $service, ?string $project, ?string $name): string
    {
        return strtolower(trim((string) $service) . '|' . trim((string) $project) . '|' . trim((string) $name));
    }

    private function existingKeys(): array
    {
        return $this->existingKeys ??= Subscription::query()
            ->get(['service_type', 'project_name', 'subscription_name'])
            ->mapWithKeys(fn ($s) => [$this->makeKey($s->service_type, $s->project_name, $s->subscription_name) => true])
            ->all();
    }

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $key = $this->makeKey($row['service_type'] ?? null, $row['project_name'] ?? null, $row['subscription_name'] ?? null);
        if (isset($this->existingKeys()[$key])) {
            $this->skipped++;
            return null;
        }
        $this->existingKeys[$key] = true;

        return new Subscription([
            'service_type' => $row['service_type'] ?? null,
            'project_name' => $row['project_name'] ?? null,
            'subscription_name' => $row['subscription_name'] ?? null,
            'vendor_name' => $row['vendor_name'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'period' => $row['period'] ?? null,
            'previous_cost' => isset($row['previous_cost']) && $row['previous_cost'] !== '' ? (float) $row['previous_cost'] : null,
            'expire_date' => $this->parseDate($row['expire_date'] ?? null),
            'start_using_date' => $this->parseDate($row['start_using_date'] ?? null),
            'renewal_cost' => isset($row['renewal_cost']) && $row['renewal_cost'] !== '' ? (float) $row['renewal_cost'] : null,
            'currency' => $row['currency'] ?? 'MMK',
            'renewal_type' => $row['renewal_type'] ?? 'Yearly',
            'renewal_status' => $row['renewal_status'] ?? 'Pending',
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
            'service_type' => 'required|string|max:100',
            'project_name' => 'required|string|max:255',
            'subscription_name' => 'required|string|max:255',
            'status' => 'nullable|in:Active,Terminated',
            'expire_date' => 'required',
            'currency' => 'nullable|in:MMK,JPY,USD',
            'renewal_type' => 'nullable|in:Yearly,Monthly,Pay as you go,One Time',
            'renewal_status' => 'nullable|in:Pending,Renewed,Expired,Cancelled',
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
