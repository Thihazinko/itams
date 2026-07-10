<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Soft delete was added so a sync that mirrored POs from Subscriptions /
        // Licenses wouldn't regenerate a dismissed PO. That sync has since been
        // removed — POs are entered by hand — so the only thing soft delete did
        // was keep a deleted PO's row (and its unique po_number) alive, which
        // meant a number could never be reused after deletion. There is no
        // restore UI either, so nothing depends on recovering trashed POs.
        //
        // Purge the already-dismissed POs (their receipts cascade via the FK),
        // then drop the column so deleting a PO frees its number for good.
        DB::table('financial_pos')->whereNotNull('deleted_at')->delete();

        Schema::table('financial_pos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
