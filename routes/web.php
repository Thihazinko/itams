<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LicenseContractController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PcAssetController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PC Master
    Route::middleware('module:pc_assets,view')->group(function () {
        Route::get('pc-assets/export', [PcAssetController::class, 'export'])->name('pc-assets.export');
        Route::get('pc-assets/template', [PcAssetController::class, 'template'])->name('pc-assets.template');
        Route::resource('pc-assets', PcAssetController::class)->only(['index', 'show']);
    });
    Route::middleware('module:pc_assets,edit')->group(function () {
        Route::post('pc-assets/import', [PcAssetController::class, 'import'])->name('pc-assets.import');
        Route::delete('pc-assets/bulk', [PcAssetController::class, 'bulkDestroy'])->name('pc-assets.bulk-destroy');
        Route::resource('pc-assets', PcAssetController::class)->except(['index', 'show']);
    });

    // Subscriptions
    Route::middleware('module:subscriptions,view')->group(function () {
        Route::get('subscriptions/export', [SubscriptionController::class, 'export'])->name('subscriptions.export');
        Route::get('subscriptions/template', [SubscriptionController::class, 'template'])->name('subscriptions.template');
        Route::resource('subscriptions', SubscriptionController::class)->only(['index']);
    });
    Route::middleware('module:subscriptions,edit')->group(function () {
        Route::post('subscriptions/import', [SubscriptionController::class, 'import'])->name('subscriptions.import');
        Route::delete('subscriptions/bulk', [SubscriptionController::class, 'bulkDestroy'])->name('subscriptions.bulk-destroy');
        Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'markRenewed'])->name('subscriptions.renew');
        Route::resource('subscriptions', SubscriptionController::class)->except(['index', 'show']);
    });

    // Licenses & Contracts
    Route::middleware('module:licenses_contracts,view')->group(function () {
        Route::get('licenses-contracts/export', [LicenseContractController::class, 'export'])->name('licenses-contracts.export');
        Route::get('licenses-contracts/template', [LicenseContractController::class, 'template'])->name('licenses-contracts.template');
        Route::resource('licenses-contracts', LicenseContractController::class)
            ->parameters(['licenses-contracts' => 'licenses_contract'])
            ->only(['index']);
    });
    Route::middleware('module:licenses_contracts,edit')->group(function () {
        Route::post('licenses-contracts/import', [LicenseContractController::class, 'import'])->name('licenses-contracts.import');
        Route::delete('licenses-contracts/bulk', [LicenseContractController::class, 'bulkDestroy'])->name('licenses-contracts.bulk-destroy');
        Route::resource('licenses-contracts', LicenseContractController::class)
            ->parameters(['licenses-contracts' => 'licenses_contract'])
            ->except(['index', 'show']);
    });

    // Devices
    Route::middleware('module:devices,view')->group(function () {
        Route::get('devices/export', [DeviceController::class, 'export'])->name('devices.export');
        Route::get('devices/template', [DeviceController::class, 'template'])->name('devices.template');
        Route::resource('devices', DeviceController::class)->only(['index', 'show']);
    });
    Route::middleware('module:devices,edit')->group(function () {
        Route::post('devices/import', [DeviceController::class, 'import'])->name('devices.import');
        Route::delete('devices/bulk', [DeviceController::class, 'bulkDestroy'])->name('devices.bulk-destroy');
        Route::resource('devices', DeviceController::class)->except(['index', 'show']);
    });

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{module}/{id}/read', [NotificationController::class, 'markRead'])
        ->where(['module' => 'subscriptions|licenses_contracts', 'id' => '[0-9]+'])
        ->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('mail-settings', [\App\Http\Controllers\MailSettingController::class, 'edit'])->name('mail-settings.edit');
        Route::put('mail-settings', [\App\Http\Controllers\MailSettingController::class, 'update'])->name('mail-settings.update');
        Route::post('mail-settings/test', [\App\Http\Controllers\MailSettingController::class, 'sendTest'])->name('mail-settings.test');

        Route::get('notification-settings', [\App\Http\Controllers\NotificationSettingController::class, 'edit'])->name('notification-settings.edit');
        Route::put('notification-settings/{module}', [\App\Http\Controllers\NotificationSettingController::class, 'update'])->name('notification-settings.update');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});
