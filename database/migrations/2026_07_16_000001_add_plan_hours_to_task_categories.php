<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Planned man-hours target for a whole category, set on the Manage Tasks
        // reference page and compared against logged (achieved) hours in reports.
        Schema::table('task_categories', function (Blueprint $table) {
            $table->decimal('plan_hours', 8, 2)->default(0)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('task_categories', function (Blueprint $table) {
            $table->dropColumn('plan_hours');
        });
    }
};
