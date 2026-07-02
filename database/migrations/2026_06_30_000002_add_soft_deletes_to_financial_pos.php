<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // POs are mirrored from their sources on every view, so a hard delete
        // would simply reappear on the next sync. A soft delete lets the sync
        // recognise a PO as "already handled" (via withTrashed checks) and leave
        // it dismissed, so deleting one keeps it out of the register for good.
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('financial_pos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
