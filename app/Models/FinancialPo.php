<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FinancialPo extends Model
{
    use SoftDeletes;

    public const CURRENCIES = [
        'MMK' => 'Myanmar (MMK)',
        'JPY' => 'Japan (JPY)',
        'USD' => 'USD',
    ];

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SUBSCRIPTION = 'subscription';
    public const SOURCE_SUBSCRIPTION_PAYG = 'subscription_payg';
    public const SOURCE_LICENSE = 'license_contract';

    // Display metadata for each source: [label, badge classes, icon].
    public const SOURCES = [
        self::SOURCE_MANUAL            => ['label' => 'One-Time Purchase', 'badge' => 'bg-success-subtle text-success-emphasis', 'icon' => 'bi-cart-plus'],
        self::SOURCE_SUBSCRIPTION      => ['label' => 'Subscription', 'badge' => 'bg-info-subtle text-info-emphasis', 'icon' => 'bi-arrow-repeat'],
        self::SOURCE_SUBSCRIPTION_PAYG => ['label' => 'Pay as you go', 'badge' => 'bg-primary-subtle text-primary-emphasis', 'icon' => 'bi-calendar-month'],
        self::SOURCE_LICENSE           => ['label' => 'License & Contract', 'badge' => 'bg-warning-subtle text-warning-emphasis', 'icon' => 'bi-file-earmark-text'],
    ];

    protected $fillable = [
        'po_number', 'po_date', 'subject', 'vendor_name', 'category',
        'total_amount', 'currency', 'source',
        'subscription_renewal_id', 'subscription_id', 'license_contract_id', 'billing_month',
        'notes', 'created_by', 'modified_by',
    ];

    protected $casts = [
        'po_date'       => 'date',
        'billing_month' => 'date',
        'total_amount'  => 'decimal:2',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(FinancialReceipt::class);
    }

    public function subscriptionRenewal(): BelongsTo
    {
        return $this->belongsTo(SubscriptionRenewal::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Pay-as-you-go POs are accrued monthly and, unlike other mirrored POs,
     * their Renewal Cost can be adjusted by hand to match actual usage.
     */
    public function isPayAsYouGo(): bool
    {
        return $this->source === self::SOURCE_SUBSCRIPTION_PAYG;
    }

    /**
     * One-time purchase orders entered by hand (e.g. a PC, UPS, hardware) — not
     * mirrored from any source, so they are fully editable and never synced.
     */
    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function licenseContract(): BelongsTo
    {
        return $this->belongsTo(LicenseContract::class);
    }

    public function sourceMeta(): array
    {
        return self::SOURCES[$this->source] ?? ['label' => ucfirst((string) $this->source), 'badge' => 'bg-secondary-subtle text-secondary-emphasis', 'icon' => 'bi-link-45deg'];
    }

    /**
     * Total paid against this PO (sum of its receipts), in the PO's currency.
     */
    public function receiptsTotal(): float
    {
        return (float) $this->receipts->sum('paid_amount');
    }

    /**
     * Remaining un-receipted amount on the PO (never negative below zero only
     * matters for display; we keep the raw difference so over-payment is visible).
     */
    public function remainingAmount(): float
    {
        return (float) $this->total_amount - $this->receiptsTotal();
    }

    public static function generatePoNumber(): string
    {
        do {
            $candidate = 'PO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (static::withTrashed()->where('po_number', $candidate)->exists());

        return $candidate;
    }
}
