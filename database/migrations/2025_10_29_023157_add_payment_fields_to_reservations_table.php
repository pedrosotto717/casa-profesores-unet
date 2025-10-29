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
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('factura_id')->nullable()->constrained('facturas')->onDelete('set null');
            $table->enum('estatus_pago', ['Pendiente', 'Pagado'])->default('Pendiente');
            $table->timestamp('fecha_cancelacion')->nullable();
            
            $table->index('factura_id');
            $table->index('estatus_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['factura_id']);
            $table->dropColumn(['factura_id', 'estatus_pago', 'fecha_cancelacion']);
        });
    }
};
