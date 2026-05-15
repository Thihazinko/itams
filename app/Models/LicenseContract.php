<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseContract extends Model
{
    protected $table = 'licenses_contracts';

    public const CURRENCIES = [
        'MMK' => 'Myanmar (MMK)',
        'JPY' => 'Japan (JPY)',
        'USD' => 'USD',
    ];

    protected $fillable = [
        'software_name', 'status', 'renewal_type', 'license_info',
        'last_renewal_date', 'expire_date', 'vendor_name',
        'previous_cost', 'renewal_cost', 'currency',
        'remarks', 'modified_by',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'last_renewal_date' => 'date',
        'previous_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
    ];
}
