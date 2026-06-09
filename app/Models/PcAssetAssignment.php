<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcAssetAssignment extends Model
{
    protected $fillable = [
        'pc_asset_id', 'employee_name', 'department',
        'assigned_at', 'released_at', 'released_reason', 'recorded_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function pcAsset(): BelongsTo
    {
        return $this->belongsTo(PcAsset::class);
    }
}
