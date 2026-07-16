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
        'financial_management' => 'Financial Management',
        'gcp_costs'          => 'GCP Cost Breakdown',
        'task_daily'         => 'Daily Task',
        'task_management'    => 'Task Management',
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
        'can_view_financial_management',
        'can_edit_financial_management',
        'can_view_gcp_costs',
        'can_edit_gcp_costs',
        'can_view_task_management',
        'can_edit_task_management',
        'can_view_task_daily',
        'can_edit_task_daily',
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
            'can_view_financial_management' => 'boolean',
            'can_edit_financial_management' => 'boolean',
            'can_view_gcp_costs' => 'boolean',
            'can_edit_gcp_costs' => 'boolean',
            'can_view_task_management' => 'boolean',
            'can_edit_task_management' => 'boolean',
            'can_view_task_daily' => 'boolean',
            'can_edit_task_daily' => 'boolean',
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

        if ((bool) $this->{"can_view_{$module}"} || (bool) $this->{"can_edit_{$module}"}) {
            return true;
        }

        // Full Task Management access implies Daily Task access.
        if ($module === 'task_daily') {
            return (bool) $this->can_view_task_management || (bool) $this->can_edit_task_management;
        }

        return false;
    }

    public function canEdit(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! array_key_exists($module, self::MODULES)) {
            return false;
        }

        if ((bool) $this->{"can_edit_{$module}"}) {
            return true;
        }

        // Full Task Management edit access implies Daily Task edit access.
        if ($module === 'task_daily') {
            return (bool) $this->can_edit_task_management;
        }

        return false;
    }

    public function canAccess(string $module, string $action = 'view'): bool
    {
        return $action === 'edit' ? $this->canEdit($module) : $this->canView($module);
    }

    /**
     * Task Management "members": the users who log man-hours on the daily sheet.
     * A user qualifies once granted Daily Task access — or full Task Management,
     * which implies it. Admins are included only when a flag is set on their own
     * account, since their role otherwise grants access without ticking the box.
     */
    public function scopeTaskMembers($query)
    {
        return $query->where(function ($q) {
            $q->where('can_view_task_daily', true)
                ->orWhere('can_edit_task_daily', true)
                ->orWhere('can_view_task_management', true)
                ->orWhere('can_edit_task_management', true);
        })->orderBy('name');
    }
}
