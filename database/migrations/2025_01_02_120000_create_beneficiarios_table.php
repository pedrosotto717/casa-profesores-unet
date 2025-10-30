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
        Schema::create('beneficiarios', function (Blueprint $table) {
            $table->id();
            
            // FK al usuario (Profesor/Agremiado)
            $table->foreignId('agremiado_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->string('nombre_completo');
            
            // Validación estricta de parentesco
            $table->enum('parentesco', [
                'conyuge',
                'hijo',
                'madre',
                'padre'
            ]);
            
            // Flujo de aprobación
            $table->enum('estatus', [
                'pendiente',
                'aprobado',
                'inactivo'
            ])->default('pendiente');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiarios');
    }
};
