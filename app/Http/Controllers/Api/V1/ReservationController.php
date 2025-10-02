<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelReservationRequest;
use App\Http\Requests\GetAvailabilityRequest;
use App\Http\Requests\RejectReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService
    ) {}

    /**
     * Create a new reservation.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createReservation(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reserva creada exitosamente. Está pendiente de aprobación administrativa.'
        ], 201);
    }

    /**
     * List reservations.
     * - Users can see only their own reservations
     * - Admins can see all reservations with filters
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Reservation::with(['requester', 'area', 'approver']);

        // If user is not admin, only show their own reservations
        if ($user->role->value !== 'administrador') {
            $query->byUser($user->id);
        } else {
            // Admin can apply filters
            if ($request->has('user_id')) {
                $query->byUser($request->user_id);
            }

            if ($request->has('area_id')) {
                $query->byArea($request->area_id);
            }

            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('from') && $request->has('to')) {
                $query->byDateRange($request->from, $request->to);
            }

            // Search by user name or area name (admin only)
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('requester', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('area', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        $reservations = $query->paginate($request->get('per_page', 15));

        return ReservationResource::collection($reservations);
    }

    /**
     * Update a reservation (only if pending).
     */
    public function update(UpdateReservationRequest $request, int $id): JsonResponse
    {
        $reservation = $this->reservationService->updateReservation(
            $id,
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reserva actualizada exitosamente.'
        ]);
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(CancelReservationRequest $request, int $id): JsonResponse
    {
        $isAdmin = $request->user()->role->value === 'administrador';
        
        $reservation = $this->reservationService->cancelReservation(
            $id,
            $request->user()->id,
            $request->validated()['reason'] ?? null,
            $isAdmin
        );

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reserva cancelada exitosamente.'
        ]);
    }

    /**
     * Approve a reservation (admin only).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $reservation = $this->reservationService->approveReservation(
            $id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reserva aprobada exitosamente.'
        ]);
    }

    /**
     * Reject a reservation (admin only).
     */
    public function reject(RejectReservationRequest $request, int $id): JsonResponse
    {
        $reservation = $this->reservationService->rejectReservation(
            $id,
            $request->user()->id,
            $request->validated()['reason']
        );

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
            'message' => 'Reserva rechazada exitosamente.'
        ]);
    }

    /**
     * Get availability for an area (public endpoint).
     */
    public function availability(GetAvailabilityRequest $request): JsonResponse
    {
        $availability = $this->reservationService->getAreaAvailability(
            (int) $request->validated()['area_id'],
            $request->validated()['from'],
            $request->validated()['to'],
            $request->validated()['slot_minutes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }
}
