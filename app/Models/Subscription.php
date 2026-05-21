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
        'period', 'previous_cost', 'expire_date', 'renewal_cost', 'currency',
        'renewal_type', 'reminder_date', 'renewal_status', 'remarks', 'modified_by',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'reminder_date' => 'date',
        'previous_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
    ];

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

    protected static function booted(): void
    {
        static::saving(function (Subscription $subscription) {
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
        });
    }
}
