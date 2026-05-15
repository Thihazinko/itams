<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_pc_assets')->default(false)->after('role');
            $table->boolean('can_subscriptions')->default(false)->after('can_pc_assets');
            $table->boolean('can_licenses_contracts')->default(false)->after('can_subscriptions');
        });

        // Preserve existing behavior: every existing account keeps access to all
        // three modules. Admins bypass the flags anyway, but flip them on for
        // consistency.
        DB::table('users')->update([
            'can_pc_assets' => true,
            'can_subscriptions' => true,
            'can_licenses_contracts' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_pc_assets', 'can_subscriptions', 'can_licenses_contracts']);
        });
    }
};
