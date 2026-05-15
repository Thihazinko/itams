<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY service_type VARCHAR(100) NOT NULL");
    }

    public function down(): void
    {
        // Coerce any free-text values to a known enum bucket so the column change does not fail.
        DB::table('subscriptions')
            ->whereNotIn('service_type', ['Domain', 'SSL', 'Subscription', 'Hosting', 'Cloud Service'])
            ->update(['service_type' => 'Subscription']);

        DB::statement("ALTER TABLE subscriptions MODIFY service_type ENUM('Domain','SSL','Subscription','Hosting','Cloud Service') NOT NULL");
    }
};
