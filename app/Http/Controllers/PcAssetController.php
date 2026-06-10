<?php

namespace App\Http\Controllers;

use App\Exports\PcAssetsExport;
use App\Exports\PcAssetsTemplate;
use App\Imports\PcAssetsImport;
use App\Models\ActivityLog;
use App\Models\PcAsset;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PcAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = PcAsset::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('computer_id', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if ($department = $request->get('department')) {
            $query->where('department', $department);
        }

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->get('attention')) {
            $query->whereIn('status', ['Damage', 'Retirement', 'Low Performance']);
        }

        $assets = $query->orderBy('computer_id')->paginate(20)->withQueryString();

        $recentLogs = ActivityLog::where(function ($q) {
                $q->where('subject_type', PcAsset::class)
                  ->orWhere(function ($q2) {
                      $q2->whereIn('action', ['imported'])
                         ->where('description', 'like', '%PC asset%');
                  });
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('pc_assets.index', compact('assets', 'statusCounts', 'recentLogs'));
    }

    public function create()
    {
        return view('pc_assets.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $asset = DB::transaction(function () use ($data, $request) {
            $asset = PcAsset::create($data);
            $this->syncSoftware($asset, $request);

            return $asset;
        });

        ActivityLogger::log(
            action: 'created',
            description: "Created PC asset {$asset->computer_id} ({$asset->hostname})",
            subject: $asset,
        );

        return redirect()->route('pc-assets.index')->with('success', 'PC Asset created.');
    }

    public function show(PcAsset $pcAsset)
    {
        $pcAsset->load('assignments', 'software');

        return view('pc_assets.show', ['asset' => $pcAsset]);
    }

    public function edit(PcAsset $pcAsset)
    {
        return view('pc_assets.edit', ['asset' => $pcAsset]);
    }

    public function update(Request $request, PcAsset $pcAsset)
    {
        $data = $this->validateData($request, $pcAsset->id);
        $data['modified_by'] = $request->user()->name;

        if (empty($data['admin_password'])) unset($data['admin_password']);
        if (empty($data['password'])) unset($data['password']);

        $original = $pcAsset->only(array_keys($data));

        DB::transaction(function () use ($pcAsset, $data, $request) {
            $pcAsset->update($data);
            $this->syncSoftware($pcAsset, $request);
        });

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->reject(fn ($v, $k) => in_array($k, ['admin_password', 'password']))
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated PC asset {$pcAsset->computer_id} ({$pcAsset->hostname})",
            subject: $pcAsset,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('pc-assets.show', $pcAsset)->with('success', 'PC Asset updated.');
    }

    public function destroy(PcAsset $pcAsset)
    {
        $label = "{$pcAsset->computer_id} ({$pcAsset->hostname})";

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted PC asset {$label}",
            subject: $pcAsset,
        );

        $pcAsset->delete();

        return redirect()->route('pc-assets.index')->with('success', 'PC Asset deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:pc_assets,id',
        ]);

        $assets = PcAsset::whereIn('id', $data['ids'])->get();

        foreach ($assets as $asset) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted PC asset {$asset->computer_id} ({$asset->hostname}) [bulk]",
                subject: $asset,
            );
            $asset->delete();
        }

        $count = $assets->count();

        return redirect()->route('pc-assets.index')->with('success', "Deleted {$count} PC asset(s).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new PcAssetsExport(), 'pc-assets-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new PcAssetsTemplate(), 'pc-assets-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new PcAssetsImport();
        $countBefore = PcAsset::count();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported = PcAsset::count() - $countBefore;
        $failed = count($import->failures);
        $skipped = $import->skipped;

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} PC asset(s)" . ($skipped > 0 ? " ({$skipped} skipped as duplicates)" : '') . ($failed > 0 ? " ({$failed} failed)" : ''),
            properties: ['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed],
        );

        if ($failed > 0) {
            $msg = "Imported {$imported} row(s); {$failed} row(s) failed validation" . ($skipped > 0 ? "; {$skipped} duplicate row(s) skipped" : '') . '.';
            $details = collect($import->failures)
                ->take(10)
                ->map(fn ($f) => 'Row ' . ($f['row'] ?? '?') . ' (' . $f['attribute'] . '): ' . implode(', ', $f['errors']))
                ->implode(' | ');
            return back()->with('error', $msg . ' ' . $details);
        }

        $msg = "Imported {$imported} PC asset(s) successfully" . ($skipped > 0 ? "; {$skipped} duplicate row(s) skipped" : '') . '.';
        return back()->with('success', $msg);
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'computer_id' => "required|string|max:255|unique:pc_assets,computer_id,{$id}",
            'hostname' => 'required|string|max:255',
            'employee_name' => 'nullable|string|max:255',
            'status' => 'required|in:Free,Active,Damage,Retirement,Low Performance',
            'department' => ['nullable', \Illuminate\Validation\Rule::in(PcAsset::DEPARTMENTS)],
            'location' => 'required|in:Office,WFH',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'cpu' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'ssd' => 'nullable|string|max:255',
            'hdd' => 'nullable|string|max:255',
            'display' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'license_key' => 'nullable|string|max:255',
            'admin_password' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'purchased_date' => 'nullable|date',
            'expire_permanent' => 'boolean',
            'expire_date' => 'nullable|date',
            'warranty_period' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'software'           => 'nullable|array',
            'software.*.name'    => 'nullable|string|max:255',
            'software.*.version' => 'nullable|string|max:255',
            'software.*.notes'   => 'nullable|string|max:255',
        ]);

        // Software is persisted separately via syncSoftware(); drop it here so
        // it isn't passed to the PcAsset mass-assignment / change tracking.
        unset($data['software']);

        // A permanent license never expires — clear any date.
        $data['expire_permanent'] = (bool) ($data['expire_permanent'] ?? false);
        if ($data['expire_permanent']) {
            $data['expire_date'] = null;
        }

        return $data;
    }

    /**
     * Replace the PC's software list with the rows submitted in the form.
     * Blank rows (no software name) are ignored.
     */
    private function syncSoftware(PcAsset $pcAsset, Request $request): void
    {
        // Software input is validated up-front in validateData().
        $rows = collect($request->input('software', []))
            ->filter(fn ($row) => is_array($row) && filled($row['name'] ?? null))
            ->map(fn ($row) => [
                'name'    => $row['name'],
                'version' => $row['version'] ?? null,
                'notes'   => $row['notes'] ?? null,
            ])
            ->values()
            ->all();

        $pcAsset->software()->delete();
        if (! empty($rows)) {
            $pcAsset->software()->createMany($rows);
        }
    }
}
