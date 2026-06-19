<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmailAccount;
use App\Models\EmailAlias;
use App\Models\LicenseContract;
use App\Models\MailSetting;
use App\Models\PcAsset;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public const CATEGORIES = [
        'pc_master'        => ['label' => 'PC Master',          'subject_type' => PcAsset::class],
        'subscription'     => ['label' => 'Subscription',       'subject_type' => Subscription::class],
        'license_contract' => ['label' => 'License & Contract', 'subject_type' => LicenseContract::class],
        'email_master'     => ['label' => 'Email Master',       'subject_types' => [EmailAccount::class, EmailAlias::class]],
        'user'             => ['label' => 'User',               'subject_type' => User::class,        'exclude_actions' => ['login', 'logout', 'login_failed']],
        'mail_setting'     => ['label' => 'Mail Setting',       'subject_type' => MailSetting::class],
        'authentication'   => ['label' => 'Authentication',     'actions' => ['login', 'logout', 'login_failed']],
    ];

    public function index(Request $request)
    {
        $query = ActivityLog::query()->with('user');

        if ($category = $request->get('category')) {
            $cfg = self::CATEGORIES[$category] ?? null;
            if ($cfg) {
                $query->where(function ($q) use ($cfg) {
                    if (! empty($cfg['subject_type'])) {
                        $q->where('subject_type', $cfg['subject_type']);
                        if (! empty($cfg['exclude_actions'])) {
                            $q->whereNotIn('action', $cfg['exclude_actions']);
                        }
                    }
                    if (! empty($cfg['subject_types'])) {
                        $q->whereIn('subject_type', $cfg['subject_types']);
                    }
                    if (! empty($cfg['actions'])) {
                        $q->orWhereIn('action', $cfg['actions']);
                    }
                });
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::orderBy('name')->get(['id', 'name']);
        $categories = self::CATEGORIES;

        return view('activity_logs.index', compact('logs', 'actions', 'users', 'categories'));
    }
}
