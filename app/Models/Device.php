<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    public const STATUSES = ['Active', 'Free', 'Damage', 'Retirement', 'Lost'];

    protected $fillable = [
        'item_name',
        'serial_number',
        'location',
        'qty',
        'status',
        'description',
        'vendor',
        'purchased_date',
        'warranty',
        'delivery_date',
        'delivery_location',
        'remark',
        'modified_by',
    ];

    protected $casts = [
        'qty'            => 'integer',
        'purchased_date' => 'date',
        'delivery_date'  => 'date',
    ];
}
