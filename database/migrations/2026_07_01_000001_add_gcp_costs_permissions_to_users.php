<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_view_gcp_costs')->default(false)->after('can_edit_financial_management');
            $table->boolean('can_edit_gcp_costs')->default(false)->after('can_view_gcp_costs');
        });

        // GCP Cost Breakdown is split out of Financial Management into its own
        // module. Like financial data, it is sensitive, so existing non-admin
        // accounts are NOT granted access by default — admins bypass the flags,
        // and other users are granted access individually from User Management.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_gcp_costs', 'can_edit_gcp_costs']);
        });
    }
};
