<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\CreateBlockRequest;
use App\Http\Resources\Chat\UserBlockResource;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * List blocks created by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $blocks = $this->chatService->getUserBlocks($request->user()->id);

        return response()->json([
            'data' => UserBlockResource::collection($blocks),
        ]);
    }

    /**
     * Create a new block.
     */
    public function store(CreateBlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $block = $this->chatService->createBlock(
                $request->user()->id,
                $data['blocked_user_id'],
                $data['reason'] ?? null,
                $data['expires_at'] ?? null
            );

            return response()->json([
                'data' => new UserBlockResource($block->load('blocked')),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove a block.
     */
    public function destroy(Request $request, int $blockedUserId): JsonResponse
    {
        $removed = $this->chatService->removeBlock(
            $request->user()->id,
            $blockedUserId
        );

        if (!$removed) {
            return response()->json([
                'message' => 'Block not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Block removed successfully.',
        ]);
    }
}
