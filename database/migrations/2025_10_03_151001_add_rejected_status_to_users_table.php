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
            // Add 'rechazado' to the existing status enum
            $table->enum('status', ['aprobacion_pendiente', 'solvente', 'insolvente', 'rechazado'])
                  ->default('aprobacion_pendiente')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove 'rechazado' from the status enum
            $table->enum('status', ['aprobacion_pendiente', 'solvente', 'insolvente'])
                  ->default('aprobacion_pendiente')
                  ->change();
        });
    }
};
