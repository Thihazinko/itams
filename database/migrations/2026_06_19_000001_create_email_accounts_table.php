<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['Gmail', 'Email'])->default('Email');
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('address');
            $table->string('username')->nullable();
            // Stored encrypted at the application layer (see EmailAccount::$casts),
            // so the ciphertext can exceed the source length — use text.
            $table->text('password')->nullable();
            $table->text('remark')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
