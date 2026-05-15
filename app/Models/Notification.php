<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'subscription_id', 'title', 'message', 'expire_date', 'days_remaining', 'read_at',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'read_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
