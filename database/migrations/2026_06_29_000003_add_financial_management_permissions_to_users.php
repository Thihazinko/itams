<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_view_financial_management')->default(false)->after('can_edit_email_master');
            $table->boolean('can_edit_financial_management')->default(false)->after('can_view_financial_management');
        });

        // Unlike the earlier module flags, financial data is sensitive, so existing
        // non-admin accounts are NOT granted access by default. Admins bypass the
        // flags; grant other users access individually from the User Management form.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_financial_management', 'can_edit_financial_management']);
        });
    }
};
