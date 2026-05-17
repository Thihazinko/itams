<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $modules = ['pc_assets', 'subscriptions', 'licenses_contracts', 'devices'];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $after = 'role';
            foreach ($this->modules as $module) {
                $table->boolean("can_view_{$module}")->default(false)->after($after);
                $table->boolean("can_edit_{$module}")->default(false)->after("can_view_{$module}");
                $after = "can_edit_{$module}";
            }
        });

        // Backfill: anyone who previously had access keeps both view and edit.
        foreach ($this->modules as $module) {
            DB::statement("UPDATE users SET can_view_{$module} = can_{$module}, can_edit_{$module} = can_{$module}");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_map(fn ($m) => "can_{$m}", $this->modules));
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $after = 'role';
            foreach ($this->modules as $module) {
                $table->boolean("can_{$module}")->default(false)->after($after);
                $after = "can_{$module}";
            }
        });

        // Reverse backfill: previous access = view OR edit on the new columns.
        foreach ($this->modules as $module) {
            DB::statement("UPDATE users SET can_{$module} = CASE WHEN can_view_{$module} = 1 OR can_edit_{$module} = 1 THEN 1 ELSE 0 END");
        }

        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach ($this->modules as $module) {
                $cols[] = "can_view_{$module}";
                $cols[] = "can_edit_{$module}";
            }
            $table->dropColumn($cols);
        });
    }
};
