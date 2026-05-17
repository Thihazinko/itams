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

    public const ALLOWED_DAYS = [10, 20, 30];

    protected $fillable = [
        'module', 'enabled', 'days_before_set', 'recipients',
    ];

    protected $casts = [
        'enabled'         => 'boolean',
        'days_before_set' => 'array',
    ];

    public static function forModule(string $module): self
    {
        return static::firstOrCreate(
            ['module' => $module],
            ['enabled' => false, 'days_before_set' => [30]],
        );
    }

    /**
     * The widest selected window. Drives "anything within N days" gates for the
     * notifications page and the topbar badge. Defaults to 30 if nothing valid is set.
     */
    public function windowDays(): int
    {
        $set = $this->days_before_set ?: [30];
        $set = array_filter(array_map('intval', $set), fn ($v) => $v > 0);
        return empty($set) ? 30 : max($set);
    }

    /**
     * Selected windows, sanitized to integers in descending order. Useful for UI.
     * @return int[]
     */
    public function selectedDays(): array
    {
        $set = array_filter(array_map('intval', $this->days_before_set ?? []), fn ($v) => $v > 0);
        rsort($set);
        return array_values(array_unique($set));
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
