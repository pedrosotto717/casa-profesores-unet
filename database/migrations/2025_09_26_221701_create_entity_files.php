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
        Schema::create('entity_files', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50); // 'Area', 'Service', 'Academy'
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->string('caption', 255)->nullable();
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id', 'sort_order']);
            $table->index(['entity_type', 'entity_id', 'is_cover']);
            $table->unique(['entity_type', 'entity_id', 'file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_files');
    }
};
