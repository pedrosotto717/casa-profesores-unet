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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained(); // acciones del sistema pueden ser NULL
            $table->string('entity_type', 120); // nombre de modelo: Reservation, Contribution, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action', 120); // ej. created, updated, status_changed
            $table->json('before')->nullable(); // estado anterior
            $table->json('after')->nullable(); // estado posterior
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
