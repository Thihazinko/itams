<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pc_assets', function (Blueprint $table) {
            // Archive marker: a "disposed" PC is soft-deleted (hidden from the
            // active list) but kept for the record.
            $table->softDeletes();

            // Disposal / budget-year retirement details.
            $table->date('retired_date')->nullable()->after('warranty_period');
            $table->string('budget_year')->nullable()->after('retired_date');
            $table->string('disposal_method')->nullable()->after('budget_year');
            $table->text('disposal_reason')->nullable()->after('disposal_method');
            $table->string('disposed_by')->nullable()->after('disposal_reason');
            $table->string('approved_by')->nullable()->after('disposed_by');
        });
    }

    public function down(): void
    {
        Schema::table('pc_assets', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'retired_date', 'budget_year', 'disposal_method',
                'disposal_reason', 'disposed_by', 'approved_by',
            ]);
        });
    }
};
