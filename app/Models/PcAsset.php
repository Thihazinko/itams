<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * The assignment history — one row per period this PC was assigned to an
     * employee. The open row (released_at = null) is the current holder.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PcAssetAssignment::class)->orderByDesc('assigned_at');
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
