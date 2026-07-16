<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_view_task_management')->default(false)->after('can_edit_gcp_costs');
            $table->boolean('can_edit_task_management')->default(false)->after('can_view_task_management');
        });

        // The users granted Task Management access double as its "members" — each
        // one becomes a man-hour column on the monthly sheet. Admins bypass the
        // flags for access, so to make an admin a member as well, tick the flag
        // on their account explicitly. Existing accounts get no access by default.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_task_management', 'can_edit_task_management']);
        });
    }
};
