<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmailAccount;
use App\Models\EmailAlias;
use Illuminate\Http\Request;

class EmailMasterController extends Controller
{
    /**
     * Tabbed landing page for Email Master. The active tab (gmail|email|alias)
     * drives which list and which "Recent Changes" feed are rendered.
     */
    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), ['gmail', 'email', 'alias'], true)
            ? $request->get('tab')
            : 'gmail';

        $search = trim((string) $request->get('search'));

        $statusFilter = in_array($request->get('status'), EmailAccount::STATUSES, true)
            ? $request->get('status')
            : null;

        $accounts = null;
        $aliases  = null;
        $typeCounts = ['total' => 0, 'active' => 0, 'inactive' => 0];

        if ($tab === 'alias') {
            $query = EmailAlias::with('members');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('main_email', 'like', "%{$search}%")
                      ->orWhereHas('members', fn ($m) => $m->where('address', 'like', "%{$search}%"));
                });
            }

            $aliases = $query->orderByDesc('id')->paginate(20)->withQueryString();

            $recentLogs = ActivityLog::where('subject_type', EmailAlias::class)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        } else {
            $type = $tab === 'gmail' ? 'Gmail' : 'Email';
            $query = EmailAccount::where('type', $type);

            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('department', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            $accounts = $query->orderByDesc('id')->paginate(20)->withQueryString();

            // KPI card counts for the active type (independent of status filter).
            $base = EmailAccount::where('type', $type);
            $typeCounts = [
                'total'    => (clone $base)->count(),
                'active'   => (clone $base)->where('status', 'Active')->count(),
                'inactive' => (clone $base)->where('status', 'Inactive')->count(),
            ];

            $recentLogs = ActivityLog::where('subject_type', EmailAccount::class)
                ->where('properties->type', $type)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        }

        $counts = [
            'gmail' => EmailAccount::where('type', 'Gmail')->count(),
            'email' => EmailAccount::where('type', 'Email')->count(),
            'alias' => EmailAlias::count(),
        ];

        return view('email_master.index', compact('tab', 'accounts', 'aliases', 'recentLogs', 'counts', 'search', 'statusFilter', 'typeCounts'));
    }
}
