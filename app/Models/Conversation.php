<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
    ];

    protected function casts(): array
    {
        return [
            'user_one_id' => 'integer',
            'user_two_id' => 'integer',
        ];
    }

    /**
     * Get the first user in the conversation.
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Get the second user in the conversation.
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('id', 'desc');
    }

    /**
     * Get read status for this conversation.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class);
    }

    /**
     * Get the other participant in the conversation for a given user.
     */
    public function otherParticipantFor(User $user): ?User
    {
        if ($this->user_one_id === $user->id) {
            return $this->userTwo;
        }

        if ($this->user_two_id === $user->id) {
            return $this->userOne;
        }

        return null;
    }

    /**
     * Get all participants in the conversation.
     */
    public function participants(): array
    {
        return [$this->userOne, $this->userTwo];
    }

    /**
     * Scope to get conversations for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_one_id', $userId)
                    ->orWhere('user_two_id', $userId);
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(User $user): bool
    {
        return $this->user_one_id === $user->id || $this->user_two_id === $user->id;
    }
}
