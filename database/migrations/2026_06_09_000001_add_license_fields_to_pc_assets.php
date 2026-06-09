<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pc_assets', function (Blueprint $table) {
            $table->string('license_key')->nullable()->after('operating_system');
            $table->date('expire_date')->nullable()->after('purchased_date');
        });
    }

    public function down(): void
    {
        Schema::table('pc_assets', function (Blueprint $table) {
            $table->dropColumn(['license_key', 'expire_date']);
        });
    }
};
