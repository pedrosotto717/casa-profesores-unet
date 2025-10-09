<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Models\UserBlock;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    /**
     * Determine whether the user can send messages in the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        // User must be a participant
        if (!$conversation->hasParticipant($user)) {
            return false;
        }

        // Get the other participant
        $otherParticipant = $conversation->otherParticipantFor($user);
        if (!$otherParticipant) {
            return false;
        }

        // Check if the other participant has blocked this user
        $isBlocked = UserBlock::where('blocker_id', $otherParticipant->id)
            ->where('blocked_id', $user->id)
            ->active()
            ->exists();

        return !$isBlocked;
    }

    /**
     * Determine whether the user can mark messages as read in the conversation.
     */
    public function markAsRead(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }
}
