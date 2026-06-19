<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoryLabel(): ?string
    {
        if (in_array($this->action, ['login', 'logout', 'login_failed'], true)) {
            return 'Authentication';
        }

        return match ($this->subject_type) {
            PcAsset::class         => 'PC Master',
            Subscription::class    => 'Subscription',
            LicenseContract::class => 'License & Contract',
            EmailAccount::class    => 'Email Master',
            EmailAlias::class      => 'Email Master',
            User::class            => 'User',
            MailSetting::class     => 'Mail Setting',
            default                => $this->subject_type ? class_basename($this->subject_type) : null,
        };
    }
}
