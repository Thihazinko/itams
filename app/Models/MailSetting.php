<?php

namespace App\Models;

use App\Casts\SafeEncrypted;
use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'mailer', 'host', 'port', 'encryption', 'auth_mode', 'username',
        'password', 'from_address', 'from_name', 'enabled',
        'reminder_recipients', 'reminder_days_before',
    ];

    protected $casts = [
        // Returns null rather than throwing when the stored value can't be
        // decrypted with the current APP_KEY (see App\Casts\SafeEncrypted).
        'password' => SafeEncrypted::class,
        'enabled' => 'boolean',
        'port' => 'integer',
        'reminder_days_before' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'mailer' => 'smtp',
            'port' => 587,
            'enabled' => false,
            'reminder_days_before' => 30,
        ]);
    }

    public function recipientsArray(): array
    {
        if (! $this->reminder_recipients) {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', $this->reminder_recipients))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();
    }
}
