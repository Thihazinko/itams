<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();

            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->string('subject');
            $table->string('reference')->nullable();

            $table->string('vendor_company')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_phone_email')->nullable();

            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_name');
            $table->string('approver_email');

            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_amount', 14, 2);
            $table->string('currency', 8);

            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('signed_token', 64)->unique();

            $table->enum('status', [
                'pending_approval',
                'approved',
                'final_confirmed',
                'rejected',
                'cancelled',
            ])->default('pending_approval');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('final_confirmed_at')->nullable();
            $table->string('final_confirmed_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_renewals');
    }
};
