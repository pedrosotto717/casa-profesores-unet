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
        Schema::table('areas', function (Blueprint $table) {
            $table->decimal('monto_hora_externo', 10, 2)->default(0.00);
            $table->integer('porcentaje_descuento_agremiado')->default(0);
            $table->string('moneda', 10)->default('USD');
            $table->boolean('es_gratis_agremiados')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn([
                'monto_hora_externo',
                'porcentaje_descuento_agremiado',
                'moneda',
                'es_gratis_agremiados'
            ]);
        });
    }
};
