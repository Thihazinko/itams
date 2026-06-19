<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('main_email');
            $table->text('remark')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('main_email');
        });

        Schema::create('email_alias_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_alias_id')->constrained('email_aliases')->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();

            $table->index('email_alias_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_alias_members');
        Schema::dropIfExists('email_aliases');
    }
};
