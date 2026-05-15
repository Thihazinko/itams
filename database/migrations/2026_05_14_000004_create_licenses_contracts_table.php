<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('software_name');
            $table->enum('status', ['Active', 'Expired', 'Terminated', 'Pending'])->default('Active');
            $table->enum('renewal_type', ['Yearly', 'Monthly', 'Pay as you go', 'One Time'])->default('Yearly');
            $table->text('license_info')->nullable();
            $table->date('last_renewal_date')->nullable();
            $table->date('expire_date');
            $table->string('vendor_name')->nullable();
            $table->decimal('previous_cost', 12, 2)->nullable();
            $table->decimal('renewal_cost', 12, 2)->nullable();
            $table->string('currency', 3)->default('MMK');
            $table->text('remarks')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses_contracts');
    }
};
