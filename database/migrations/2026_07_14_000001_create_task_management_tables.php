<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference structure mirrored from the "Task Management.xlsx" workbook:
        // a fixed set of categories, each holding a list of tasks.
        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('task_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_category_id')->constrained('task_categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['task_category_id', 'sort_order']);
        });

        // One row per (task, member, month): the planned vs achieved man-hours a
        // member spent on a task in a given month. Difference is computed, not stored.
        Schema::create('task_man_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_item_id')->constrained('task_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->decimal('plan_hours', 7, 2)->default(0);
            $table->decimal('achievement_hours', 7, 2)->default(0);
            $table->string('created_by')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->unique(['task_item_id', 'user_id', 'year', 'month'], 'task_man_hours_unique');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_man_hours');
        Schema::dropIfExists('task_items');
        Schema::dropIfExists('task_categories');
    }
};
