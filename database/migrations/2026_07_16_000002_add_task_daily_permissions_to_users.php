<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Daily Task" is a narrower permission than full Task Management: it lets a
        // user log their own daily hours without seeing the monthly reports or the
        // task/category setup. Full Task Management access implies Daily Task (see
        // User::canView/canEdit), so existing accounts keep their access unchanged.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_view_task_daily')->default(false)->after('can_edit_task_management');
            $table->boolean('can_edit_task_daily')->default(false)->after('can_view_task_daily');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_task_daily', 'can_edit_task_daily']);
        });
    }
};
