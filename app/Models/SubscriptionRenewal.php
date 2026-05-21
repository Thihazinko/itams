<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SubscriptionRenewal extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_FIRST_APPROVED = 'first_approved';
    public const STATUS_PENDING_SECOND = 'pending_second_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_FINAL = 'final_confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const APPROVER_FIRST = 'first';
    public const APPROVER_SECOND = 'second';

    protected $fillable = [
        'subscription_id',
        'po_number', 'po_date', 'subject', 'reference',
        'vendor_company', 'vendor_name', 'vendor_phone_email',
        'approver_user_id', 'approver_name', 'approver_email',
        'second_approver_user_id', 'second_approver_name', 'second_approver_email',
        'quantity', 'unit_price', 'total_amount', 'currency',
        'notes', 'pdf_path', 'signed_token', 'second_signed_token',
        'status',
        'mailed_first_at', 'mailed_second_at',
        'approved_at', 'second_approved_at',
        'final_confirmed_at', 'final_confirmed_by',
        'rejected_at', 'rejected_reason',
        'created_by',
    ];

    protected $casts = [
        'po_date'              => 'date',
        'unit_price'           => 'decimal:2',
        'total_amount'         => 'decimal:2',
        'mailed_first_at'      => 'datetime',
        'mailed_second_at'     => 'datetime',
        'approved_at'          => 'datetime',
        'second_approved_at'   => 'datetime',
        'final_confirmed_at'   => 'datetime',
        'rejected_at'          => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function secondApproverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_approver_user_id');
    }

    public static function generatePoNumber(): string
    {
        do {
            $candidate = 'PO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (static::where('po_number', $candidate)->exists());

        return $candidate;
    }

    public static function generateSignedToken(): string
    {
        do {
            $candidate = bin2hex(random_bytes(24));
        } while (
            static::where('signed_token', $candidate)->exists()
            || static::where('second_signed_token', $candidate)->exists()
        );

        return $candidate;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFirstApproved(): bool
    {
        return $this->status === self::STATUS_FIRST_APPROVED;
    }

    public function isPendingSecond(): bool
    {
        return $this->status === self::STATUS_PENDING_SECOND;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_FINAL, self::STATUS_REJECTED, self::STATUS_CANCELLED], true);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING], true);
    }

    public function approverStepForToken(?string $token): ?string
    {
        if (! $token) {
            return null;
        }
        if ($this->signed_token && hash_equals($this->signed_token, $token)) {
            return self::APPROVER_FIRST;
        }
        if ($this->second_signed_token && hash_equals($this->second_signed_token, $token)) {
            return self::APPROVER_SECOND;
        }
        return null;
    }
}
