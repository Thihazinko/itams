<?php

namespace App\Support;

use App\Models\LicenseContract;
use App\Models\NotificationRead;
use App\Models\NotificationSetting;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Live count of records that currently warrant a renewal notification,
 * gated per-module by NotificationSetting (enabled flag + reminder window),
 * and reduced per-user by stored "read" rows whose signature still matches
 * the current expire_date + urgency bucket.
 *
 * Drives the topbar bell badge. Does NOT query the historical notifications
 * table — the badge reflects the current state of Subscriptions and Licenses.
 */
class ExpiryNotificationCounter
{
    public static function total(?User $user = null): int
    {
        return self::summary($user)['total'];
    }

    /**
     * @return array{
     *   total:int, overdue:int, due_soon:int, upcoming:int,
     *   by_module: array<string, array{enabled:bool, total:int, overdue:int, due_soon:int, upcoming:int}>
     * }
     */
    public static function summary(?User $user = null): array
    {
        $today = Carbon::today();
        $soonCutoff = $today->copy()->addDays(7);

        // Pre-load this user's still-valid reads (one query, modules combined).
        $reads = $user ? self::loadValidReads($user, $today) : [];

        $byModule = [];
        $total = $overdue = $dueSoon = 0;

        foreach (['subscriptions', 'licenses_contracts'] as $module) {
            $setting = self::setting($module);
            $enabled = $setting && $setting->enabled;

            if (! $enabled) {
                $byModule[$module] = [
                    'enabled' => false, 'total' => 0, 'overdue' => 0, 'due_soon' => 0, 'upcoming' => 0,
                ];
                continue;
            }

            $threshold = $today->copy()->addDays(max(1, $setting->windowDays()));
            $rows = self::rowsInWindow($module, $threshold);

            $mTotal = $mOverdue = $mDueSoon = 0;
            foreach ($rows as $row) {
                if ($user && self::isRead($reads, $module, $row, $today)) continue;
                $mTotal++;
                $days = $today->diffInDays(Carbon::parse($row->expire_date), false);
                if ($days < 0) $mOverdue++;
                elseif ($days >= 0 && $days <= 7) $mDueSoon++;
            }
            $mUpcoming = max(0, $mTotal - $mOverdue - $mDueSoon);

            $byModule[$module] = [
                'enabled'  => true,
                'total'    => $mTotal,
                'overdue'  => $mOverdue,
                'due_soon' => $mDueSoon,
                'upcoming' => $mUpcoming,
            ];

            $total   += $mTotal;
            $overdue += $mOverdue;
            $dueSoon += $mDueSoon;
        }

        return [
            'total'     => $total,
            'overdue'   => $overdue,
            'due_soon'  => $dueSoon,
            'upcoming'  => max(0, $total - $overdue - $dueSoon),
            'by_module' => $byModule,
        ];
    }

    /**
     * @return array<string, array<int, string>>  module => [id => stored_signature]
     */
    private static function loadValidReads(User $user, Carbon $today): array
    {
        $out = ['subscriptions' => [], 'licenses_contracts' => []];

        NotificationRead::query()
            ->where('user_id', $user->id)
            ->get(['module', 'notifiable_id', 'read_signature'])
            ->each(function (NotificationRead $r) use (&$out) {
                $out[$r->module][$r->notifiable_id] = $r->read_signature;
            });

        return $out;
    }

    private static function isRead(array $reads, string $module, object $row, Carbon $today): bool
    {
        $storedSig = $reads[$module][$row->id] ?? null;
        if (! $storedSig) return false;
        $days = (int) $today->diffInDays(Carbon::parse($row->expire_date), false);
        $currentSig = NotificationRead::signature(Carbon::parse($row->expire_date), $days);
        return $storedSig === $currentSig;
    }

    private static function rowsInWindow(string $module, Carbon $threshold): \Illuminate\Support\Collection
    {
        $base = $module === 'subscriptions'
            ? self::subscriptionsBase()
            : self::licenseContractsBase();
        return $base->whereDate('expire_date', '<=', $threshold)
            ->get(['id', 'expire_date']);
    }

    private static function subscriptionsBase(): Builder
    {
        return Subscription::query()
            ->where('status', 'Active')
            ->where('renewal_status', '!=', 'Renewed');
    }

    private static function licenseContractsBase(): Builder
    {
        return LicenseContract::query()
            ->whereNotIn('status', ['Terminated']);
    }

    private static function setting(string $module): ?NotificationSetting
    {
        try {
            return NotificationSetting::query()->where('module', $module)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
