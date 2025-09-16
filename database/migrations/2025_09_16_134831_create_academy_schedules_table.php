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
        Schema::create('academy_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('area_id')->constrained(); // dónde se dicta
            $table->tinyInteger('day_of_week'); // 0..6
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('capacity')->nullable(); // cupo máximo para ese bloque
            $table->timestamps();
            
            // Único para evitar duplicados
            $table->unique(['academy_id', 'day_of_week', 'start_time', 'end_time']);
            $table->index('academy_id');
            $table->index('area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_schedules');
    }
};
