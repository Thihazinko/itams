<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GcpCostReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $pdfData,
        public string $fileName,
        public string $currency,
        public string $periodLabel,
        public int $count,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gcp-cost-report',
            with: [
                'currency'    => $this->currency,
                'periodLabel' => $this->periodLabel,
                'count'       => $this->count,
                'appName'     => config('app.name', 'ITAMS'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfData, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }
}
