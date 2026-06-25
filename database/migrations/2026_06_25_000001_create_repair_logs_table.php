<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_logs', function (Blueprint $table) {
            $table->id();
            // Link to the PC in PC Master; nulled if that PC is later deleted.
            // computer_id is also stored as a snapshot so the log stays readable.
            $table->foreignId('pc_asset_id')->nullable()->constrained('pc_assets')->nullOnDelete();
            $table->string('computer_id');
            $table->date('repair_date');
            $table->string('employee_name')->nullable();
            $table->string('department')->nullable();
            $table->text('repair_process');
            $table->string('status')->default('In Progress');
            $table->text('remark')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('computer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_logs');
    }
};
