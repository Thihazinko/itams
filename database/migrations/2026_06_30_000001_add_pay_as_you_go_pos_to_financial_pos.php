<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pay-as-you-go subscriptions accrue a fresh PO every month they stay
        // active (unlike one-off renewal / license POs). These rows are keyed by
        // (subscription_id, billing_month) so the sync stays insert-only and
        // idempotent, and — unlike mirrored POs — their Renewal Cost is editable.
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('subscription_renewal_id')
                ->constrained('subscriptions')
                ->nullOnDelete();

            // First day of the billing month this PO covers.
            $table->date('billing_month')->nullable()->after('subscription_id');

            // One PO per subscription per month.
            $table->unique(['subscription_id', 'billing_month']);
        });
    }

    public function down(): void
    {
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->dropUnique(['subscription_id', 'billing_month']);
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn('billing_month');
        });
    }
};
