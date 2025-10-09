<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'sender_id' => 'integer',
            'receiver_id' => 'integer',
        ];
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who received this message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope to get messages before a specific message ID (for pagination).
     */
    public function scopeBeforeId($query, int $messageId)
    {
        return $query->where('id', '<', $messageId);
    }

    /**
     * Scope to get messages for a specific conversation.
     */
    public function scopeForConversation($query, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Scope to get unread messages for a specific user.
     */
    public function scopeUnreadForUser($query, int $userId, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId)
                    ->where('receiver_id', $userId)
                    ->whereDoesntHave('conversation.reads', function ($q) use ($userId) {
                        $q->where('user_id', $userId)
                          ->whereColumn('last_read_message_id', '>=', 'conversation_messages.id');
                    });
    }
}
