<?php

namespace App\Mail;

use App\Models\SubscriptionRenewal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SubscriptionRenewal $renewal,
        public string $quotationUrl,
        public string $pdfAbsolutePath,
        public string $approverStep = SubscriptionRenewal::APPROVER_FIRST,
        public ?string $recipientName = null,
        public ?string $recipientEmail = null,
    ) {
        $this->recipientName  ??= $approverStep === SubscriptionRenewal::APPROVER_SECOND
            ? $renewal->second_approver_name
            : $renewal->approver_name;
        $this->recipientEmail ??= $approverStep === SubscriptionRenewal::APPROVER_SECOND
            ? $renewal->second_approver_email
            : $renewal->approver_email;
    }

    public function envelope(): Envelope
    {
        $app = config('app.name', 'ITAMS');
        $stage = $this->approverStep === SubscriptionRenewal::APPROVER_SECOND
            ? '2nd approval'
            : '1st approval';
        return new Envelope(
            subject: "[{$app}] Purchase Order {$this->renewal->po_number} \u{2014} {$stage} needed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.po-request',
            with: [
                'renewal'        => $this->renewal,
                'quotationUrl'   => $this->quotationUrl,
                'approverStep'   => $this->approverStep,
                'recipientName'  => $this->recipientName,
                'recipientEmail' => $this->recipientEmail,
                'appName'        => config('app.name', 'ITAMS'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfAbsolutePath)
                ->as($this->renewal->po_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
