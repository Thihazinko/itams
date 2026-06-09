<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses_contracts', function (Blueprint $table) {
            $table->boolean('expire_permanent')->default(false)->after('expire_date');
            // Permanent records have no expiry, so the date must allow null.
            $table->date('expire_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('licenses_contracts', function (Blueprint $table) {
            $table->dropColumn('expire_permanent');
            $table->date('expire_date')->nullable(false)->change();
        });
    }
};
