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
        Schema::create('academy_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('academy_schedule_id')->nullable()->constrained(); // si se asigna franja concreta
            $table->enum('status', ['pendiente', 'confirmada', 'anulada'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Único recomendado para evitar duplicados generales
            $table->unique(['academy_id', 'user_id']);
            $table->index('academy_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_enrollments');
    }
};
