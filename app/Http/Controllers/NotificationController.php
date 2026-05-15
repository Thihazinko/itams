<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with('subscription');

        $status = $request->get('status');
        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $kpis = [
            'total'  => Notification::count(),
            'unread' => Notification::whereNull('read_at')->count(),
            'read'   => Notification::whereNotNull('read_at')->count(),
        ];

        return view('notifications.index', compact('notifications', 'kpis'));
    }

    public function markRead(Notification $notification)
    {
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }

    public function markAllRead()
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
