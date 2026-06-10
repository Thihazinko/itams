<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses_contracts', function (Blueprint $table) {
            $table->date('start_using_date')->nullable()->after('last_renewal_date');
        });
    }

    public function down(): void
    {
        Schema::table('licenses_contracts', function (Blueprint $table) {
            $table->dropColumn('start_using_date');
        });
    }
};
