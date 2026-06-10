<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LicenseContractController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PcAssetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionRenewalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

// Public signed quotation access (for external approvers without a login)
Route::get('quotations/{renewal}/sign/{token}', [SubscriptionRenewalController::class, 'showSigned'])
    ->name('subscriptions.renewals.show.signed');
Route::post('quotations/{renewal}/approve', [SubscriptionRenewalController::class, 'approve'])
    ->name('subscriptions.renewals.approve');
Route::post('quotations/{renewal}/reject', [SubscriptionRenewalController::class, 'reject'])
    ->name('subscriptions.renewals.reject');
Route::get('quotations/{renewal}/pdf', [SubscriptionRenewalController::class, 'downloadPdf'])
    ->name('subscriptions.renewals.pdf');

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
    Route::middleware('module:pc_assets,edit')->group(function () {
        Route::post('pc-assets/import', [PcAssetController::class, 'import'])->name('pc-assets.import');
        Route::delete('pc-assets/bulk', [PcAssetController::class, 'bulkDestroy'])->name('pc-assets.bulk-destroy');
        Route::resource('pc-assets', PcAssetController::class)->except(['index', 'show']);
    });
    Route::middleware('module:pc_assets,view')->group(function () {
        Route::get('pc-assets/export', [PcAssetController::class, 'export'])->name('pc-assets.export');
        Route::get('pc-assets/template', [PcAssetController::class, 'template'])->name('pc-assets.template');
        Route::resource('pc-assets', PcAssetController::class)->only(['index', 'show']);
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
        Route::post('subscriptions/{subscription}/renewals', [SubscriptionRenewalController::class, 'store'])->name('subscriptions.renewals.store');
        Route::post('subscriptions/renewals/{renewal}/final-confirm', [SubscriptionRenewalController::class, 'finalConfirm'])->name('subscriptions.renewals.final-confirm');
        Route::post('subscriptions/renewals/{renewal}/cancel', [SubscriptionRenewalController::class, 'cancel'])->name('subscriptions.renewals.cancel');
        Route::resource('subscriptions', SubscriptionController::class)->except(['index', 'show']);
    });
    Route::middleware('module:subscriptions,view')->group(function () {
        Route::get('subscriptions/renewals/{renewal}', [SubscriptionRenewalController::class, 'show'])->name('subscriptions.renewals.show');
        // Numeric constraint so this doesn't shadow subscriptions/create, /export, /template.
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])
            ->whereNumber('subscription')
            ->name('subscriptions.show');
    });

    // Licenses & Contracts
    Route::middleware('module:licenses_contracts,view')->group(function () {
        Route::get('licenses-contracts/export', [LicenseContractController::class, 'export'])->name('licenses-contracts.export');
        Route::get('licenses-contracts/template', [LicenseContractController::class, 'template'])->name('licenses-contracts.template');
        Route::resource('licenses-contracts', LicenseContractController::class)
            ->parameters(['licenses-contracts' => 'licenses_contract'])
            ->only(['index', 'show'])
            ->where(['licenses_contract' => '[0-9]+']);
    });
    Route::middleware('module:licenses_contracts,edit')->group(function () {
        Route::post('licenses-contracts/import', [LicenseContractController::class, 'import'])->name('licenses-contracts.import');
        Route::delete('licenses-contracts/bulk', [LicenseContractController::class, 'bulkDestroy'])->name('licenses-contracts.bulk-destroy');
        Route::resource('licenses-contracts', LicenseContractController::class)
            ->parameters(['licenses-contracts' => 'licenses_contract'])
            ->except(['index', 'show']);
    });

    // Devices
    Route::middleware('module:devices,edit')->group(function () {
        Route::post('devices/import', [DeviceController::class, 'import'])->name('devices.import');
        Route::delete('devices/bulk', [DeviceController::class, 'bulkDestroy'])->name('devices.bulk-destroy');
        Route::resource('devices', DeviceController::class)->except(['index', 'show']);
    });
    Route::middleware('module:devices,view')->group(function () {
        Route::get('devices/export', [DeviceController::class, 'export'])->name('devices.export');
        Route::get('devices/template', [DeviceController::class, 'template'])->name('devices.template');
        Route::resource('devices', DeviceController::class)->only(['index', 'show']);
    });

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{module}/{id}/read', [NotificationController::class, 'markRead'])
        ->where(['module' => 'subscriptions|licenses_contracts', 'id' => '[0-9]+'])
        ->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Self-service profile (any authenticated user — admin or not)
    Route::get('profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile',  [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('admin')->group(function () {
        Route::get('purchase-orders', [SubscriptionRenewalController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/{renewal}/edit', [SubscriptionRenewalController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('purchase-orders/{renewal}', [SubscriptionRenewalController::class, 'update'])->name('purchase-orders.update');
        Route::post('purchase-orders/{renewal}/send-first', [SubscriptionRenewalController::class, 'sendFirstMail'])->name('purchase-orders.send-first');
        Route::post('purchase-orders/{renewal}/send-second', [SubscriptionRenewalController::class, 'sendSecondMail'])->name('purchase-orders.send-second');

        Route::resource('users', UserController::class)->except(['show']);

        Route::get('mail-settings', [\App\Http\Controllers\MailSettingController::class, 'edit'])->name('mail-settings.edit');
        Route::put('mail-settings', [\App\Http\Controllers\MailSettingController::class, 'update'])->name('mail-settings.update');
        Route::post('mail-settings/test', [\App\Http\Controllers\MailSettingController::class, 'sendTest'])->name('mail-settings.test');

        Route::get('notification-settings', [\App\Http\Controllers\NotificationSettingController::class, 'edit'])->name('notification-settings.edit');
        Route::put('notification-settings/{module}', [\App\Http\Controllers\NotificationSettingController::class, 'update'])->name('notification-settings.update');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});
