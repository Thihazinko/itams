<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GcpCostBreakdown extends Model
{
    protected $fillable = [
        'period_start', 'period_end', 'billing_account_name', 'reported_by',
        'exchange_rate', 'discount_amount', 'tax_amount', 'notes', 'created_by', 'modified_by',
    ];

    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'exchange_rate'   => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'tax_amount'      => 'decimal:6',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(GcpCostLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Distinct, non-empty account types used across this breakdown's lines. */
    public function accountTypes(): array
    {
        return $this->lines->pluck('account_type')->filter()->unique()->values()->all();
    }

    /**
     * Currency label for this breakdown, mirroring the index USD/JPY tabs: JPY
     * when a line carries a yen cost, USD when a line is billed only in dollars.
     */
    public function currencyLabel(): string
    {
        $hasJpy = $this->lines->contains(fn ($l) => $l->cost_jpy !== null);
        $hasUsd = $this->lines->contains(fn ($l) => $l->cost_usd !== null && $l->cost_jpy === null);

        return match (true) {
            $hasJpy && $hasUsd => 'JPY & USD',
            $hasUsd            => 'USD',
            default            => 'JPY',
        };
    }

    /** Sum of all line costs in JPY. */
    public function totalCostJpy(): float
    {
        return (float) $this->lines->sum('cost_jpy');
    }

    /** Sum of all line costs in USD. */
    public function totalCostUsd(): float
    {
        return (float) $this->lines->sum('cost_usd');
    }

    /** Whether a manual discount and/or tax amount has been set on this breakdown. */
    public function hasAdjustments(): bool
    {
        return (float) ($this->discount_amount ?? 0) != 0.0
            || (float) ($this->tax_amount ?? 0) != 0.0;
    }

    /** Subtotal after subtracting the manual discount and adding the manual tax. */
    public function grandTotal(float $subtotal): float
    {
        return $subtotal - (float) ($this->discount_amount ?? 0) + (float) ($this->tax_amount ?? 0);
    }

    /** Grand total (post discount + tax) for each currency. */
    public function grandTotalJpy(): float
    {
        return $this->grandTotal($this->totalCostJpy());
    }

    public function grandTotalUsd(): float
    {
        return $this->grandTotal($this->totalCostUsd());
    }

    /** Short "May 2026" style label for the billing period (end month). */
    public function periodLabel(): string
    {
        if ($this->period_end) {
            return Carbon::parse($this->period_end)->format('M Y');
        }
        if ($this->period_start) {
            return Carbon::parse($this->period_start)->format('M Y');
        }

        return '—';
    }

    /** Full "1 May 2026 – 31 May 2026" range for the header. */
    public function periodRange(): string
    {
        $start = $this->period_start ? Carbon::parse($this->period_start)->format('j M Y') : null;
        $end   = $this->period_end ? Carbon::parse($this->period_end)->format('j M Y') : null;

        return match (true) {
            $start && $end => "{$start} – {$end}",
            (bool) $start  => $start,
            (bool) $end    => $end,
            default        => '—',
        };
    }
}
