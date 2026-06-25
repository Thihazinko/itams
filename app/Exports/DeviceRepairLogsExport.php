<?php

namespace App\Exports;

use App\Models\DeviceRepairLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeviceRepairLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    private int $no = 0;

    public function query(): Builder
    {
        return DeviceRepairLog::query()->orderByDesc('repair_date')->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['no', 'date', 'employee', 'department', 'device', 'repair_process', 'status', 'remark'];
    }

    public function map($log): array
    {
        return [
            ++$this->no,
            optional($log->repair_date)->format('Y-m-d'),
            $log->employee_name,
            $log->department,
            $log->device_label,
            $log->repair_process,
            $log->status,
            $log->remark,
        ];
    }

    // Stream rows in batches instead of loading the whole table into memory.
    public function chunkSize(): int
    {
        return 500;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
