<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reconcile the GCP discount/tax columns to the final amount-based design.
     *
     * The original 2026_07_03_000001 migration first shipped percentage columns
     * (discount_percent / tax_percent) and was later rewritten in place to add
     * amount columns instead. Environments that already ran the percentage
     * version have that migration recorded, so the rewritten file never re-runs
     * for them — leaving them without discount_amount / tax_amount and 500ing on
     * save. This migration is fully idempotent: it drops any leftover percentage
     * columns and adds the amount columns only where missing, so it lands every
     * environment (fresh, percent-based, or already-amount-based) on the same
     * final schema.
     */
    public function up(): void
    {
        Schema::table('gcp_cost_breakdowns', function (Blueprint $table) {
            if (Schema::hasColumn('gcp_cost_breakdowns', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
            if (Schema::hasColumn('gcp_cost_breakdowns', 'tax_percent')) {
                $table->dropColumn('tax_percent');
            }
        });

        Schema::table('gcp_cost_breakdowns', function (Blueprint $table) {
            if (! Schema::hasColumn('gcp_cost_breakdowns', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 6)->nullable()->after('exchange_rate');
            }
            if (! Schema::hasColumn('gcp_cost_breakdowns', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 6)->nullable()->after('discount_amount');
            }
        });
    }

    /**
     * Intentionally irreversible: the columns' canonical definition lives in
     * 2026_07_03_000001. Rolling this reconciliation back would risk dropping
     * live discount/tax data, so we leave the schema as-is.
     */
    public function down(): void
    {
        // no-op
    }
};
