<?php

namespace App\Models;

use App\Models\MailSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
