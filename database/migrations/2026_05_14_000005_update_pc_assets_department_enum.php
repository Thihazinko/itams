<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen to varchar so we can rewrite the old 'IT' value to 'IT Development'.
        DB::statement("ALTER TABLE pc_assets MODIFY department VARCHAR(50) NULL");

        DB::table('pc_assets')->where('department', 'IT')->update(['department' => 'IT Development']);

        DB::statement("ALTER TABLE pc_assets MODIFY department ENUM('Admin','Finance','HR','IT Development','Contract','Offshore','SST','BPO','Infra') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pc_assets MODIFY department VARCHAR(50) NULL");

        DB::table('pc_assets')->where('department', 'IT Development')->update(['department' => 'IT']);
        DB::table('pc_assets')->whereNotIn('department', ['IT', 'HR', 'Finance', 'Contract'])->update(['department' => null]);

        DB::statement("ALTER TABLE pc_assets MODIFY department ENUM('IT','HR','Finance','Contract') NULL");
    }
};
