<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class LicenseContract extends Model
{
    protected $table = 'licenses_contracts';

    public const CURRENCIES = [
        'MMK' => 'Myanmar (MMK)',
        'JPY' => 'Japan (JPY)',
        'USD' => 'USD',
    ];

    protected $fillable = [
        'software_name', 'status', 'renewal_type', 'license_info',
        'last_renewal_date', 'start_using_date', 'expire_date', 'expire_permanent', 'vendor_name',
        'previous_cost', 'renewal_cost', 'currency',
        'remarks', 'modified_by',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'expire_permanent' => 'boolean',
        'last_renewal_date' => 'date',
        'start_using_date' => 'date',
        'previous_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
    ];

    /**
     * Uploaded documents for this license/contract — the signed contract,
     * invoice, renewal quotes, etc. Newest first.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(LicenseContractAttachment::class)->latest();
    }

    /**
     * How long this license/contract has been in use, from the start using date
     * up to today — but capped at the expire date, since an expired license is
     * no longer in use. E.g. "2 years 3 months", "1 month", "12 days".
     * Returns null when there's no start date or it is in the future.
     */
    public function getUsageDurationAttribute(): ?string
    {
        $start = $this->start_using_date;
        $today = Carbon::today();

        if (! $start || $start->gt($today)) {
            return null;
        }

        // Usage stops at expiry: a non-permanent license that has already
        // expired hasn't been in use since its expire date, so end the span
        // there rather than letting it keep counting up to today.
        $end = (! $this->expire_permanent && $this->expire_date && $this->expire_date->lt($today))
            ? $this->expire_date
            : $today;

        // Guard against a start date that falls after the (capped) end.
        if ($start->gt($end)) {
            return null;
        }

        $diff = $start->diff($end);

        $parts = [];
        if ($diff->y) {
            $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m) {
            $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }
        // Show days only for short spans (under a month) to keep it concise.
        if (! $diff->y && ! $diff->m) {
            $parts[] = $diff->d . ' day' . ($diff->d === 1 ? '' : 's');
        }

        return implode(' ', $parts);
    }
}
