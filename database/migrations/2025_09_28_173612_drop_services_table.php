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
        Schema::dropIfExists('services');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('requires_reservation')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['area_id', 'is_active']);
        });
    }
};
