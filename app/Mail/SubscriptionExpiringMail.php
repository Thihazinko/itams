<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription, public int $daysRemaining)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[ITAMS] Renewal Reminder: {$this->subscription->subscription_name} expires in {$this->daysRemaining} day(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-expiring',
            with: [
                'subscription' => $this->subscription,
                'daysRemaining' => $this->daysRemaining,
            ],
        );
    }
}
