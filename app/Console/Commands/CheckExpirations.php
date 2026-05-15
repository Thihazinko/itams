<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringMail;
use App\Models\MailSetting;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckExpirations extends Command
{
    protected $signature = 'app:check-expirations';

    protected $description = 'Check subscriptions expiring within the configured reminder window, update status, create notifications, and email recipients.';

    public function handle(): int
    {
        $settings = MailSetting::current();
        $daysBefore = max(1, (int) ($settings->reminder_days_before ?? 30));

        $today = Carbon::today();
        $threshold = $today->copy()->addDays($daysBefore);

        $expiring = Subscription::where('status', 'Active')
            ->where('renewal_status', '!=', 'Renewed')
            ->whereDate('expire_date', '<=', $threshold)
            ->whereDate('expire_date', '>=', $today)
            ->get();

        $expired = Subscription::where('status', 'Active')
            ->where('renewal_status', '!=', 'Renewed')
            ->whereDate('expire_date', '<', $today)
            ->get();

        foreach ($expired as $subscription) {
            $subscription->renewal_status = 'Expired';
            $subscription->saveQuietly();
        }
        $this->info("Marked {$expired->count()} subscriptions as Expired.");

        $recipients = $settings->recipientsArray();
        if (empty($recipients)) {
            $recipients = User::where('role', 'admin')->pluck('email')->filter()->values()->toArray();
        }
        $sentCount = 0;

        foreach ($expiring as $subscription) {
            if ($subscription->renewal_status !== 'Pending') {
                $subscription->renewal_status = 'Pending';
                $subscription->saveQuietly();
            }

            $daysRemaining = (int) Carbon::today()->diffInDays($subscription->expire_date, false);

            $alreadyNotifiedToday = Notification::where('subscription_id', $subscription->id)
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadyNotifiedToday) {
                continue;
            }

            Notification::create([
                'subscription_id' => $subscription->id,
                'title' => "Renewal Due: {$subscription->subscription_name}",
                'message' => "{$subscription->subscription_name} ({$subscription->service_type}) expires on {$subscription->expire_date->format('Y-m-d')} ({$daysRemaining} day(s) left).",
                'expire_date' => $subscription->expire_date,
                'days_remaining' => $daysRemaining,
            ]);

            if (! empty($recipients)) {
                try {
                    Mail::to($recipients)->send(new SubscriptionExpiringMail($subscription, $daysRemaining));
                    $sentCount++;
                } catch (\Throwable $e) {
                    $this->error("Failed to send mail for subscription #{$subscription->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Processed {$expiring->count()} expiring subscriptions; sent {$sentCount} email batches to " . count($recipients) . " recipient(s).");
        $this->info("Reminder window: {$daysBefore} day(s) before expiry.");

        return self::SUCCESS;
    }
}
