<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAliasMember extends Model
{
    protected $fillable = [
        'email_alias_id',
        'address',
    ];

    public function alias(): BelongsTo
    {
        return $this->belongsTo(EmailAlias::class, 'email_alias_id');
    }
}
