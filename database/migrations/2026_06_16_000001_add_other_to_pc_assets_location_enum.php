<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pc_assets MODIFY location ENUM('Office','WFH','Other') NOT NULL DEFAULT 'Office'");
    }

    public function down(): void
    {
        // Revert any 'Other' rows before narrowing the ENUM, or they'd be truncated.
        DB::table('pc_assets')->where('location', 'Other')->update(['location' => 'Office']);

        DB::statement("ALTER TABLE pc_assets MODIFY location ENUM('Office','WFH') NOT NULL DEFAULT 'Office'");
    }
};
