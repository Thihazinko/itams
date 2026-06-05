<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pc_assets MODIFY department ENUM('Admin','Finance','HR','IT Development','Contract','Offshore','SST','BPO','Infra','Sale') NULL");
    }

    public function down(): void
    {
        // Revert any 'Sale' rows before narrowing the ENUM, or they'd be truncated.
        DB::table('pc_assets')->where('department', 'Sale')->update(['department' => null]);

        DB::statement("ALTER TABLE pc_assets MODIFY department ENUM('Admin','Finance','HR','IT Development','Contract','Offshore','SST','BPO','Infra') NULL");
    }
};
