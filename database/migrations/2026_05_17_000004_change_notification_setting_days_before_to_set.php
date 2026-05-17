<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->json('days_before_set')->nullable()->after('days_before');
        });

        // Backfill: wrap the existing single integer in a JSON array.
        // e.g. 15 -> "[15]"
        DB::table('notification_settings')->get()->each(function ($row) {
            $value = $row->days_before !== null ? [(int) $row->days_before] : [30];
            DB::table('notification_settings')
                ->where('id', $row->id)
                ->update(['days_before_set' => json_encode($value)]);
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn('days_before');
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->integer('days_before')->default(30)->after('enabled');
        });

        // Reverse backfill: take the largest selected window as the legacy single value.
        DB::table('notification_settings')->get()->each(function ($row) {
            $set = json_decode($row->days_before_set ?? '[30]', true) ?: [30];
            DB::table('notification_settings')
                ->where('id', $row->id)
                ->update(['days_before' => max(array_map('intval', $set))]);
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn('days_before_set');
        });
    }
};
