<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    public const MODULES = [
        'pc_assets'          => 'PC Master',
        'devices'            => 'Device Master',
        'subscriptions'      => 'Subscriptions',
        'licenses_contracts' => 'License & Contract',
    ];

    protected $fillable = [
        'module', 'enabled', 'days_before', 'recipients',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        'days_before' => 'integer',
    ];

    public static function forModule(string $module): self
    {
        return static::firstOrCreate(
            ['module' => $module],
            ['enabled' => false, 'days_before' => 30],
        );
    }

    public function recipientsArray(): array
    {
        if (! $this->recipients) {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', $this->recipients))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();
    }
}
