<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pc_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pc_asset_id')->constrained()->cascadeOnDelete();
            $table->string('employee_name')->nullable();
            $table->string('department')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('released_reason')->nullable();
            $table->string('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['pc_asset_id', 'assigned_at']);
        });

        // Backfill: record the CURRENT assignment for every PC that already
        // has an employee, so existing assignments show up in the history
        // immediately (using the asset's creation time as the start date).
        DB::statement("
            INSERT INTO pc_asset_assignments
                (pc_asset_id, employee_name, department, assigned_at, recorded_by, created_at, updated_at)
            SELECT id, employee_name, department, COALESCE(created_at, NOW()), modified_by, NOW(), NOW()
            FROM pc_assets
            WHERE employee_name IS NOT NULL AND employee_name <> ''
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('pc_asset_assignments');
    }
};
