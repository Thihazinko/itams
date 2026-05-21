<?php

namespace App\Http\Controllers;

use App\Exports\SubscriptionsExport;
use App\Exports\SubscriptionsTemplate;
use App\Imports\SubscriptionsImport;
use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('subscription_name', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('service_type')) {
            $query->where('service_type', 'like', "%{$type}%");
        }

        if ($renewal = $request->get('renewal_status')) {
            $query->where('renewal_status', $renewal);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->get('expiring_soon')) {
            $query->whereBetween('expire_date', [Carbon::today(), Carbon::today()->addDays(30)])
                  ->where('renewal_status', '!=', 'Renewed');
        }

        if ($request->get('overdue')) {
            $today = Carbon::today();
            $query->where(function ($q) use ($today) {
                $q->where('renewal_status', 'Expired')
                  ->orWhere(function ($q2) use ($today) {
                      $q2->where('expire_date', '<', $today)
                         ->whereNotIn('renewal_status', ['Renewed', 'Cancelled']);
                  });
            });
        }

        $subscriptions = $query->orderBy('expire_date')->paginate(20)->withQueryString();

        $activeRenewals = SubscriptionRenewal::query()
            ->whereIn('subscription_id', $subscriptions->pluck('id'))
            ->whereIn('status', [
                SubscriptionRenewal::STATUS_DRAFT,
                SubscriptionRenewal::STATUS_PENDING,
                SubscriptionRenewal::STATUS_FIRST_APPROVED,
                SubscriptionRenewal::STATUS_PENDING_SECOND,
                SubscriptionRenewal::STATUS_APPROVED,
            ])
            ->get()
            ->keyBy('subscription_id');

        $approverChoices = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $recentLogs = ActivityLog::where(function ($q) {
                $q->where('subject_type', Subscription::class)
                  ->orWhere(function ($q2) {
                      $q2->whereIn('action', ['imported'])
                         ->where('description', 'like', '%subscription%');
                  });
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $today    = Carbon::today();
        $in30Days = $today->copy()->addDays(30);

        $expiringSoon = Subscription::whereBetween('expire_date', [$today, $in30Days])
            ->where('renewal_status', '!=', 'Renewed')
            ->orderBy('expire_date')
            ->limit(8)
            ->get();

        $kpis = [
            'total'    => Subscription::count(),
            'active'   => Subscription::where('status', 'Active')->count(),
            'expiring' => Subscription::whereBetween('expire_date', [$today, $in30Days])
                            ->where('renewal_status', '!=', 'Renewed')
                            ->count(),
            'pending'  => Subscription::where('renewal_status', 'Pending')->count(),
            'expired'  => Subscription::where('renewal_status', 'Expired')
                            ->orWhere(function ($q) use ($today) {
                                $q->where('expire_date', '<', $today)
                                  ->whereNotIn('renewal_status', ['Renewed', 'Cancelled']);
                            })
                            ->count(),
        ];

        return view('subscriptions.index', compact('subscriptions', 'recentLogs', 'expiringSoon', 'kpis', 'activeRenewals', 'approverChoices'));
    }

    public function create()
    {
        return view('subscriptions.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $subscription = Subscription::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Created subscription {$subscription->subscription_name} ({$subscription->project_name})",
            subject: $subscription,
        );

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created.');
    }

    public function edit(Subscription $subscription)
    {
        return view('subscriptions.edit', ['subscription' => $subscription]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $this->validateData($request);
        $data['modified_by'] = $request->user()->name;

        $original = $subscription->only(array_keys($data));
        $subscription->update($data);

        $changes = collect($data)
            ->reject(fn ($v, $k) => ($original[$k] ?? null) == $v)
            ->keys()
            ->all();

        ActivityLogger::log(
            action: 'updated',
            description: "Updated subscription {$subscription->subscription_name} ({$subscription->project_name})",
            subject: $subscription,
            properties: ['changed_fields' => $changes],
        );

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated.');
    }

    public function destroy(Subscription $subscription)
    {
        $label = "{$subscription->subscription_name} ({$subscription->project_name})";

        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted subscription {$label}",
            subject: $subscription,
        );

        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:subscriptions,id',
        ]);

        $subs = Subscription::whereIn('id', $data['ids'])->get();

        foreach ($subs as $sub) {
            ActivityLogger::log(
                action: 'deleted',
                description: "Deleted subscription {$sub->subscription_name} ({$sub->project_name}) [bulk]",
                subject: $sub,
            );
            $sub->delete();
        }

        $count = $subs->count();

        return redirect()->route('subscriptions.index')->with('success', "Deleted {$count} subscription(s).");
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new SubscriptionsExport(), 'subscriptions-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }

    public function template(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new SubscriptionsTemplate(), 'subscriptions-template.' . $ext, $format);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $import = new SubscriptionsImport();
        $countBefore = Subscription::count();
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        $imported = $import->imported = Subscription::count() - $countBefore;
        $failed = count($import->failures);
        $skipped = $import->skipped;

        ActivityLogger::log(
            action: 'imported',
            description: "Imported {$imported} subscription(s)" . ($skipped > 0 ? " ({$skipped} skipped as duplicates)" : '') . ($failed > 0 ? " ({$failed} failed)" : ''),
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

        $msg = "Imported {$imported} subscription(s) successfully" . ($skipped > 0 ? "; {$skipped} duplicate row(s) skipped" : '') . '.';
        return back()->with('success', $msg);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'service_type' => 'required|string|max:100',
            'project_name' => 'required|string|max:255',
            'subscription_name' => 'required|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Terminated',
            'period' => 'nullable|string|max:255',
            'previous_cost' => 'nullable|numeric|min:0',
            'expire_date' => 'required|date',
            'renewal_cost' => 'nullable|numeric|min:0',
            'currency' => 'required|in:MMK,JPY,USD',
            'renewal_type' => 'required|in:Yearly,Monthly,Pay as you go,One Time',
            'renewal_status' => 'required|in:Pending,Renewed,Expired,Cancelled',
            'remarks' => 'nullable|string',
        ]);
    }
}
