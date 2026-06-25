<?php

namespace App\Http\Controllers;

use App\Exports\RepairLogsExport;
use App\Exports\RepairLogsTemplate;
use App\Imports\RepairLogsImport;
use App\Models\ActivityLog;
use App\Models\PcAsset;
use App\Models\RepairLog;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class RepairLogController extends Controller
{
    public function index(Request $request)
    {
        $query = RepairLog::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('computer_id', 'like', "%{$search}%")
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

        $recentLogs = ActivityLog::where('subject_type', RepairLog::class)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('repair_logs.index', compact('logs', 'statusCounts', 'recentLogs'));
    }

    public function create()
    {
        return view('repair_logs.create', ['pcOptions' => $this->pcOptions()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $log = RepairLog::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created repair log for {$log->computer_id} ({$log->status})",
            subject: $log,
        );

        return redirect()->route('repair-logs.index')->with('success', 'Repair log created.');
    }

    public function edit(RepairLog $repairLog)
    {
        return view('repair_logs.edit', [
            'log'       => $repairLog,
            'pcOptions' => $this->pcOptions(),
        ]);
    }

    public function update(Request $request, RepairLog $repairLog)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $original = $repairLog->only(array_keys($data));
        $repairLog->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated repair log for {$repairLog->computer_id} ({$repairLog->status})",
            subject: $repairLog,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('repair-logs.index')->with('success', 'Repair log updated.');
    }

    public function destroy(RepairLog $repairLog)
    {
        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted repair log for {$repairLog->computer_id}",
            subject: $repairLog,
        );

        $repairLog->delete();

        return redirect()->route('repair-logs.index')->with('success', 'Repair log deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:repair_logs,id',
        ]);

        $logs = RepairLog::whereIn('id', $data['ids'])->get();

        foreach ($logs as $log) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted repair log for {$log->computer_id} [bulk]",
                subject: $log,
            );
            $log->delete();
        }

        $count = $logs->count();

        return redirect()->route('repair-logs.index')->with('success', "Deleted {$count} repair log(s).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new RepairLogsExport(), 'repair-logs-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new RepairLogsTemplate(), 'repair-logs-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new RepairLogsImport();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported;
        $failed = count($import->failures);

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} repair log(s)" . ($failed > 0 ? " ({$failed} failed)" : ''),
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

        return back()->with('success', "Imported {$imported} repair log(s) successfully.");
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'computer_id'    => 'required|string|max:255',
            'repair_date'    => 'required|date',
            'employee_name'  => 'nullable|string|max:255',
            'department'     => ['nullable', Rule::in(PcAsset::DEPARTMENTS)],
            'repair_process' => 'required|string',
            'status'         => ['required', Rule::in(RepairLog::STATUSES)],
            'remark'         => 'nullable|string',
        ]);

        // Resolve the linked PC (computer_id is unique in PC Master).
        $data['pc_asset_id'] = PcAsset::where('computer_id', $data['computer_id'])->value('id');

        return $data;
    }

    /**
     * Computer IDs for the linked dropdown, each carrying the current employee
     * and department so the form can auto-fill those fields on selection.
     */
    private function pcOptions()
    {
        return PcAsset::orderBy('computer_id')->get(['computer_id', 'employee_name', 'department']);
    }
}
