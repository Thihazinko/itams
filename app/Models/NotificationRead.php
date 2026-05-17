<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    protected $fillable = [
        'user_id', 'module', 'notifiable_id', 'read_signature', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Compute the signature used to decide whether a stored read is still
     * valid for the current state of a record. Whenever the signature for
     * the live record differs from what we stored at read-time, the item
     * re-surfaces as unread.
     */
    public static function signature(CarbonInterface $expireDate, int $daysRemaining): string
    {
        $bucket = $daysRemaining < 0
            ? 'overdue'
            : ($daysRemaining <= 7 ? 'soon' : 'upcoming');

        return $expireDate->format('Y-m-d') . '|' . $bucket;
    }
}
