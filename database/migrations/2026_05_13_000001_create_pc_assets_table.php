<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pc_assets', function (Blueprint $table) {
            $table->id();
            $table->string('computer_id')->unique();
            $table->string('hostname');
            $table->string('employee_name')->nullable();
            $table->enum('status', ['Free', 'Active', 'Damage', 'Retirement', 'Low Performance'])->default('Free');
            $table->enum('department', ['IT', 'HR', 'Finance', 'Contract'])->nullable();
            $table->enum('location', ['Office', 'WFH'])->default('Office');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('cpu')->nullable();
            $table->string('ram')->nullable();
            $table->string('ssd')->nullable();
            $table->string('hdd')->nullable();
            $table->string('display')->nullable();
            $table->string('operating_system')->nullable();
            $table->text('admin_password')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->date('purchased_date')->nullable();
            $table->string('warranty_period')->nullable();
            $table->text('remarks')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pc_assets');
    }
};
