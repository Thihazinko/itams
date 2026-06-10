<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pc_asset_software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pc_asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('pc_asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pc_asset_software');
    }
};
