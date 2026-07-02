<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_pos', function (Blueprint $table) {
            $table->id();

            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->string('subject');
            $table->string('vendor_name')->nullable();
            $table->string('category')->nullable();

            $table->decimal('total_amount', 16, 2)->default(0);
            $table->string('currency', 3)->default('MMK');

            // 'manual' rows are entered here directly; 'subscription' rows are
            // mirrored from approved subscription_renewals so they appear in the
            // same register without being editable twice.
            $table->enum('source', ['manual', 'subscription'])->default('manual');
            $table->foreignId('subscription_renewal_id')
                ->nullable()
                ->unique()
                ->constrained('subscription_renewals')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('currency');
            $table->index('po_date');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_pos');
    }
};
