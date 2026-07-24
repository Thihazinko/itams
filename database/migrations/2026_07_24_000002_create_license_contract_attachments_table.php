<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_contract_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('license_contract_id')
                ->constrained('licenses_contracts')
                ->cascadeOnDelete();

            // File stored on the public disk (like financial receipts).
            $table->string('file_path');
            $table->string('original_name')->nullable();
            // Optional user-friendly label / document type (e.g. "Contract",
            // "Invoice", "Renewal Quote").
            $table->string('label')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('license_contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_contract_attachments');
    }
};
