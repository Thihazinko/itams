<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per project line in a monthly GCP billing table, mirroring the
        // spreadsheet columns (No, Project Name, Usage, Billing Account, Project
        // ID, usage dates, Billing Card, Card Setting, Cost ¥, status note).
        Schema::create('gcp_cost_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gcp_cost_breakdown_id')
                ->constrained('gcp_cost_breakdowns')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);
            $table->string('project_name')->nullable();
            $table->text('usage')->nullable();
            $table->string('billing_account_name')->nullable();
            $table->string('project_id')->nullable();
            $table->date('usage_start_date')->nullable();
            $table->date('usage_end_date')->nullable();
            $table->string('billing_card')->nullable();
            $table->string('card_setting')->nullable();
            $table->decimal('cost_jpy', 18, 6)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['gcp_cost_breakdown_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gcp_cost_lines');
    }
};
