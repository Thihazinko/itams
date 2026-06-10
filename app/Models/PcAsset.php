<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PcAsset extends Model
{
    public const DEPARTMENTS = [
        'Admin', 'Finance', 'HR', 'IT Development', 'Contract',
        'Offshore', 'SST', 'BPO', 'Infra', 'Sale',
    ];

    protected $fillable = [
        'computer_id', 'hostname', 'employee_name', 'status', 'department',
        'location', 'brand', 'model', 'serial_number', 'cpu', 'ram', 'ssd',
        'hdd', 'display', 'operating_system', 'license_key', 'admin_password',
        'username', 'password', 'purchased_date', 'expire_date', 'expire_permanent',
        'warranty_period', 'remarks', 'modified_by',
    ];

    protected $casts = [
        'purchased_date' => 'date',
        'expire_date' => 'date',
        'expire_permanent' => 'boolean',
        'admin_password' => 'encrypted',
        'username' => 'encrypted',
        'password' => 'encrypted',
    ];

    /**
     * The warranty's end date, derived from the purchased date plus the
     * free-text warranty period (e.g. "3 years", "12 months", "90 days").
     * Returns null when either input is missing or the period can't be parsed.
     */
    public function getWarrantyEndDateAttribute(): ?Carbon
    {
        if (! $this->purchased_date || blank($this->warranty_period)) {
            return null;
        }

        if (! preg_match('/(\d+(?:\.\d+)?)\s*(year|yr|month|mo|week|wk|day)/i', $this->warranty_period, $m)) {
            return null;
        }

        $amount = (float) $m[1];
        $unit   = strtolower($m[2]);
        $end    = $this->purchased_date->copy();

        return match (true) {
            str_starts_with($unit, 'y') => $end->addMonths((int) round($amount * 12)),
            str_starts_with($unit, 'm') => $end->addMonths((int) round($amount)),
            str_starts_with($unit, 'w') => $end->addWeeks((int) round($amount)),
            default                     => $end->addDays((int) round($amount)),
        };
    }

    /**
     * The warranty status, computed from the warranty end date relative to
     * today: "Expired", "Expiring Soon" (within 30 days), or "In Warranty".
     * Returns "Unknown" when the end date can't be determined.
     */
    public function getWarrantyStatusAttribute(): string
    {
        $end = $this->warranty_end_date;

        if (! $end) {
            return 'Unknown';
        }

        $today = Carbon::today();

        return match (true) {
            $end->lt($today)                       => 'Expired',
            $end->lte($today->copy()->addDays(30)) => 'Expiring Soon',
            default                                => 'In Warranty',
        };
    }

    /**
     * The assignment history — one row per period this PC was assigned to an
     * employee. The open row (released_at = null) is the current holder.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PcAssetAssignment::class)->orderByDesc('assigned_at');
    }

    /**
     * Software installed on this PC, entered manually. One row per program.
     */
    public function software(): HasMany
    {
        return $this->hasMany(PcAssetSoftware::class)->orderBy('name');
    }

    /**
     * Maintain the assignment history automatically: open a record when a PC
     * is first assigned, and on every employee change close the previous
     * holder's record and open one for the new holder.
     */
    protected static function booted(): void
    {
        static::created(function (PcAsset $pc) {
            if (filled($pc->employee_name)) {
                $pc->assignments()->create([
                    'employee_name' => $pc->employee_name,
                    'department'    => $pc->department,
                    'assigned_at'   => $pc->freshTimestamp(),
                    'recorded_by'   => $pc->modified_by,
                ]);
            }
        });

        static::updated(function (PcAsset $pc) {
            if (! $pc->wasChanged('employee_name')) {
                return;
            }

            // Close the current open assignment (the previous holder).
            $pc->assignments()
                ->whereNull('released_at')
                ->update(['released_at' => $pc->freshTimestamp()]);

            // Open a new assignment when the PC is handed to someone.
            if (filled($pc->employee_name)) {
                $pc->assignments()->create([
                    'employee_name' => $pc->employee_name,
                    'department'    => $pc->department,
                    'assigned_at'   => $pc->freshTimestamp(),
                    'recorded_by'   => $pc->modified_by,
                ]);
            }
        });
    }
}
