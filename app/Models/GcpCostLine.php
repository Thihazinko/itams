<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GcpCostLine extends Model
{
    protected $fillable = [
        'gcp_cost_breakdown_id', 'sort_order', 'account_type', 'project_name', 'usage',
        'billing_account_name', 'project_id', 'usage_start_date', 'usage_end_date',
        'billing_card', 'card_setting', 'cost_jpy', 'cost_usd', 'status',
    ];

    protected $casts = [
        'usage_start_date' => 'date',
        'usage_end_date'   => 'date',
        'cost_jpy'         => 'decimal:6',
        'cost_usd'         => 'decimal:6',
    ];

    public function breakdown(): BelongsTo
    {
        return $this->belongsTo(GcpCostBreakdown::class, 'gcp_cost_breakdown_id');
    }
}
