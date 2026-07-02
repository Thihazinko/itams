<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each GCP project line now carries its cost in both JPY and USD.
        Schema::table('gcp_cost_lines', function (Blueprint $table) {
            $table->decimal('cost_usd', 18, 6)->nullable()->after('cost_jpy');
        });

        // Backfill existing rows with the USD equivalent of the JPY cost using the
        // breakdown's exchange rate (JPY per USD), so historical data isn't blank.
        DB::statement('
            UPDATE gcp_cost_lines l
            JOIN gcp_cost_breakdowns b ON b.id = l.gcp_cost_breakdown_id
            SET l.cost_usd = ROUND(l.cost_jpy / b.exchange_rate, 6)
            WHERE l.cost_jpy IS NOT NULL AND b.exchange_rate IS NOT NULL AND b.exchange_rate > 0
        ');
    }

    public function down(): void
    {
        Schema::table('gcp_cost_lines', function (Blueprint $table) {
            $table->dropColumn('cost_usd');
        });
    }
};
