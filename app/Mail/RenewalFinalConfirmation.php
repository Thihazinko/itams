<?php

namespace App\Mail;

use App\Models\SubscriptionRenewal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenewalFinalConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SubscriptionRenewal $renewal,
        public ?string $pdfAbsolutePath = null,
    ) {}

    public function envelope(): Envelope
    {
        $app = config('app.name', 'ITAMS');
        $name = $this->renewal->subscription->subscription_name;
        return new Envelope(
            subject: "[{$app}] Renewal confirmed: {$name} (P.O. {$this->renewal->po_number})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.renewal-final',
            with: [
                'renewal' => $this->renewal,
                'appName' => config('app.name', 'ITAMS'),
            ],
        );
    }

    public function attachments(): array
    {
        if ($this->pdfAbsolutePath && file_exists($this->pdfAbsolutePath)) {
            return [
                Attachment::fromPath($this->pdfAbsolutePath)
                    ->as($this->renewal->po_number . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
