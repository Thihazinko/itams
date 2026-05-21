<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_renewals', function (Blueprint $table) {
            $table->foreignId('second_approver_user_id')->nullable()->after('approver_email')
                ->constrained('users')->nullOnDelete();
            $table->string('second_approver_name')->nullable()->after('second_approver_user_id');
            $table->string('second_approver_email')->nullable()->after('second_approver_name');
            $table->string('second_signed_token', 64)->nullable()->unique()->after('signed_token');
            $table->timestamp('mailed_first_at')->nullable()->after('approved_at');
            $table->timestamp('mailed_second_at')->nullable()->after('mailed_first_at');
            $table->timestamp('second_approved_at')->nullable()->after('mailed_second_at');
        });

        DB::statement("ALTER TABLE subscription_renewals MODIFY COLUMN status ENUM(
            'draft',
            'pending_approval',
            'first_approved',
            'pending_second_approval',
            'approved',
            'final_confirmed',
            'rejected',
            'cancelled'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE subscription_renewals SET status='pending_approval'
            WHERE status IN ('draft','first_approved','pending_second_approval')");

        DB::statement("ALTER TABLE subscription_renewals MODIFY COLUMN status ENUM(
            'pending_approval',
            'approved',
            'final_confirmed',
            'rejected',
            'cancelled'
        ) NOT NULL DEFAULT 'pending_approval'");

        Schema::table('subscription_renewals', function (Blueprint $table) {
            $table->dropForeign(['second_approver_user_id']);
            $table->dropColumn([
                'second_approver_user_id',
                'second_approver_name',
                'second_approver_email',
                'second_signed_token',
                'mailed_first_at',
                'mailed_second_at',
                'second_approved_at',
            ]);
        });
    }
};
