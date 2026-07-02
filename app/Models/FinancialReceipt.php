<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReceipt extends Model
{
    protected $fillable = [
        'financial_po_id', 'receipt_number', 'receipt_date', 'paid_amount',
        'currency', 'payment_method', 'file_path', 'notes',
        'created_by', 'modified_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'paid_amount'  => 'decimal:2',
    ];

    public function financialPo(): BelongsTo
    {
        return $this->belongsTo(FinancialPo::class);
    }
}
