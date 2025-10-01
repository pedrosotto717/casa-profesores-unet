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
        Schema::table('users', function (Blueprint $table) {
            // Remove old is_solvent field
            $table->dropColumn('is_solvent');
            
            // Add new status field with enum values
            $table->enum('status', ['aprobacion_pendiente', 'solvente', 'insolvente'])
                  ->default('aprobacion_pendiente')
                  ->after('role');
            
            // Add responsible_email field (nullable, stores professor's email)
            $table->string('responsible_email', 180)
                  ->nullable()
                  ->after('status');
            
            // Add aspired_role field (nullable, for auto-registration)
            $table->enum('aspired_role', ['profesor', 'estudiante', 'instructor', 'obrero'])
                  ->nullable()
                  ->after('responsible_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop new fields
            $table->dropColumn(['status', 'responsible_email', 'aspired_role']);
            
            // Restore old is_solvent field
            $table->boolean('is_solvent')->default(false)->after('sso_uid');
        });
    }
};
