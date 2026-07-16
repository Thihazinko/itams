<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily hourly timesheet, modelled on the "Daily Task" sheet in
        // Task Management.xlsx: one row per (user, date, hour slot). Each filled
        // slot is one man-hour spent on the chosen category / task.
        Schema::create('task_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedTinyInteger('slot'); // 1-8, see TaskDailyEntry::SLOTS
            $table->foreignId('task_category_id')->nullable()->constrained('task_categories')->nullOnDelete();
            $table->foreignId('task_item_id')->nullable()->constrained('task_items')->nullOnDelete();
            $table->string('project_name')->nullable();
            $table->string('expense_name')->nullable();
            $table->string('work_type')->nullable();  // Regular / Temporary
            $table->string('study_type')->nullable(); // Work / Study
            $table->text('task_detail')->nullable();
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date', 'slot'], 'task_daily_entries_unique');
            $table->index(['work_date']);
            $table->index(['task_category_id']);
        });

        // The Plan-vs-Achievement monthly table is superseded by daily logging.
        Schema::dropIfExists('task_man_hours');
    }

    public function down(): void
    {
        Schema::dropIfExists('task_daily_entries');

        // Recreate the superseded man-hours table so this migration is reversible.
        Schema::create('task_man_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_item_id')->constrained('task_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('plan_hours', 7, 2)->default(0);
            $table->decimal('achievement_hours', 7, 2)->default(0);
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->unique(['task_item_id', 'user_id', 'year', 'month'], 'task_man_hours_unique');
            $table->index(['year', 'month']);
        });
    }
};
