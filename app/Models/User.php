<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const MODULES = [
        'pc_assets'          => 'PC Master',
        'subscriptions'      => 'Subscriptions',
        'licenses_contracts' => 'License & Contract',
        'devices'            => 'Device Management',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'can_pc_assets',
        'can_subscriptions',
        'can_licenses_contracts',
        'can_devices',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_pc_assets' => 'boolean',
            'can_subscriptions' => 'boolean',
            'can_licenses_contracts' => 'boolean',
            'can_devices' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAccess(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return match ($module) {
            'pc_assets'          => (bool) $this->can_pc_assets,
            'subscriptions'      => (bool) $this->can_subscriptions,
            'licenses_contracts' => (bool) $this->can_licenses_contracts,
            'devices'            => (bool) $this->can_devices,
            default              => false,
        };
    }
}
