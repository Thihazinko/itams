<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl,
        public int $expireMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        $app = config('app.name', 'ITAMS');
        return new Envelope(
            subject: "[{$app}] Password reset request",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'user'          => $this->user,
                'resetUrl'      => $this->resetUrl,
                'expireMinutes' => $this->expireMinutes,
                'appName'       => config('app.name', 'ITAMS'),
            ],
        );
    }
}
