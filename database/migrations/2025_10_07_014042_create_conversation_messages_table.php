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
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->timestamps();

            // Indexes for performance
            $table->index(['conversation_id', 'id'], 'conversation_messages_conversation_id_index');
            $table->index(['conversation_id', 'receiver_id', 'id'], 'conversation_messages_unread_index');
            $table->index(['sender_id'], 'conversation_messages_sender_index');
            $table->index(['receiver_id'], 'conversation_messages_receiver_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};