<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per monthly GCP billing table (mirrors the "JPY Cost Table"
        // spreadsheet header: period, who reported it, and the exchange rate).
        Schema::create('gcp_cost_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('billing_account_name')->nullable();
            $table->string('reported_by')->nullable();
            $table->decimal('exchange_rate', 14, 6)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gcp_cost_breakdowns');
    }
};
