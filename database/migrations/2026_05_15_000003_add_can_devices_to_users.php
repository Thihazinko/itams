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
            $table->boolean('can_devices')->default(false)->after('can_licenses_contracts');
        });

        // Mirror the convention used for the other module flags: existing accounts
        // keep access to the new module by default. Admins bypass the flags anyway.
        DB::table('users')->update(['can_devices' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_devices');
        });
    }
};
