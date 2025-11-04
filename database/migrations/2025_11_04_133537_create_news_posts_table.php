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
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique()->index();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->longText('content');
            $table->foreignId('author_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
