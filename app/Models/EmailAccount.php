<?php

namespace App\Models;

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
        'password' => 'encrypted',
    ];
}
