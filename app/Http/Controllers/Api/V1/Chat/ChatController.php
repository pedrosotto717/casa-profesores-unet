<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\CreateConversationRequest;
use App\Http\Requests\Chat\MarkAsReadRequest;
use App\Http\Requests\Chat\SearchUsersRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\Chat\ConversationResource;
use App\Http\Resources\Chat\ConversationWithDetailsResource;
use App\Http\Resources\Chat\ConversationMessageResource;
use App\Http\Resources\Chat\UserSearchResource;
use App\Http\Resources\Chat\UnreadSummaryResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Search users for starting conversations.
     */
    public function searchUsers(SearchUsersRequest $request): JsonResponse
    {
        $users = $this->chatService->searchUsers(
            $request->validated()['q'],
            $request->user()->id
        );

        return response()->json([
            'data' => UserSearchResource::collection($users),
        ]);
    }

    /**
     * Create or get a conversation with another user.
     */
    public function createConversation(CreateConversationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $currentUser = $request->user();

        // Find the peer user
        if (isset($data['peer_email'])) {
            $peerUser = User::where('email', $data['peer_email'])->first();
        } else {
            $peerUser = User::find($data['peer_id']);
        }

        if (!$peerUser) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Create or find the conversation
        $conversation = $this->chatService->findOrCreateConversation(
            $currentUser->id,
            $peerUser->id
        );

        return response()->json([
            'data' => new ConversationResource($conversation),
        ], 201);
    }

    /**
     * List conversations for the authenticated user.
     */
    public function listConversations(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);
        $page = (int) $request->get('page', 1);

        $conversations = $this->chatService->listConversationsWithCounters(
            $request->user()->id,
            $perPage,
            $page
        );

        // Convert to models for resource transformation
        $conversationModels = collect($conversations)->map(function ($data) {
            $conversation = Conversation::find($data['id']);
            $conversation->unread_count = $data['unread_count'];
            return $conversation;
        });

        return response()->json([
            'data' => ConversationWithDetailsResource::collection($conversationModels),
        ]);
    }

    /**
     * Get messages for a conversation.
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check authorization
        if (!$conversation->hasParticipant($request->user())) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }
        $limit = (int) $request->get('limit', 25);
        $beforeId = $request->get('before_id') ? (int) $request->get('before_id') : null;

        $result = $this->chatService->getMessages($conversation, $limit, $beforeId);

        return response()->json([
            'data' => ConversationMessageResource::collection($result['messages']),
            'has_more' => $result['has_more'],
            'next_before_id' => $result['next_before_id'],
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check authorization
        if (!$conversation->hasParticipant($request->user())) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            $message = $this->chatService->sendMessage(
                $conversation,
                $request->user()->id,
                $request->validated()['body']
            );

            return response()->json([
                'data' => new ConversationMessageResource($message),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Mark messages as read in a conversation.
     */
    public function markAsRead(MarkAsReadRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check authorization
        if (!$conversation->hasParticipant($request->user())) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $data = $request->validated();
        $this->chatService->markRead(
            $conversation,
            $request->user()->id,
            $data['up_to_message_id'] ?? null
        );

        return response()->json([
            'message' => 'Messages marked as read.',
        ]);
    }

    /**
     * Get unread summary for the authenticated user.
     */
    public function getUnreadSummary(Request $request): JsonResponse
    {
        $summary = $this->chatService->getUnreadSummary($request->user()->id);

        return response()->json([
            'data' => new UnreadSummaryResource((object) $summary),
        ]);
    }
}
