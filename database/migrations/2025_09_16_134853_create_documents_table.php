<?php declare(strict_types=1);

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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('file_url', 255); // ruta local o S3
            $table->enum('visibility', ['publico', 'interno', 'solo_admin'])->default('interno');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
