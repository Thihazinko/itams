<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // POs are now sourced only from approved Subscription renewals and from
        // License & Contract records — manual entry is removed. Drop any existing
        // manual POs (their receipts cascade away with them).
        DB::table('financial_pos')->where('source', 'manual')->delete();

        Schema::table('financial_pos', function (Blueprint $table) {
            // Widen `source` from the old enum to a plain string so it can carry
            // the new 'license_contract' value without enum-alter friction.
            $table->string('source', 20)->default('subscription')->change();

            // Link to a License & Contract record (mirrors subscription_renewal_id).
            $table->foreignId('license_contract_id')
                ->nullable()
                ->unique()
                ->after('subscription_renewal_id')
                ->constrained('licenses_contracts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('license_contract_id');
            $table->enum('source', ['manual', 'subscription'])->default('manual')->change();
        });
    }
};
