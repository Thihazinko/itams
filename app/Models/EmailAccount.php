<?php

namespace App\Models;

use App\Casts\SafeEncrypted;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    public const TYPES = ['Gmail', 'Email'];

    public const STATUSES = ['Active', 'Inactive'];

    protected $fillable = [
        'type',
        'status',
        'name',
        'department',
        'address',
        'username',
        'password',
        'remark',
        'modified_by',
    ];

    protected $casts = [
        // Transparently encrypt/decrypt the stored credential. Anyone with the
        // app key can read it; the goal is to keep plaintext out of the DB.
        // SafeEncrypted matches the built-in "encrypted" cast but returns null
        // instead of throwing when a value can't be decrypted with the current
        // APP_KEY, so listing/export won't 500 on an undecryptable row.
        'password' => SafeEncrypted::class,
    ];
}
