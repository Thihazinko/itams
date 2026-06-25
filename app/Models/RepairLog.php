<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairLog extends Model
{
    public const STATUSES = ['In Progress', 'Completed'];

    protected $fillable = [
        'pc_asset_id',
        'computer_id',
        'repair_date',
        'employee_name',
        'department',
        'repair_process',
        'status',
        'remark',
        'modified_by',
    ];

    protected $casts = [
        'repair_date' => 'date',
    ];

    /**
     * The PC this repair log refers to. May be null if the PC was deleted
     * after the log was recorded (computer_id keeps a readable snapshot).
     */
    public function pcAsset(): BelongsTo
    {
        return $this->belongsTo(PcAsset::class);
    }
}
