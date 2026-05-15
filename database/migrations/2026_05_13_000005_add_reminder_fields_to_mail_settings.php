<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->text('reminder_recipients')->nullable()->after('from_name');
            $table->unsignedInteger('reminder_days_before')->default(30)->after('reminder_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->dropColumn(['reminder_recipients', 'reminder_days_before']);
        });
    }
};
