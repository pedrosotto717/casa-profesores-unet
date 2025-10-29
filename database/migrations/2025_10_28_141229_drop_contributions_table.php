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
        Schema::dropIfExists('contributions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('period');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->datetime('paid_at')->nullable();
            $table->string('receipt_url', 255)->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'period'], 'contributions_unique');
            $table->index('user_id');
        });
    }
};
