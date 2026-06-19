<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAlias extends Model
{
    protected $fillable = [
        'main_email',
        'remark',
        'modified_by',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(EmailAliasMember::class);
    }
}
