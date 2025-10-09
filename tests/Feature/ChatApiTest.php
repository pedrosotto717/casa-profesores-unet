<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
    }

    public function test_can_search_users(): void
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/chat/users/search?q=test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'has_blocked_me',
                        'i_blocked_them',
                    ]
                ]
            ]);
    }

    public function test_can_create_conversation_by_email(): void
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/conversations', [
                'peer_email' => $this->user2->email,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_can_create_conversation_by_id(): void
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/conversations', [
                'peer_id' => $this->user2->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_cannot_create_conversation_with_self(): void
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/conversations', [
                'peer_id' => $this->user1->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['peer_id']);
    }

    public function test_can_list_conversations(): void
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'other_participant',
                        'last_message',
                        'unread_count',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_can_send_message(): void
    {
        // First create a conversation
        $conversationResponse = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/conversations', [
                'peer_id' => $this->user2->id,
            ]);

        $conversationId = $conversationResponse->json('data.id');

        // Send a message
        $response = $this->actingAs($this->user1)
            ->postJson("/api/v1/chat/conversations/{$conversationId}/messages", [
                'body' => 'Hello, how are you?',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'conversation_id',
                    'sender_id',
                    'receiver_id',
                    'body',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_can_get_messages(): void
    {
        // First create a conversation
        $conversationResponse = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/conversations', [
                'peer_id' => $this->user2->id,
            ]);

        $conversationId = $conversationResponse->json('data.id');

        // Get messages
        $response = $this->actingAs($this->user1)
            ->getJson("/api/v1/chat/conversations/{$conversationId}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'has_more',
                'next_before_id',
            ]);
    }

    public function test_can_mark_messages_as_read(): void
    {
        // First create a conversation
        $conversationResponse = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/conversations', [
                'peer_id' => $this->user2->id,
            ]);

        $conversationId = $conversationResponse->json('data.id');

        // Mark as read
        $response = $this->actingAs($this->user1)
            ->postJson("/api/v1/chat/conversations/{$conversationId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Messages marked as read.',
            ]);
    }

    public function test_can_get_unread_summary(): void
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/chat/unread/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_unread',
                    'conversations',
                ]
            ]);
    }

    public function test_can_create_user_block(): void
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/blocks', [
                'blocked_user_id' => $this->user2->id,
                'reason' => 'Spam messages',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'blocker_id',
                    'blocked_id',
                    'blocked_user',
                    'reason',
                    'expires_at',
                    'is_active',
                    'created_at',
                ]
            ]);
    }

    public function test_can_list_user_blocks(): void
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/chat/blocks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'blocker_id',
                        'blocked_id',
                        'blocked_user',
                        'reason',
                        'expires_at',
                        'is_active',
                        'created_at',
                    ]
                ]
            ]);
    }

    public function test_can_remove_user_block(): void
    {
        // First create a block
        $this->actingAs($this->user1)
            ->postJson('/api/v1/chat/blocks', [
                'blocked_user_id' => $this->user2->id,
            ]);

        // Remove the block
        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/v1/chat/blocks/{$this->user2->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Block removed successfully.',
            ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/chat/conversations');
        $response->assertStatus(401);
    }
}
