<?php

namespace App\Http\Controllers;

use App\Models\LicenseContract;
use App\Models\NotificationRead;
use App\Models\NotificationSetting;
use App\Models\Subscription;
use App\Support\ExpiryNotificationCounter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $today = Carbon::today();
        $user  = $request->user();

        $module = $request->get('module');
        if (! in_array($module, ['subscriptions', 'licenses_contracts'], true)) {
            $module = null;
        }

        $status = $request->get('status');
        if (! in_array($status, ['unread', 'read'], true)) {
            $status = null;
        }

        // Load this user's reads keyed by module + id for O(1) lookup.
        $userReads = $this->loadUserReads($user);

        $subs = $this->subscriptionItems($today, $userReads);
        $lcs = $this->licenseContractItems($today, $userReads);

        // Summary for the badge/per-user (subtract read items).
        $summary = ExpiryNotificationCounter::summary($user);

        $items = match ($module) {
            'subscriptions'      => $subs,
            'licenses_contracts' => $lcs,
            default              => $subs->concat($lcs),
        };

        // Read/unread filter — applied AFTER summary so KPIs reflect totals, not the filter.
        if ($status === 'unread') {
            $items = $items->filter(fn ($i) => ! $i->is_read);
        } elseif ($status === 'read') {
            $items = $items->filter(fn ($i) => $i->is_read);
        }

        // Unread first, then most urgent first.
        $items = $items->sortBy([
            fn ($a, $b) => ($a->is_read <=> $b->is_read),
            fn ($a, $b) => ($a->days_remaining <=> $b->days_remaining),
        ])->values();

        $notifications = $this->paginate($items, $request);

        // Read/unread KPI counts across all enabled modules.
        $allItems = $subs->concat($lcs);
        $unreadAll = $allItems->where('is_read', false)->count();
        $readAll = $allItems->where('is_read', true)->count();

        return view('notifications.index', compact(
            'notifications', 'summary', 'module', 'status', 'unreadAll', 'readAll'
        ));
    }

    public function markRead(Request $request, string $module, int $id)
    {
        $user = $request->user();
        $today = Carbon::today();

        $record = $module === 'subscriptions'
            ? Subscription::query()->where('id', $id)->first(['id', 'expire_date'])
            : LicenseContract::query()->where('id', $id)->first(['id', 'expire_date']);

        if (! $record || ! $record->expire_date) {
            return back()->with('error', 'Item no longer exists.');
        }

        $days = (int) $today->diffInDays(Carbon::parse($record->expire_date), false);
        $signature = NotificationRead::signature(Carbon::parse($record->expire_date), $days);

        NotificationRead::updateOrCreate(
            ['user_id' => $user->id, 'module' => $module, 'notifiable_id' => $id],
            ['read_signature' => $signature, 'read_at' => now()],
        );

        return back();
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // Mark every currently-listed item (across modules) as read at its current signature.
        $userReads = $this->loadUserReads($user);
        $subs = $this->subscriptionItems($today, $userReads);
        $lcs  = $this->licenseContractItems($today, $userReads);

        $count = 0;
        foreach ($subs->concat($lcs) as $item) {
            if ($item->is_read) continue;
            NotificationRead::updateOrCreate(
                ['user_id' => $user->id, 'module' => $item->module, 'notifiable_id' => $item->record_id],
                ['read_signature' => $item->signature, 'read_at' => now()],
            );
            $count++;
        }

        $msg = $count > 0
            ? "Marked {$count} notification" . ($count === 1 ? '' : 's') . ' as read.'
            : 'No unread notifications to mark.';

        return back()->with('success', $msg);
    }

    /**
     * @return array<string, array<int, string>>  module => [id => stored_signature]
     */
    private function loadUserReads($user): array
    {
        $out = ['subscriptions' => [], 'licenses_contracts' => []];
        if (! $user) return $out;

        NotificationRead::query()
            ->where('user_id', $user->id)
            ->get(['module', 'notifiable_id', 'read_signature'])
            ->each(function (NotificationRead $r) use (&$out) {
                $out[$r->module][$r->notifiable_id] = $r->read_signature;
            });

        return $out;
    }

    private function subscriptionItems(Carbon $today, array $userReads): Collection
    {
        $setting = NotificationSetting::query()->where('module', 'subscriptions')->first();
        if (! $setting || ! $setting->enabled) {
            return collect();
        }

        $threshold = $today->copy()->addDays(max(1, $setting->windowDays()));

        return Subscription::query()
            ->where('status', 'Active')
            ->where('renewal_status', '!=', 'Renewed')
            ->whereDate('expire_date', '<=', $threshold)
            ->orderBy('expire_date')
            ->get()
            ->map(function (Subscription $s) use ($today, $userReads) {
                $days = (int) $today->diffInDays($s->expire_date, false);
                $sig  = NotificationRead::signature($s->expire_date, $days);
                $stored = $userReads['subscriptions'][$s->id] ?? null;
                return (object) [
                    'module'         => 'subscriptions',
                    'module_label'   => 'Subscription',
                    'module_icon'    => 'bi-calendar-event',
                    'record_id'      => $s->id,
                    'title'          => "Renewal Due: {$s->subscription_name}",
                    'message'        => "{$s->subscription_name} ({$s->service_type}) expires on {$s->expire_date->format('Y-m-d')}.",
                    'expire_date'    => $s->expire_date,
                    'days_remaining' => $days,
                    'signature'      => $sig,
                    'is_read'        => $stored === $sig,
                    'link_route'     => 'subscriptions.edit',
                    'link_param'     => $s,
                    'link_label'     => 'View subscription',
                ];
            });
    }

    private function licenseContractItems(Carbon $today, array $userReads): Collection
    {
        $setting = NotificationSetting::query()->where('module', 'licenses_contracts')->first();
        if (! $setting || ! $setting->enabled) {
            return collect();
        }

        $threshold = $today->copy()->addDays(max(1, $setting->windowDays()));

        return LicenseContract::query()
            ->whereNotIn('status', ['Terminated'])
            ->whereDate('expire_date', '<=', $threshold)
            ->orderBy('expire_date')
            ->get()
            ->map(function (LicenseContract $lc) use ($today, $userReads) {
                $days = (int) $today->diffInDays($lc->expire_date, false);
                $sig  = NotificationRead::signature($lc->expire_date, $days);
                $stored = $userReads['licenses_contracts'][$lc->id] ?? null;
                $vendor = $lc->vendor_name ? " — {$lc->vendor_name}" : '';
                return (object) [
                    'module'         => 'licenses_contracts',
                    'module_label'   => 'License & Contract',
                    'module_icon'    => 'bi-file-earmark-text',
                    'record_id'      => $lc->id,
                    'title'          => "Renewal Due: {$lc->software_name}",
                    'message'        => "{$lc->software_name}{$vendor} expires on {$lc->expire_date->format('Y-m-d')}.",
                    'expire_date'    => $lc->expire_date,
                    'days_remaining' => $days,
                    'signature'      => $sig,
                    'is_read'        => $stored === $sig,
                    'link_route'     => 'licenses-contracts.edit',
                    'link_param'     => $lc,
                    'link_label'     => 'View license / contract',
                ];
            });
    }

    private function paginate(Collection $items, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->get('page', 1));
        $slice = $items->forPage($page, self::PER_PAGE)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
