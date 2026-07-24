<?php

namespace App\Http\Controllers;

use App\Exports\LicensesContractsExport;
use App\Exports\LicensesContractsTemplate;
use App\Imports\LicensesContractsImport;
use App\Models\ActivityLog;
use App\Models\LicenseContract;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LicenseContractController extends Controller
{
    public function index(Request $request)
    {
        $query = LicenseContract::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('software_name', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhere('license_info', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $today    = Carbon::today();
        $in30Days = $today->copy()->addDays(30);

        if ($request->get('expiring_soon')) {
            $query->whereBetween('expire_date', [$today, $in30Days])
                  ->whereNotIn('status', ['Terminated', 'Expired']);
        }

        if ($request->get('overdue')) {
            $query->where(function ($q) use ($today) {
                $q->where('status', 'Expired')
                  ->orWhere(function ($q2) use ($today) {
                      $q2->where('expire_date', '<', $today)
                         ->whereNotIn('status', ['Terminated']);
                  });
            });
        }

        $items = $query->orderBy('expire_date')->paginate(20)->withQueryString();

        $recentLogs = ActivityLog::where('subject_type', LicenseContract::class)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $expiringSoon = LicenseContract::whereBetween('expire_date', [$today, $in30Days])
            ->whereNotIn('status', ['Terminated', 'Expired'])
            ->orderBy('expire_date')
            ->limit(8)
            ->get();

        $kpis = [
            'total'    => LicenseContract::count(),
            'active'   => LicenseContract::where('status', 'Active')->count(),
            'expiring' => LicenseContract::whereBetween('expire_date', [$today, $in30Days])
                            ->whereNotIn('status', ['Terminated', 'Expired'])
                            ->count(),
            'pending'  => LicenseContract::where('status', 'Pending')->count(),
            'expired'  => LicenseContract::where('status', 'Expired')
                            ->orWhere(function ($q) use ($today) {
                                $q->where('expire_date', '<', $today)
                                  ->whereNotIn('status', ['Terminated']);
                            })
                            ->count(),
        ];

        return view('licenses_contracts.index', compact('items', 'recentLogs', 'expiringSoon', 'kpis'));
    }

    public function create()
    {
        return view('licenses_contracts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $item = LicenseContract::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created license/contract {$item->software_name}",
            subject: $item,
        );

        return redirect()->route('licenses-contracts.index')->with('success', 'License/Contract created.');
    }

    public function show(LicenseContract $licenses_contract)
    {
        $licenses_contract->load('attachments');

        return view('licenses_contracts.show', ['item' => $licenses_contract]);
    }

    public function edit(LicenseContract $licenses_contract)
    {
        return view('licenses_contracts.edit', ['item' => $licenses_contract]);
    }

    public function update(Request $request, LicenseContract $licenses_contract)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $original = $licenses_contract->only(array_keys($data));
        $licenses_contract->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated license/contract {$licenses_contract->software_name}",
            subject: $licenses_contract,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('licenses-contracts.show', $licenses_contract)->with('success', 'License/Contract updated.');
    }

    public function destroy(LicenseContract $licenses_contract)
    {
        $label = $licenses_contract->software_name;

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted license/contract {$label}",
            subject: $licenses_contract,
        );

        $licenses_contract->delete();

        return redirect()->route('licenses-contracts.index')->with('success', 'License/Contract deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:licenses_contracts,id',
        ]);

        $items = LicenseContract::whereIn('id', $data['ids'])->get();

        foreach ($items as $item) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted license/contract {$item->software_name} [bulk]",
                subject: $item,
            );
            $item->delete();
        }

        $count = $items->count();

        return redirect()->route('licenses-contracts.index')->with('success', "Deleted {$count} license/contract record(s).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new LicensesContractsExport(), 'licenses-contracts-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new LicensesContractsTemplate(), 'licenses-contracts-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new LicensesContractsImport();
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
            description: "Imported {$imported} new license/contract record(s)" . ($updated > 0 ? ", updated {$updated}" : '') . ($failed > 0 ? " ({$failed} failed)" : ''),
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

        $msg = "Imported {$imported} new license/contract record(s)" . ($updated > 0 ? "; updated {$updated} existing record(s)" : '') . ' successfully.';
        return back()->with('success', $msg);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'software_name' => 'required|string|max:255',
            'status' => 'required|in:Active,Expired,Terminated,Pending',
            'renewal_type' => 'required|in:Yearly,Monthly,One Time',
            'license_info' => 'nullable|string',
            'last_renewal_date' => 'nullable|date',
            'start_using_date' => 'nullable|date',
            'expire_permanent' => 'boolean',
            'expire_date' => 'nullable|required_if:expire_permanent,0,false|date',
            'vendor_name' => 'nullable|string|max:255',
            'previous_cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'currency' => 'required|in:MMK,JPY,USD',
            'remarks' => 'nullable|string',
        ]);

        // A permanent license never expires and is a one-time purchase —
        // clear the expiry date and the previous/renewal cost split (the single
        // cost lives in renewal_cost).
        $data['expire_permanent'] = (bool) ($data['expire_permanent'] ?? false);
        if ($data['expire_permanent']) {
            $data['expire_date'] = null;
            $data['previous_cost'] = null;
        }

        return $data;
    }
}
