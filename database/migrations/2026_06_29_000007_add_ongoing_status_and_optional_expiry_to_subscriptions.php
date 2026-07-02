<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pay-as-you-go (usage-based, e.g. AWS/GCP) has no fixed expiry, so the
        // Expire Date becomes optional and such subscriptions carry a dedicated
        // "Ongoing" renewal status.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('expire_date')->nullable()->change();
        });

        DB::statement("ALTER TABLE subscriptions MODIFY renewal_status
            ENUM('Pending','Renewed','Expired','Cancelled','Ongoing') NOT NULL DEFAULT 'Pending'");

        // Backfill existing rows to the new rules.
        DB::table('subscriptions')->where('status', 'Terminated')
            ->update(['renewal_status' => 'Cancelled']);
        DB::table('subscriptions')->where('status', 'Active')
            ->where('renewal_type', 'Pay as you go')
            ->update(['renewal_status' => 'Ongoing']);
    }

    public function down(): void
    {
        DB::table('subscriptions')->where('renewal_status', 'Ongoing')
            ->update(['renewal_status' => 'Pending']);

        DB::statement("ALTER TABLE subscriptions MODIFY renewal_status
            ENUM('Pending','Renewed','Expired','Cancelled') NOT NULL DEFAULT 'Pending'");

        // Restore NOT NULL: fill any blanks first so the change can't fail.
        DB::table('subscriptions')->whereNull('expire_date')
            ->update(['expire_date' => now()->toDateString()]);
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('expire_date')->nullable(false)->change();
        });
    }
};
