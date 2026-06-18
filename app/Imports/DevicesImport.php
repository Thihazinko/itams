<?php

namespace App\Imports;

use App\Models\Device;
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

class DevicesImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
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

        $serial = trim((string) ($row['serial_number'] ?? ''));

        $attributes = [
            'item_name'         => trim((string) ($row['item_name'] ?? '')),
            'category'          => $row['category'] ?? null,
            'serial_number'     => $serial !== '' ? $serial : null,
            'location'          => $row['location'] ?? null,
            'qty'               => (int) ($row['qty'] ?? 1) ?: 1,
            'status'            => $row['status'] ?? 'Free',
            'description'       => $row['description'] ?? null,
            'vendor'            => $row['vendor'] ?? null,
            'purchased_date'    => $this->parseDate($row['purchased_date'] ?? null),
            'warranty'          => $row['warranty'] ?? null,
            'delivery_date'     => $this->parseDate($row['delivery_date'] ?? null),
            'delivery_location' => $row['delivery_location'] ?? null,
            'remark'            => $row['remark'] ?? null,
            'modified_by'       => Auth::user()?->name ?? 'Import',
        ];

        // Upsert by serial number so edits to an existing device are applied.
        // Rows without a serial can't be matched, so they always create.
        if ($serial !== '') {
            $existing = Device::whereRaw('LOWER(serial_number) = ?', [strtolower($serial)])->first();
            if ($existing) {
                $existing->fill($attributes)->save();
                $this->updated++;
                return null;
            }
        }

        Device::create($attributes);
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
            'item_name' => 'required|string|max:255',
            'qty'       => 'nullable|integer|min:1',
            'status'    => ['nullable', \Illuminate\Validation\Rule::in(\App\Models\Device::STATUSES)],
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
