<?php

namespace App\Models;

use App\Models\MailSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    public const CURRENCIES = [
        'MMK' => 'Myanmar (MMK)',
        'JPY' => 'Japan (JPY)',
        'USD' => 'USD',
    ];

    protected $fillable = [
        'service_type', 'project_name', 'subscription_name', 'vendor_name', 'status',
        'period', 'previous_cost', 'expire_date', 'previous_renewal_date', 'start_using_date', 'renewal_cost', 'currency',
        'renewal_type', 'reminder_date', 'renewal_status', 'remarks', 'modified_by',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'previous_renewal_date' => 'date',
        'start_using_date' => 'date',
        'reminder_date' => 'date',
        'previous_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
    ];

    /**
     * How long this subscription has been in use, from the start using date up
     * to today — but capped at the expire date once it has expired (unless it
     * was renewed). E.g. "2 years 3 months", "1 month", "12 days".
     * Returns null when there's no start date or it is in the future.
     */
    public function getUsageDurationAttribute(): ?string
    {
        $start = $this->start_using_date;
        $today = Carbon::today();

        if (! $start || $start->gt($today)) {
            return null;
        }

        // Usage stops at expiry: an expired, non-renewed subscription hasn't
        // been in use since its expire date, so end the span there rather than
        // letting it keep counting up to today.
        $end = ($this->expire_date && $this->expire_date->lt($today) && $this->renewal_status !== 'Renewed')
            ? $this->expire_date
            : $today;

        if ($start->gt($end)) {
            return null;
        }

        $diff = $start->diff($end);

        $parts = [];
        if ($diff->y) {
            $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m) {
            $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }
        // Show days only for short spans (under a month) to keep it concise.
        if (! $diff->y && ! $diff->m) {
            $parts[] = $diff->d . ' day' . ($diff->d === 1 ? '' : 's');
        }

        return implode(' ', $parts);
    }

    /**
     * Renewal Status is derived automatically from Status and Expire Date:
     *   - Terminated                      => Cancelled
     *   - Active, past the expire date    => Expired
     *   - Active, within 1 month of expiry=> Pending
     *   - Active, otherwise (or no expiry)=> Renewed
     */
    public function computeRenewalStatus(?Carbon $today = null): string
    {
        $today = $today ? $today->copy()->startOfDay() : Carbon::today();

        if ($this->status === 'Terminated') {
            return 'Cancelled';
        }

        // Pay-as-you-go (usage-based billing) has no renewal cycle — always Ongoing.
        if ($this->renewal_type === 'Pay as you go') {
            return 'Ongoing';
        }

        if (! $this->expire_date) {
            return 'Renewed';
        }

        $expire = Carbon::parse($this->expire_date)->startOfDay();

        if ($expire->lt($today)) {
            return 'Expired';
        }

        if ($expire->lte($today->copy()->addMonthNoOverflow())) {
            return 'Pending';
        }

        return 'Renewed';
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class)->orderByDesc('id');
    }

    public function activeRenewal(): ?SubscriptionRenewal
    {
        return $this->renewals()
            ->whereIn('status', [
                SubscriptionRenewal::STATUS_DRAFT,
                SubscriptionRenewal::STATUS_PENDING,
                SubscriptionRenewal::STATUS_FIRST_APPROVED,
                SubscriptionRenewal::STATUS_PENDING_SECOND,
                SubscriptionRenewal::STATUS_APPROVED,
            ])
            ->first();
    }

    /**
     * Add the free-text Period (e.g. "1 Year", "2 years", "3", "6 months") to a
     * base date. A bare number is treated as years. Returns null if unparseable.
     */
    public function addPeriodTo(Carbon $base): ?Carbon
    {
        $period = trim((string) $this->period);
        if ($period === '' || ! preg_match('/\d+/', $period, $m)) {
            return null;
        }

        $n = (int) $m[0];
        if ($n <= 0) {
            return null;
        }

        if (stripos($period, 'month') !== false) {
            return $base->copy()->addMonthsNoOverflow($n);
        }
        if (stripos($period, 'week') !== false) {
            return $base->copy()->addWeeks($n);
        }
        if (stripos($period, 'day') !== false) {
            return $base->copy()->addDays($n);
        }

        return $base->copy()->addYearsNoOverflow($n);
    }

    protected static function booted(): void
    {
        static::saving(function (Subscription $subscription) {
            // When both Previous Renewal Date and Period are set, the current
            // term ends at Previous Renewal Date + Period — so derive the Expire
            // Date from them (this then drives reminder_date and renewal_status).
            // Pay-as-you-go has no fixed term, so it is exempt.
            if ($subscription->renewal_type !== 'Pay as you go'
                && $subscription->previous_renewal_date && filled($subscription->period)) {
                $due = $subscription->addPeriodTo(Carbon::parse($subscription->previous_renewal_date));
                if ($due) {
                    $subscription->expire_date = $due;
                }
            }

            if ($subscription->expire_date) {
                $days = 30;
                try {
                    $settings = MailSetting::query()->first();
                    if ($settings && $settings->reminder_days_before) {
                        $days = (int) $settings->reminder_days_before;
                    }
                } catch (\Throwable $e) {
                    // mail_settings table may not exist yet during initial migrate
                }
                $subscription->reminder_date = Carbon::parse($subscription->expire_date)->subDays($days);
            }

            // Renewal Status is always derived from Status + Expire Date.
            $subscription->renewal_status = $subscription->computeRenewalStatus();
        });
    }
}
