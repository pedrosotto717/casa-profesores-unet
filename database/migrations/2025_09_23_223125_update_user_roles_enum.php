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
        Schema::table('users', function (Blueprint $table) {
            // Update the role enum to include new roles and change 'docente' to 'profesor'
            $table->enum('role', ['usuario', 'profesor', 'instructor', 'administrador', 'obrero', 'estudiante', 'invitado'])
                  ->default('usuario')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('role', ['docente', 'administrador', 'obrero', 'estudiante', 'invitado'])
                  ->default('docente')
                  ->change();
        });
    }
};
