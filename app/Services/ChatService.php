<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationRead;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class ChatService
{
    /**
     * Find or create a conversation between two users.
     */
    public function findOrCreateConversation(int $userIdA, int $userIdB): Conversation
    {
        // Ensure consistent ordering: user_one_id < user_two_id
        $userOneId = min($userIdA, $userIdB);
        $userTwoId = max($userIdA, $userIdB);

        return Conversation::firstOrCreate(
            [
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]
        );
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Conversation $conversation, int $senderId, string $body): ConversationMessage
    {
        // Validate sender is a participant
        if (!$conversation->hasParticipant(User::find($senderId))) {
            throw new \InvalidArgumentException('Sender is not a participant in this conversation.');
        }

        // Get the other participant (receiver)
        $sender = User::find($senderId);
        $receiver = $conversation->otherParticipantFor($sender);

        if (!$receiver) {
            throw new \InvalidArgumentException('Cannot determine receiver for this conversation.');
        }

        // Check if receiver has blocked the sender
        $isBlocked = UserBlock::where('blocker_id', $receiver->id)
            ->where('blocked_id', $senderId)
            ->active()
            ->exists();

        if ($isBlocked) {
            throw new \InvalidArgumentException('You cannot send messages to this user as they have blocked you.');
        }

        // Apply rate limiting
        $this->checkRateLimits($senderId, $conversation->id);

        // Create the message
        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'receiver_id' => $receiver->id,
            'body' => trim($body),
        ]);

        return $message;
    }

    /**
     * Mark messages as read for a user in a conversation.
     */
    public function markRead(Conversation $conversation, int $userId, ?int $upToMessageId = null): void
    {
        if (!$conversation->hasParticipant(User::find($userId))) {
            throw new \InvalidArgumentException('User is not a participant in this conversation.');
        }

        // If no specific message ID provided, mark all messages as read
        if ($upToMessageId === null) {
            $lastMessage = $conversation->messages()
                ->where('receiver_id', $userId)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastMessage) {
                $upToMessageId = $lastMessage->id;
            }
        }

        // Update or create read status
        ConversationRead::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
            ],
            [
                'last_read_message_id' => $upToMessageId,
                'last_read_at' => now(),
            ]
        );
    }

    /**
     * Get unread count for a user in a conversation.
     */
    public function unreadCount(Conversation $conversation, int $userId): int
    {
        $lastReadMessageId = ConversationRead::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->value('last_read_message_id') ?? 0;

        return ConversationMessage::where('conversation_id', $conversation->id)
            ->where('receiver_id', $userId)
            ->where('id', '>', $lastReadMessageId)
            ->count();
    }

    /**
     * List conversations for a user with counters and last message info.
     */
    public function listConversationsWithCounters(int $userId, int $limit = 20, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;

        // Get conversations with unread counts
        $conversations = Conversation::forUser($userId)
            ->with(['userOne', 'userTwo', 'messages' => function ($query) {
                $query->orderBy('id', 'desc')->limit(1);
            }])
            ->leftJoin('conversation_reads', function ($join) use ($userId) {
                $join->on('conversations.id', '=', 'conversation_reads.conversation_id')
                     ->where('conversation_reads.user_id', '=', $userId);
            })
            ->leftJoin('conversation_messages as unread_messages', function ($join) use ($userId) {
                $join->on('conversations.id', '=', 'unread_messages.conversation_id')
                     ->where('unread_messages.receiver_id', '=', $userId)
                     ->whereRaw('unread_messages.id > COALESCE(conversation_reads.last_read_message_id, 0)');
            })
            ->select([
                'conversations.*',
                DB::raw('COUNT(unread_messages.id) as unread_count')
            ])
            ->groupBy('conversations.id')
            ->orderByRaw('(SELECT MAX(id) FROM conversation_messages WHERE conversation_id = conversations.id) DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Add unread_count to each conversation
        $conversations->each(function ($conversation) {
            $conversation->unread_count = (int) $conversation->unread_count;
        });

        return $conversations->toArray();
    }

    /**
     * Get messages for a conversation with pagination.
     */
    public function getMessages(Conversation $conversation, int $limit = 25, ?int $beforeId = null): array
    {
        $query = $conversation->messages()
            ->with(['sender', 'receiver'])
            ->orderBy('id', 'desc');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($limit + 1)->get();
        $hasMore = $messages->count() > $limit;

        if ($hasMore) {
            $messages->pop(); // Remove the extra message
        }

        $nextBeforeId = $hasMore ? $messages->last()->id : null;

        return [
            'messages' => $messages->reverse()->values(),
            'has_more' => $hasMore,
            'next_before_id' => $nextBeforeId,
        ];
    }

    /**
     * Get unread summary for a user.
     */
    public function getUnreadSummary(int $userId): array
    {
        $conversations = Conversation::forUser($userId)
            ->leftJoin('conversation_reads', function ($join) use ($userId) {
                $join->on('conversations.id', '=', 'conversation_reads.conversation_id')
                     ->where('conversation_reads.user_id', '=', $userId);
            })
            ->leftJoin('conversation_messages', function ($join) use ($userId) {
                $join->on('conversations.id', '=', 'conversation_messages.conversation_id')
                     ->where('conversation_messages.receiver_id', '=', $userId)
                     ->whereRaw('conversation_messages.id > COALESCE(conversation_reads.last_read_message_id, 0)');
            })
            ->select([
                'conversations.id as conversation_id',
                DB::raw('COUNT(conversation_messages.id) as unread_count')
            ])
            ->groupBy('conversations.id')
            ->having('unread_count', '>', 0)
            ->get();

        $totalUnread = $conversations->sum('unread_count');

        return [
            'total_unread' => $totalUnread,
            'conversations' => $conversations->map(function ($item) {
                return [
                    'conversation_id' => $item->conversation_id,
                    'unread_count' => (int) $item->unread_count,
                ];
            })->toArray(),
        ];
    }

    /**
     * Search users for starting conversations.
     */
    public function searchUsers(string $query, int $currentUserId, int $limit = 10): Collection
    {
        return User::where('id', '!=', $currentUserId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Create a user block.
     */
    public function createBlock(int $blockerId, int $blockedId, ?string $reason = null, ?string $expiresAt = null): UserBlock
    {
        if ($blockerId === $blockedId) {
            throw new \InvalidArgumentException('You cannot block yourself.');
        }

        return UserBlock::create([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
            'reason' => $reason,
            'expires_at' => $expiresAt ? now()->parse($expiresAt) : null,
        ]);
    }

    /**
     * Remove a user block.
     */
    public function removeBlock(int $blockerId, int $blockedId): bool
    {
        return UserBlock::where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->delete() > 0;
    }

    /**
     * Get blocks created by a user.
     */
    public function getUserBlocks(int $userId): Collection
    {
        return UserBlock::where('blocker_id', $userId)
            ->with('blocked')
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check rate limits for message sending.
     */
    private function checkRateLimits(int $userId, int $conversationId): void
    {
        // Global rate limit: 60 messages per minute
        $globalKey = "chat:global:{$userId}";
        if (RateLimiter::tooManyAttempts($globalKey, 60)) {
            throw new \InvalidArgumentException('Rate limit exceeded. Please wait before sending more messages.');
        }

        // Per-conversation rate limit: 30 messages per minute
        $conversationKey = "chat:conversation:{$conversationId}:{$userId}";
        if (RateLimiter::tooManyAttempts($conversationKey, 30)) {
            throw new \InvalidArgumentException('Rate limit exceeded for this conversation. Please wait before sending more messages.');
        }

        // Hit the rate limiters
        RateLimiter::hit($globalKey, 60); // 1 minute
        RateLimiter::hit($conversationKey, 60); // 1 minute
    }
}
