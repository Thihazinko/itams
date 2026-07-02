<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('financial_po_id')
                ->constrained('financial_pos')
                ->cascadeOnDelete();

            $table->string('receipt_number')->nullable();
            // The date money actually changed hands — this is what monthly/yearly
            // budget usage is bucketed by.
            $table->date('receipt_date');
            $table->decimal('paid_amount', 16, 2);
            $table->string('currency', 3)->default('MMK');
            $table->string('payment_method')->nullable();

            // Optional scan/photo of the receipt, stored on the public disk.
            $table->string('file_path')->nullable();

            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('receipt_date');
            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_receipts');
    }
};
