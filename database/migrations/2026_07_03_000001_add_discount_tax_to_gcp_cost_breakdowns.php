<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoice-style adjustments applied to a breakdown's line subtotal:
        // a discount then a tax, both as percentages so they apply cleanly to
        // whichever currency (¥/$) the breakdown is billed in.
        Schema::table('gcp_cost_breakdowns', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 4)->nullable()->after('exchange_rate');
            $table->decimal('tax_percent', 8, 4)->nullable()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('gcp_cost_breakdowns', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
    }
};
