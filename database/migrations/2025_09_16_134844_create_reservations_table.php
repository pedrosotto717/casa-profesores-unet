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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users'); // quien solicita
            $table->foreignId('area_id')->constrained();
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->enum('status', ['pendiente', 'aprobada', 'rechazada', 'cancelada', 'completada', 'expirada'])->default('pendiente');
            $table->foreignId('approved_by')->nullable()->constrained('users'); // admin que aprueba/niega
            $table->datetime('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable(); // justificación admin
            $table->string('title', 180)->nullable(); // etiqueta/resumen del evento
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índice compuesto para consultas de disponibilidad
            $table->index(['area_id', 'starts_at', 'ends_at']);
            $table->index('requester_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
