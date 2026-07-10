<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceRepairLogController;
use App\Http\Controllers\EmailAccountController;
use App\Http\Controllers\EmailAliasController;
use App\Http\Controllers\EmailMasterController;
use App\Http\Controllers\FinancialPoController;
use App\Http\Controllers\FinancialReceiptController;
use App\Http\Controllers\GcpCostBreakdownController;
use App\Http\Controllers\LicenseContractController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PcAssetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairLogController;
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

    // PC Master — Repair Logs tab (shares the pc_assets module permission)
    Route::middleware('module:pc_assets,edit')->group(function () {
        Route::post('repair-logs/import', [RepairLogController::class, 'import'])->name('repair-logs.import');
        Route::delete('repair-logs/bulk', [RepairLogController::class, 'bulkDestroy'])->name('repair-logs.bulk-destroy');
        Route::resource('repair-logs', RepairLogController::class)->except(['index', 'show']);
    });
    Route::middleware('module:pc_assets,view')->group(function () {
        Route::get('repair-logs/export', [RepairLogController::class, 'export'])->name('repair-logs.export');
        Route::get('repair-logs/template', [RepairLogController::class, 'template'])->name('repair-logs.template');
        Route::resource('repair-logs', RepairLogController::class)->only(['index']);
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

    // Device Master — Repair Logs tab (shares the devices module permission)
    Route::middleware('module:devices,edit')->group(function () {
        Route::post('device-repair-logs/import', [DeviceRepairLogController::class, 'import'])->name('device-repair-logs.import');
        Route::delete('device-repair-logs/bulk', [DeviceRepairLogController::class, 'bulkDestroy'])->name('device-repair-logs.bulk-destroy');
        Route::resource('device-repair-logs', DeviceRepairLogController::class)->except(['index', 'show']);
    });
    Route::middleware('module:devices,view')->group(function () {
        Route::get('device-repair-logs/export', [DeviceRepairLogController::class, 'export'])->name('device-repair-logs.export');
        Route::get('device-repair-logs/template', [DeviceRepairLogController::class, 'template'])->name('device-repair-logs.template');
        Route::resource('device-repair-logs', DeviceRepairLogController::class)->only(['index']);
    });

    // Email Master (Gmail / Email accounts + Aliases)
    Route::middleware('module:email_master,view')->group(function () {
        Route::get('email-master', [EmailMasterController::class, 'index'])->name('email-master.index');

        // Templates carry no data, so viewing is enough to download them.
        Route::get('email-master/accounts/template', [EmailAccountController::class, 'template'])->name('email-accounts.template');
        Route::get('email-master/aliases/template', [EmailAliasController::class, 'template'])->name('email-aliases.template');
    });

    Route::middleware('module:email_master,edit')->group(function () {
        // Export decrypts account passwords, so require edit permission — this
        // also matches the UI, where the Export button is gated behind canEdit.
        Route::get('email-master/accounts/export', [EmailAccountController::class, 'export'])->name('email-accounts.export');
        Route::get('email-master/aliases/export', [EmailAliasController::class, 'export'])->name('email-aliases.export');

        // Accounts (Gmail + Email share one controller, distinguished by type)
        Route::post('email-master/accounts/import', [EmailAccountController::class, 'import'])->name('email-accounts.import');
        Route::delete('email-master/accounts/bulk', [EmailAccountController::class, 'bulkDestroy'])->name('email-accounts.bulk-destroy');
        Route::get('email-master/accounts/create', [EmailAccountController::class, 'create'])->name('email-accounts.create');
        Route::post('email-master/accounts', [EmailAccountController::class, 'store'])->name('email-accounts.store');
        Route::get('email-master/accounts/{emailAccount}/edit', [EmailAccountController::class, 'edit'])->whereNumber('emailAccount')->name('email-accounts.edit');
        Route::put('email-master/accounts/{emailAccount}', [EmailAccountController::class, 'update'])->whereNumber('emailAccount')->name('email-accounts.update');
        Route::delete('email-master/accounts/{emailAccount}', [EmailAccountController::class, 'destroy'])->whereNumber('emailAccount')->name('email-accounts.destroy');

        // Aliases
        Route::post('email-master/aliases/import', [EmailAliasController::class, 'import'])->name('email-aliases.import');
        Route::delete('email-master/aliases/bulk', [EmailAliasController::class, 'bulkDestroy'])->name('email-aliases.bulk-destroy');
        Route::get('email-master/aliases/create', [EmailAliasController::class, 'create'])->name('email-aliases.create');
        Route::post('email-master/aliases', [EmailAliasController::class, 'store'])->name('email-aliases.store');
        Route::get('email-master/aliases/{emailAlias}/edit', [EmailAliasController::class, 'edit'])->whereNumber('emailAlias')->name('email-aliases.edit');
        Route::put('email-master/aliases/{emailAlias}', [EmailAliasController::class, 'update'])->whereNumber('emailAlias')->name('email-aliases.update');
        Route::delete('email-master/aliases/{emailAlias}', [EmailAliasController::class, 'destroy'])->whereNumber('emailAlias')->name('email-aliases.destroy');
    });

    // Financial Management (approved POs + receipts, budget usage)
    Route::middleware('module:financial_management,view')->group(function () {
        Route::get('financial-pos/export', [FinancialPoController::class, 'export'])->name('financial-pos.export');
        Route::get('financial-pos/{financialPo}/receipts/{receipt}/file', [FinancialReceiptController::class, 'downloadFile'])
            ->whereNumber('financialPo')->whereNumber('receipt')->name('financial-pos.receipts.file.download');
        Route::resource('financial-pos', FinancialPoController::class)
            ->parameters(['financial-pos' => 'financialPo'])
            ->only(['index', 'show'])
            ->where(['financialPo' => '[0-9]+']);
    });
    Route::middleware('module:financial_management,edit')->group(function () {
        // POs are system-managed (mirrored from Subscriptions and License &
        // Contract), so there is no create/delete — only receipts are recorded
        // by hand against each PO. The sole exception is pay-as-you-go POs, whose
        // Renewal Cost can be edited (guarded inside the controller).
        // One-time (manual) purchase orders — e.g. a PC, UPS, hardware — entered
        // by hand rather than mirrored from a subscription / license.
        Route::get('financial-pos/create', [FinancialPoController::class, 'create'])->name('financial-pos.create');
        Route::post('financial-pos', [FinancialPoController::class, 'store'])->name('financial-pos.store');
        Route::get('financial-pos/{financialPo}/edit', [FinancialPoController::class, 'edit'])
            ->whereNumber('financialPo')->name('financial-pos.edit');
        Route::put('financial-pos/{financialPo}', [FinancialPoController::class, 'update'])
            ->whereNumber('financialPo')->name('financial-pos.update');
        Route::delete('financial-pos/{financialPo}', [FinancialPoController::class, 'destroy'])
            ->whereNumber('financialPo')->name('financial-pos.destroy');
        Route::post('financial-receipts', [FinancialReceiptController::class, 'storeFromHistory'])
            ->name('financial-receipts.store');
        Route::post('financial-pos/{financialPo}/receipts', [FinancialReceiptController::class, 'store'])
            ->whereNumber('financialPo')->name('financial-pos.receipts.store');
        Route::post('financial-pos/{financialPo}/receipts/quick-upload', [FinancialReceiptController::class, 'quickUpload'])
            ->whereNumber('financialPo')->name('financial-pos.receipts.quick-upload');
        Route::post('financial-pos/{financialPo}/receipts/{receipt}/file', [FinancialReceiptController::class, 'uploadFile'])
            ->whereNumber('financialPo')->whereNumber('receipt')->name('financial-pos.receipts.file.upload');
        Route::delete('financial-pos/{financialPo}/receipts/{receipt}', [FinancialReceiptController::class, 'destroy'])
            ->whereNumber('financialPo')->whereNumber('receipt')->name('financial-pos.receipts.destroy');
    });

    // GCP Cost Breakdown — monthly Google Cloud billing tables (own permission).
    Route::middleware('module:gcp_costs,view')->group(function () {
        Route::get('gcp-costs', [GcpCostBreakdownController::class, 'index'])->name('gcp-costs.index');
        Route::get('gcp-costs/compare', [GcpCostBreakdownController::class, 'compare'])->name('gcp-costs.compare');
        Route::get('gcp-costs/{gcpCost}', [GcpCostBreakdownController::class, 'show'])
            ->whereNumber('gcpCost')->name('gcp-costs.show');
    });
    Route::middleware('module:gcp_costs,edit')->group(function () {
        Route::get('gcp-costs/{gcpCost}/export', [GcpCostBreakdownController::class, 'export'])
            ->whereNumber('gcpCost')->name('gcp-costs.export');
        Route::get('gcp-costs/create', [GcpCostBreakdownController::class, 'create'])->name('gcp-costs.create');
        Route::post('gcp-costs', [GcpCostBreakdownController::class, 'store'])->name('gcp-costs.store');
        Route::post('gcp-costs/{gcpCost}/mail', [GcpCostBreakdownController::class, 'mail'])
            ->whereNumber('gcpCost')->name('gcp-costs.mail');
        Route::post('gcp-costs/{gcpCost}/duplicate', [GcpCostBreakdownController::class, 'duplicate'])
            ->whereNumber('gcpCost')->name('gcp-costs.duplicate');
        Route::get('gcp-costs/{gcpCost}/edit', [GcpCostBreakdownController::class, 'edit'])
            ->whereNumber('gcpCost')->name('gcp-costs.edit');
        Route::put('gcp-costs/{gcpCost}', [GcpCostBreakdownController::class, 'update'])
            ->whereNumber('gcpCost')->name('gcp-costs.update');
        Route::delete('gcp-costs/{gcpCost}', [GcpCostBreakdownController::class, 'destroy'])
            ->whereNumber('gcpCost')->name('gcp-costs.destroy');
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
