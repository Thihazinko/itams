<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('location')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->enum('status', ['Active', 'Free', 'Damage', 'Retirement', 'Lost'])->default('Free');
            $table->text('description')->nullable();
            $table->string('vendor')->nullable();
            $table->date('purchased_date')->nullable();
            $table->string('warranty')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->text('remark')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();

            $table->index('item_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
