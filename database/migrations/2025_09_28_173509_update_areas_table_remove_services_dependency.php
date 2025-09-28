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
        Schema::table('areas', function (Blueprint $table) {
            // Add is_reservable field to indicate if area can be reserved
            $table->boolean('is_reservable')->nullable()->after('capacity');
            
            // Remove hourly_rate field as it's not appropriate for non-profit institution
            $table->dropColumn('hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            // Restore hourly_rate field
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('capacity');
            
            // Remove is_reservable field
            $table->dropColumn('is_reservable');
        });
    }
};
