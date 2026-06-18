<?php

namespace App\Http\Controllers;

use App\Exports\DevicesExport;
use App\Exports\DevicesTemplate;
use App\Imports\DevicesImport;
use App\Models\ActivityLog;
use App\Models\Device;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = Device::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('delivery_location', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->get('attention')) {
            $query->whereIn('status', ['Damage', 'Retirement', 'Lost']);
        }

        $statusCounts = Device::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $devices = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $recentLogs = ActivityLog::where('subject_type', Device::class)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('devices.index', compact('devices', 'statusCounts', 'recentLogs'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $device = Device::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created device {$device->item_name}",
            subject: $device,
        );

        return redirect()->route('devices.index')->with('success', 'Device created.');
    }

    public function show(Device $device)
    {
        return view('devices.show', compact('device'));
    }

    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $original = $device->only(array_keys($data));
        $device->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated device {$device->item_name}",
            subject: $device,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('devices.index')->with('success', 'Device updated.');
    }

    public function destroy(Device $device)
    {
        $label = $device->item_name;

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted device {$label}",
            subject: $device,
        );

        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:devices,id',
        ]);

        $devices = Device::whereIn('id', $data['ids'])->get();

        foreach ($devices as $device) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted device {$device->item_name} [bulk]",
                subject: $device,
            );
            $device->delete();
        }

        $count = $devices->count();

        return redirect()->route('devices.index')->with('success', "Deleted {$count} device(s).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new DevicesExport(), 'devices-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new DevicesTemplate(), 'devices-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new DevicesImport();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported;
        $updated = $import->updated;
        $failed = count($import->failures);

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} new device(s)" . ($updated > 0 ? ", updated {$updated}" : '') . ($failed > 0 ? " ({$failed} failed)" : ''),
            properties: ['imported' => $imported, 'updated' => $updated, 'failed' => $failed],
        );

        if ($failed > 0) {
            $msg = "Imported {$imported} new row(s)" . ($updated > 0 ? "; updated {$updated} existing row(s)" : '') . "; {$failed} row(s) failed validation.";
            $details = collect($import->failures)
                ->take(10)
                ->map(fn ($f) => 'Row ' . ($f['row'] ?? '?') . ' (' . $f['attribute'] . '): ' . implode(', ', $f['errors']))
                ->implode(' | ');
            return back()->with('error', $msg . ' ' . $details);
        }

        $msg = "Imported {$imported} new device(s)" . ($updated > 0 ? "; updated {$updated} existing device(s)" : '') . ' successfully.';
        return back()->with('success', $msg);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'item_name'         => 'required|string|max:255',
            'category'          => 'nullable|string|max:255',
            'serial_number'     => 'nullable|string|max:255',
            'location'          => 'nullable|string|max:255',
            'qty'               => 'required|integer|min:1',
            'status'            => ['required', \Illuminate\Validation\Rule::in(Device::STATUSES)],
            'description'       => 'nullable|string',
            'vendor'            => 'nullable|string|max:255',
            'purchased_date'    => 'nullable|date',
            'warranty'          => 'nullable|string|max:255',
            'delivery_date'     => 'nullable|date',
            'delivery_location' => 'nullable|string|max:255',
            'remark'            => 'nullable|string',
        ]);
    }
}
