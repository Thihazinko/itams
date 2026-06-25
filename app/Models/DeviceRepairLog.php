<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceRepairLog extends Model
{
    public const STATUSES = ['In Progress', 'Completed'];

    protected $fillable = [
        'device_id',
        'device_label',
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
     * The device this repair log refers to. May be null if the device was
     * deleted after the log was recorded (device_label keeps a snapshot).
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
