<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notifications');
    }

    public function down(): void
    {
        // Recreate the table in the same shape as 2026_05_13_000003 so a
        // rollback of this migration leaves the schema in a runnable state.
        // No data is restored — historical notification rows are not preserved.
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->date('expire_date');
            $table->integer('days_remaining');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
};
