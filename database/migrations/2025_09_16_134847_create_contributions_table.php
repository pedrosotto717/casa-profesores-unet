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
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('period'); // convención: usar día 1 de cada mes, ej. 2025-07-01
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->datetime('paid_at')->nullable();
            $table->string('receipt_url', 255)->nullable(); // comprobante opcional
            $table->timestamps();
            
            // Único compuesto para evitar duplicados por usuario y período
            $table->unique(['user_id', 'period'], 'contributions_unique');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
