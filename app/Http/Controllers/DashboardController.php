<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\LicenseContract;
use App\Models\PcAsset;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $in30Days = $today->copy()->addDays(30);

        $expiringSubsCount = Subscription::where('status', 'Active')
            ->where('renewal_status', '!=', 'Renewed')
            ->whereBetween('expire_date', [$today, $in30Days])
            ->count();

        $expiringLicensesCount = LicenseContract::whereNotIn('status', ['Terminated', 'Expired'])
            ->whereBetween('expire_date', [$today, $in30Days])
            ->count();

        $stats = [
            'total_assets'         => PcAsset::count(),
            'active_assets'        => PcAsset::where('status', 'Active')->count(),
            'free_assets'          => PcAsset::where('status', 'Free')->count(),

            'total_devices'        => Device::count(),
            'devices_qty'          => (int) Device::sum('qty'),
            'active_devices'       => Device::where('status', 'Active')->count(),

            'total_subscriptions'  => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'Active')->count(),

            'total_licenses'       => LicenseContract::count(),
            'active_licenses'      => LicenseContract::where('status', 'Active')->count(),

            'expiring_subs'        => $expiringSubsCount,
            'expiring_licenses'    => $expiringLicensesCount,
            'expiring_total'       => $expiringSubsCount + $expiringLicensesCount,

            'expired_subs'         => Subscription::where('renewal_status', 'Expired')->count(),
        ];

        $assetStatusCounts = PcAsset::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $deviceStatusCounts = Device::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $expiringSoon = Subscription::where('status', 'Active')
            ->where('renewal_status', '!=', 'Renewed')
            ->whereBetween('expire_date', [$today, $in30Days])
            ->orderBy('expire_date')
            ->limit(6)
            ->get();

        $expiringLicenses = LicenseContract::whereNotIn('status', ['Terminated', 'Expired'])
            ->whereBetween('expire_date', [$today, $in30Days])
            ->orderBy('expire_date')
            ->limit(6)
            ->get();

        $recentActivity = ActivityLog::orderByDesc('created_at')->limit(8)->get();

        return view('dashboard', compact(
            'stats',
            'assetStatusCounts',
            'deviceStatusCounts',
            'expiringSoon',
            'expiringLicenses',
            'recentActivity',
        ));
    }
}
