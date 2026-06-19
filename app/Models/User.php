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
        'email_master'       => 'Email Master',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'can_view_pc_assets',
        'can_edit_pc_assets',
        'can_view_subscriptions',
        'can_edit_subscriptions',
        'can_view_licenses_contracts',
        'can_edit_licenses_contracts',
        'can_view_devices',
        'can_edit_devices',
        'can_view_email_master',
        'can_edit_email_master',
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
            'can_view_pc_assets' => 'boolean',
            'can_edit_pc_assets' => 'boolean',
            'can_view_subscriptions' => 'boolean',
            'can_edit_subscriptions' => 'boolean',
            'can_view_licenses_contracts' => 'boolean',
            'can_edit_licenses_contracts' => 'boolean',
            'can_view_devices' => 'boolean',
            'can_edit_devices' => 'boolean',
            'can_view_email_master' => 'boolean',
            'can_edit_email_master' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canView(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! array_key_exists($module, self::MODULES)) {
            return false;
        }

        return (bool) $this->{"can_view_{$module}"} || (bool) $this->{"can_edit_{$module}"};
    }

    public function canEdit(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! array_key_exists($module, self::MODULES)) {
            return false;
        }

        return (bool) $this->{"can_edit_{$module}"};
    }

    public function canAccess(string $module, string $action = 'view'): bool
    {
        return $action === 'edit' ? $this->canEdit($module) : $this->canView($module);
    }
}
