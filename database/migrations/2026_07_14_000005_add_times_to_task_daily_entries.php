<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The "Hours" column becomes editable: each slot now carries its own
        // start/end time (pre-filled with the standard slot times) so a user can
        // adjust the actual window worked. Man-hours are derived from the span.
        Schema::table('task_daily_entries', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('slot');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('task_daily_entries', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
