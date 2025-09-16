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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_user_id')->constrained('users'); // quien invita, típicamente docente
            $table->foreignId('invitee_user_id')->nullable()->constrained('users'); // si ya existe el usuario invitado
            $table->string('email', 180); // correo del invitado
            $table->char('token', 64)->unique(); // seguro, no predecible
            $table->enum('status', ['pendiente', 'aceptada', 'rechazada', 'expirada', 'revocada'])->default('pendiente');
            $table->datetime('expires_at')->nullable(); // ej. +90 días
            $table->text('message')->nullable(); // nota opcional del docente
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // admin que aprobó/rechazó
            $table->datetime('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index('inviter_user_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
