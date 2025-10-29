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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('tipo', ['Aporte Solvencia', 'Pago Reserva']);
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 10)->default('USD');
            $table->timestamp('fecha_emision');
            $table->timestamp('fecha_pago')->nullable();
            $table->enum('estatus_pago', ['Pagado', 'Pendiente'])->default('Pagado');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            $table->index('tipo');
            $table->index('estatus_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
