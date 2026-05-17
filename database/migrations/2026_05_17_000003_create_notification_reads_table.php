<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Module + record id pair, since reads span subscriptions and licenses_contracts.
            $table->string('module', 32);
            $table->unsignedBigInteger('notifiable_id');
            // Snapshot of expire_date + urgency bucket at read time. If the
            // current value differs, the item is treated as unread again — so
            // renewals (expire_date moves) and bucket transitions (upcoming →
            // due_soon → overdue) auto-re-surface in the badge.
            $table->string('read_signature', 64);
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'module', 'notifiable_id']);
            $table->index(['module', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
