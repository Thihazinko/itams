<?php

namespace App\Imports;

use App\Models\Subscription;
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

class SubscriptionsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;
    public int $updated = 0;
    public int $skipped = 0;

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $service = trim((string) ($row['service_type'] ?? ''));
        $project = trim((string) ($row['project_name'] ?? ''));
        $name = trim((string) ($row['subscription_name'] ?? ''));

        $attributes = [
            'service_type' => $row['service_type'] ?? null,
            'project_name' => $row['project_name'] ?? null,
            'subscription_name' => $row['subscription_name'] ?? null,
            'vendor_name' => $row['vendor_name'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'period' => $row['period'] ?? null,
            'previous_cost' => isset($row['previous_cost']) && $row['previous_cost'] !== '' ? (float) $row['previous_cost'] : null,
            'expire_date' => $this->parseDate($row['expire_date'] ?? null),
            'previous_renewal_date' => $this->parseDate($row['previous_renewal_date'] ?? null),
            'start_using_date' => $this->parseDate($row['start_using_date'] ?? null),
            'renewal_cost' => isset($row['renewal_cost']) && $row['renewal_cost'] !== '' ? (float) $row['renewal_cost'] : null,
            'currency' => $row['currency'] ?? 'MMK',
            'renewal_type' => $row['renewal_type'] ?? 'Yearly',
            'renewal_status' => $row['renewal_status'] ?? 'Pending',
            'remarks' => $row['remarks'] ?? null,
            'modified_by' => Auth::user()?->name ?? 'Import',
        ];

        // Upsert by service + project + subscription name (case-insensitive) so
        // edits to an existing subscription are applied instead of being skipped.
        $existing = Subscription::whereRaw('LOWER(service_type) = ?', [strtolower($service)])
            ->whereRaw("LOWER(COALESCE(project_name, '')) = ?", [strtolower($project)])
            ->whereRaw("LOWER(COALESCE(subscription_name, '')) = ?", [strtolower($name)])
            ->first();
        if ($existing) {
            $existing->fill($attributes)->save();
            $this->updated++;
            return null;
        }

        Subscription::create($attributes);
        $this->imported++;
        return null;
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
            'expire_date' => 'required_unless:renewal_type,Pay as you go',
            'currency' => 'nullable|in:MMK,JPY,USD',
            'renewal_type' => 'nullable|in:Yearly,Monthly,Pay as you go,One Time',
            'renewal_status' => 'nullable|in:Pending,Renewed,Expired,Cancelled,Ongoing',
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
