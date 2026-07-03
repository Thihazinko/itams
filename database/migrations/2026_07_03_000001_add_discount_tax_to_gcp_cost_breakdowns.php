<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoice-style adjustments applied to a breakdown's line subtotal: a
        // discount then a tax, entered by hand as amounts in the breakdown's
        // billing currency (¥ or $). Grand total = subtotal − discount + tax.
        Schema::table('gcp_cost_breakdowns', function (Blueprint $table) {
            $table->decimal('discount_amount', 14, 6)->nullable()->after('exchange_rate');
            $table->decimal('tax_amount', 14, 6)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('gcp_cost_breakdowns', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'tax_amount']);
        });
    }
};
