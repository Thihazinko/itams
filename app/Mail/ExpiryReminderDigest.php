<?php

namespace App\Mail;

use App\Models\LicenseContract;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Digest email for a single (module × day-mark) bucket — e.g.
 * "Subscriptions expiring in exactly 30 days".
 *
 * Sent by app:check-expirations once per day per matching bucket, so a
 * record set up with [30, 20, 10] receives at most three reminders before
 * it expires (one at each mark) instead of daily window spam.
 */
class ExpiryReminderDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $moduleKey,
        public string $moduleLabel,
        public int $daysAhead,
        public Collection $records,
    ) {
    }

    public function envelope(): Envelope
    {
        $app   = config('app.name', 'ITAMS');
        $count = $this->records->count();
        $plural = $count === 1 ? '' : 's';
        return new Envelope(
            subject: "[{$app}] Reminder: {$count} {$this->moduleLabel}{$plural} expire in {$this->daysAhead} day(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expiry-reminder-digest',
            with: [
                'moduleKey'   => $this->moduleKey,
                'moduleLabel' => $this->moduleLabel,
                'daysAhead'   => $this->daysAhead,
                'rows'        => $this->normalizedRows(),
                'count'       => $this->records->count(),
                'appName'     => config('app.name', 'ITAMS'),
            ],
        );
    }

    /**
     * Normalize subscription / license rows into a uniform shape for the view.
     * @return array<int, array<string,mixed>>
     */
    private function normalizedRows(): array
    {
        return $this->records->map(function ($r) {
            if ($r instanceof Subscription) {
                return [
                    'name'     => $r->subscription_name,
                    'detail'   => $r->service_type,
                    'vendor'   => $r->vendor_name,
                    'expires'  => $r->expire_date?->format('Y-m-d'),
                    'cost'     => $r->renewal_cost,
                    'currency' => $r->currency,
                ];
            }
            if ($r instanceof LicenseContract) {
                return [
                    'name'     => $r->software_name,
                    'detail'   => $r->license_info,
                    'vendor'   => $r->vendor_name,
                    'expires'  => $r->expire_date?->format('Y-m-d'),
                    'cost'     => $r->renewal_cost,
                    'currency' => $r->currency,
                ];
            }
            return [
                'name' => (string) ($r->name ?? '—'),
                'detail' => null, 'vendor' => null,
                'expires' => null, 'cost' => null, 'currency' => null,
            ];
        })->all();
    }
}
