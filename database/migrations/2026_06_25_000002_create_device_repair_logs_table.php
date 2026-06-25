<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_repair_logs', function (Blueprint $table) {
            $table->id();
            // Link to the device in Device Master; nulled if that device is
            // later deleted. device_label keeps a readable snapshot regardless.
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('device_label');
            $table->date('repair_date');
            $table->string('employee_name')->nullable();
            $table->string('department')->nullable();
            $table->text('repair_process');
            $table->string('status')->default('In Progress');
            $table->text('remark')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_repair_logs');
    }
};
