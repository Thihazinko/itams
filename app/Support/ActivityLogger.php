<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Record an activity log entry.
     *
     * @param  string       $action       e.g. login, logout, login_failed, created, updated, deleted, imported, renewed
     * @param  string       $description  Human-readable description shown in the change log table
     * @param  Model|null   $subject      Optional model the action targeted (used to capture type & id)
     * @param  array        $properties   Optional context payload (e.g. ['old' => [...], 'new' => [...]])
     * @param  array        $overrides    Override user_id/user_name/user_email (used for failed logins where Auth is empty)
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        array $overrides = []
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id'      => $overrides['user_id']    ?? ($user?->id),
            'user_name'    => $overrides['user_name']  ?? ($user?->name),
            'user_email'   => $overrides['user_email'] ?? ($user?->email),
            'action'       => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'ip_address'   => Request::ip(),
            'user_agent'   => substr((string) Request::userAgent(), 0, 500),
            'properties'   => $properties ?: null,
        ]);
    }
}
