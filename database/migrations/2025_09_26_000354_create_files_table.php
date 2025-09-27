<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('original_filename', 255);
            $table->string('file_path', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 64)->nullable(); // SHA-256 hash for deduplication
            $table->enum('file_type', ['document', 'image', 'receipt', 'other'])->default('other');
            $table->string('storage_disk', 50)->default('r2');
            $table->json('metadata')->nullable(); // For additional file metadata
            $table->enum('visibility', ['publico', 'privado', 'restringido'])->default('publico');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('file_hash');
            $table->index('file_type');
            $table->index('storage_disk');
            $table->index('uploaded_by');
            $table->index(['uploaded_by', 'file_type']);
            $table->index(['file_type', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
