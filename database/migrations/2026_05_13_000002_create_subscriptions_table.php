<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->enum('service_type', ['Domain', 'SSL', 'Subscription', 'Hosting', 'Cloud Service']);
            $table->string('project_name');
            $table->string('subscription_name');
            $table->enum('status', ['Active', 'Terminated'])->default('Active');
            $table->string('period')->nullable();
            $table->decimal('previous_cost', 10, 2)->nullable();
            $table->date('expire_date');
            $table->decimal('renewal_cost', 10, 2)->nullable();
            $table->enum('renewal_type', ['Yearly', 'Monthly', 'Pay as you go', 'One Time'])->default('Yearly');
            $table->date('reminder_date')->nullable();
            $table->enum('renewal_status', ['Pending', 'Renewed', 'Expired', 'Cancelled'])->default('Pending');
            $table->text('remarks')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
