<?php

namespace App\Exports;

use App\Models\RepairLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RepairLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    private int $no = 0;

    public function query(): Builder
    {
        return RepairLog::query()->orderByDesc('repair_date')->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['no', 'date', 'employee', 'department', 'pc_id', 'repair_process', 'status', 'remark'];
    }

    public function map($log): array
    {
        return [
            ++$this->no,
            optional($log->repair_date)->format('Y-m-d'),
            $log->employee_name,
            $log->department,
            $log->computer_id,
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
