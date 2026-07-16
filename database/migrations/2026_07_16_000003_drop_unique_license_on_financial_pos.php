<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `license_contract_id` was made UNIQUE back when each License & Contract
     * mirrored to exactly one PO. POs are now linked by hand and a monthly-billed
     * licence needs a PO per month, so the single-column unique wrongly blocks the
     * second PO for the same licence (the insert fails on save). Replace it with a
     * plain index — the foreign key stays, but multiple POs may link to one licence.
     */
    public function up(): void
    {
        // Add a plain index first so the foreign key keeps a supporting index
        // once the unique one is dropped.
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->index('license_contract_id');
        });

        Schema::table('financial_pos', function (Blueprint $table) {
            $table->dropUnique('financial_pos_license_contract_id_unique');
        });
    }

    public function down(): void
    {
        // Reverting requires no duplicate licence links to exist.
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->unique('license_contract_id', 'financial_pos_license_contract_id_unique');
        });

        Schema::table('financial_pos', function (Blueprint $table) {
            $table->dropIndex(['license_contract_id']);
        });
    }
};
