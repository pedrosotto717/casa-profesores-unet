<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvitationRequest;
use App\Http\Requests\RejectInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService
    ) {}

    /**
     * Create a new invitation.
     */
    public function store(CreateInvitationRequest $request): JsonResponse
    {
        $invitation = $this->invitationService->createInvitation(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new InvitationResource($invitation),
            'message' => 'Invitación creada exitosamente. El administrador revisará tu solicitud.',
            'meta' => [
                'version' => 'v1',
            ],
        ], 201);
    }

    /**
     * Get all invitations (admin sees all, professor sees only their own).
     */
    public function index(Request $request): JsonResponse
    {
        $invitations = $this->invitationService->getAllInvitations($request->user());

        return response()->json([
            'success' => true,
            'data' => InvitationResource::collection($invitations),
            'meta' => [
                'version' => 'v1',
                'total' => $invitations->count(),
                'pending' => $invitations->where('status', 'pending')->count(),
            ],
        ]);
    }

    /**
     * Get pending invitations (admin only).
     */
    public function pending(Request $request): JsonResponse
    {
        $invitations = $this->invitationService->getPendingInvitations();

        return response()->json([
            'success' => true,
            'data' => InvitationResource::collection($invitations),
            'meta' => [
                'version' => 'v1',
                'count' => $invitations->count(),
            ],
        ]);
    }

    /**
     * Approve an invitation (admin only).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $this->invitationService->approveInvitation($id, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'invitation_id' => $id,
            ],
            'message' => 'Invitación aprobada y usuario creado exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Reject an invitation (admin only).
     */
    public function reject(RejectInvitationRequest $request, int $id): JsonResponse
    {
        $invitation = $this->invitationService->rejectInvitation(
            $id,
            $request->user()->id,
            $request->validated()['rejection_reason']
        );

        return response()->json([
            'success' => true,
            'data' => new InvitationResource($invitation),
            'message' => 'Invitación rechazada exitosamente.',
            'meta' => [
                'version' => 'v1',
            ],
        ]);
    }
}
