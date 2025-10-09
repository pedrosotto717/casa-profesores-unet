<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;
    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->chatService = app(ChatService::class);
    }

    public function test_can_create_conversation(): void
    {
        $conversation = $this->chatService->findOrCreateConversation(
            $this->user1->id,
            $this->user2->id
        );

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertTrue($conversation->hasParticipant($this->user1));
        $this->assertTrue($conversation->hasParticipant($this->user2));
    }

    public function test_can_send_message(): void
    {
        $conversation = $this->chatService->findOrCreateConversation(
            $this->user1->id,
            $this->user2->id
        );

        $message = $this->chatService->sendMessage(
            $conversation,
            $this->user1->id,
            'Hello, how are you?'
        );

        $this->assertInstanceOf(ConversationMessage::class, $message);
        $this->assertEquals($this->user1->id, $message->sender_id);
        $this->assertEquals($this->user2->id, $message->receiver_id);
        $this->assertEquals('Hello, how are you?', $message->body);
    }

    public function test_cannot_send_message_to_blocked_user(): void
    {
        // User2 blocks User1
        UserBlock::create([
            'blocker_id' => $this->user2->id,
            'blocked_id' => $this->user1->id,
        ]);

        $conversation = $this->chatService->findOrCreateConversation(
            $this->user1->id,
            $this->user2->id
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You cannot send messages to this user as they have blocked you.');

        $this->chatService->sendMessage(
            $conversation,
            $this->user1->id,
            'This should fail'
        );
    }

    public function test_can_mark_messages_as_read(): void
    {
        $conversation = $this->chatService->findOrCreateConversation(
            $this->user1->id,
            $this->user2->id
        );

        // Send a message
        $message = $this->chatService->sendMessage(
            $conversation,
            $this->user1->id,
            'Hello'
        );

        // Check unread count before marking as read
        $unreadCount = $this->chatService->unreadCount($conversation, $this->user2->id);
        $this->assertEquals(1, $unreadCount);

        // Mark as read
        $this->chatService->markRead($conversation, $this->user2->id, $message->id);

        // Check unread count after marking as read
        $unreadCount = $this->chatService->unreadCount($conversation, $this->user2->id);
        $this->assertEquals(0, $unreadCount);
    }

    public function test_can_search_users(): void
    {
        $users = $this->chatService->searchUsers('test', $this->user1->id);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
        $this->assertNotContains($this->user1->id, $users->pluck('id'));
    }

    public function test_can_create_user_block(): void
    {
        $block = $this->chatService->createBlock(
            $this->user1->id,
            $this->user2->id,
            'Spam messages'
        );

        $this->assertInstanceOf(UserBlock::class, $block);
        $this->assertEquals($this->user1->id, $block->blocker_id);
        $this->assertEquals($this->user2->id, $block->blocked_id);
        $this->assertEquals('Spam messages', $block->reason);
    }

    public function test_cannot_block_self(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You cannot block yourself.');

        $this->chatService->createBlock(
            $this->user1->id,
            $this->user1->id,
            'This should fail'
        );
    }

    public function test_can_remove_user_block(): void
    {
        // Create a block
        $this->chatService->createBlock(
            $this->user1->id,
            $this->user2->id
        );

        // Remove the block
        $removed = $this->chatService->removeBlock(
            $this->user1->id,
            $this->user2->id
        );

        $this->assertTrue($removed);
    }

    public function test_rate_limiting_prevents_spam(): void
    {
        $conversation = $this->chatService->findOrCreateConversation(
            $this->user1->id,
            $this->user2->id
        );

        // Clear any existing rate limits
        RateLimiter::clear("chat:global:{$this->user1->id}");
        RateLimiter::clear("chat:conversation:{$conversation->id}:{$this->user1->id}");

        // Send 30 messages to hit per-conversation rate limit
        for ($i = 0; $i < 30; $i++) {
            $this->chatService->sendMessage(
                $conversation,
                $this->user1->id,
                "Message {$i}"
            );
        }

        // The 31st message should fail due to per-conversation rate limit
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate limit exceeded for this conversation. Please wait before sending more messages.');

        $this->chatService->sendMessage(
            $conversation,
            $this->user1->id,
            'This should fail due to rate limit'
        );
    }
}
