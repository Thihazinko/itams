<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'mailer', 'host', 'port', 'encryption', 'auth_mode', 'username',
        'password', 'from_address', 'from_name', 'enabled',
        'reminder_recipients', 'reminder_days_before',
    ];

    protected $casts = [
        'password' => 'encrypted',
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
