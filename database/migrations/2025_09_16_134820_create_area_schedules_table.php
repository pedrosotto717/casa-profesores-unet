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
        Schema::create('area_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0=domingo .. 6=sábado
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_open')->default(true);
            $table->timestamps();
            
            // Único compuesto para evitar duplicados
            $table->unique(['area_id', 'day_of_week', 'start_time', 'end_time'], 'area_schedules_unique');
            $table->index('area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_schedules');
    }
};
