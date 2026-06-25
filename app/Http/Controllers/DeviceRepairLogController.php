<?php

namespace App\Http\Controllers;

use App\Exports\DeviceRepairLogsExport;
use App\Exports\DeviceRepairLogsTemplate;
use App\Imports\DeviceRepairLogsImport;
use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\DeviceRepairLog;
use App\Models\PcAsset;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class DeviceRepairLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DeviceRepairLog::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('device_label', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('repair_process', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $logs = $query->orderByDesc('repair_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $recentLogs = ActivityLog::where('subject_type', DeviceRepairLog::class)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('device_repair_logs.index', compact('logs', 'statusCounts', 'recentLogs'));
    }

    public function create()
    {
        return view('device_repair_logs.create', ['deviceOptions' => $this->deviceOptions()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $log = DeviceRepairLog::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created device repair log for {$log->device_label} ({$log->status})",
            subject: $log,
        );

        return redirect()->route('device-repair-logs.index')->with('success', 'Repair log created.');
    }

    public function edit(DeviceRepairLog $deviceRepairLog)
    {
        return view('device_repair_logs.edit', [
            'log'           => $deviceRepairLog,
            'deviceOptions' => $this->deviceOptions(),
        ]);
    }

    public function update(Request $request, DeviceRepairLog $deviceRepairLog)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $original = $deviceRepairLog->only(array_keys($data));
        $deviceRepairLog->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated device repair log for {$deviceRepairLog->device_label} ({$deviceRepairLog->status})",
            subject: $deviceRepairLog,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('device-repair-logs.index')->with('success', 'Repair log updated.');
    }

    public function destroy(DeviceRepairLog $deviceRepairLog)
    {
        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted device repair log for {$deviceRepairLog->device_label}",
            subject: $deviceRepairLog,
        );

        $deviceRepairLog->delete();

        return redirect()->route('device-repair-logs.index')->with('success', 'Repair log deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:device_repair_logs,id',
        ]);

        $logs = DeviceRepairLog::whereIn('id', $data['ids'])->get();

        foreach ($logs as $log) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted device repair log for {$log->device_label} [bulk]",
                subject: $log,
            );
            $log->delete();
        }

        $count = $logs->count();

        return redirect()->route('device-repair-logs.index')->with('success', "Deleted {$count} repair log(s).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new DeviceRepairLogsExport(), 'device-repair-logs-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new DeviceRepairLogsTemplate(), 'device-repair-logs-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new DeviceRepairLogsImport();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported;
        $failed = count($import->failures);

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} device repair log(s)" . ($failed > 0 ? " ({$failed} failed)" : ''),
            properties: ['imported' => $imported, 'failed' => $failed],
        );

        if ($failed > 0) {
            $msg = "Imported {$imported} row(s); {$failed} row(s) failed validation.";
            $details = collect($import->failures)
                ->take(10)
                ->map(fn ($f) => 'Row ' . ($f['row'] ?? '?') . ' (' . $f['attribute'] . '): ' . implode(', ', $f['errors']))
                ->implode(' | ');
            return back()->with('error', $msg . ' ' . $details);
        }

        return back()->with('success', "Imported {$imported} device repair log(s) successfully.");
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'device_id'      => 'required|integer|exists:devices,id',
            'repair_date'    => 'required|date',
            'employee_name'  => 'nullable|string|max:255',
            'department'     => ['nullable', Rule::in(PcAsset::DEPARTMENTS)],
            'repair_process' => 'required|string',
            'status'         => ['required', Rule::in(DeviceRepairLog::STATUSES)],
            'remark'         => 'nullable|string',
        ]);

        // Snapshot a readable label so the log stays meaningful if the device
        // is renamed or deleted later.
        $validated['device_label'] = $this->deviceLabel(Device::find($validated['device_id']));

        return $validated;
    }

    /**
     * Devices for the linked dropdown, ordered by name.
     */
    private function deviceOptions()
    {
        return Device::orderBy('item_name')->get(['id', 'item_name', 'serial_number', 'category']);
    }

    /**
     * A readable identifier for a device: item name plus serial when present.
     */
    private function deviceLabel(?Device $device): string
    {
        if (! $device) {
            return '';
        }

        return $device->serial_number
            ? "{$device->item_name} ({$device->serial_number})"
            : (string) $device->item_name;
    }
}
