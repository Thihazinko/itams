<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcAssetSoftware extends Model
{
    protected $table = 'pc_asset_software';

    protected $fillable = [
        'pc_asset_id', 'name', 'version', 'notes',
    ];

    public function pcAsset(): BelongsTo
    {
        return $this->belongsTo(PcAsset::class);
    }
}
