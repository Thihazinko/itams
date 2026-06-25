<?php

namespace App\Imports;

use App\Models\PcAsset;
use App\Models\RepairLog;
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

class RepairLogsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsOnFailure, SkipsOnError
{
    use Importable;

    public array $failures = [];
    public int $imported = 0;

    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        $computerId = trim((string) ($row['pc_id'] ?? ''));
        if ($computerId === '') {
            return null;
        }

        $status = ucwords(strtolower(trim((string) ($row['status'] ?? ''))));
        if (! in_array($status, RepairLog::STATUSES, true)) {
            $status = 'In Progress';
        }

        $pcAssetId = PcAsset::where('computer_id', $computerId)->value('id');

        RepairLog::create([
            'pc_asset_id'    => $pcAssetId,
            'computer_id'    => $computerId,
            'repair_date'    => $this->parseDate($row['date'] ?? null) ?? now()->format('Y-m-d'),
            'employee_name'  => $row['employee'] ?? null,
            'department'     => $row['department'] ?? null,
            'repair_process' => (string) ($row['repair_process'] ?? ''),
            'status'         => $status,
            'remark'         => $row['remark'] ?? null,
            'modified_by'    => Auth::user()?->name ?? 'Import',
        ]);

        $this->imported++;

        return null;
    }

    private function parseDate($value): ?string
    {
        if (! $value) {
            return null;
        }

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
            'pc_id'          => 'required|string|max:255',
            'repair_process' => 'required|string',
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
