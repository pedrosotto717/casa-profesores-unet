<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\TipoFactura;
use App\Enums\EstatusFactura;
use App\Enums\EstatusPago;
use App\Models\AcademySchedule;
use App\Models\Area;
use App\Models\AreaSchedule;
use App\Models\AuditLog;
use App\Models\Factura;
use App\Models\Reservation;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReservationService
{
    private NotificationService $notificationService;
    private PricingService $pricingService;

    public function __construct(NotificationService $notificationService, PricingService $pricingService)
    {
        $this->notificationService = $notificationService;
        $this->pricingService = $pricingService;
    }

    /**
     * Create a new reservation.
     */
    public function createReservation(array $data, int $userId): Reservation
    {
        return DB::transaction(function () use ($data, $userId) {
            $user = User::findOrFail($userId);
            $area = Area::findOrFail($data['area_id']);

            // Validate user can make reservations
            $this->validateUserCanReserve($user);

            // Validate area is reservable
            $this->validateAreaIsReservable($area);

            // Validate time windows
            $this->validateTimeWindows($data['starts_at'], $data['ends_at']);

            // Check for conflicts with approved reservations and academy schedules
            $this->validateNoConflicts($data['area_id'], $data['starts_at'], $data['ends_at']);

            // Check if area is free for agremiados and user is a professor
            $isFreeForAgremiados = $area->es_gratis_agremiados && $user->role === UserRole::Profesor;
            
            // Determine payment status (always pending for approval, but payment status varies)
            $estatusPago = $isFreeForAgremiados ? EstatusPago::Gratis : EstatusPago::Pendiente;

            // Create the reservation
            $reservation = Reservation::create([
                'requester_id' => $userId,
                'area_id' => $data['area_id'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => ReservationStatus::Pendiente, // Always pending for admin approval
                'estatus_pago' => $estatusPago,
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // If area is free for agremiados, create automatic factura (but still needs approval)
            if ($isFreeForAgremiados) {
                $factura = Factura::create([
                    'user_id' => $userId,
                    'tipo' => TipoFactura::PagoReserva,
                    'monto' => 0.00,
                    'moneda' => $area->moneda,
                    'fecha_emision' => now(),
                    'fecha_pago' => now(),
                    'estatus_pago' => EstatusFactura::Pagado,
                    'descripcion' => sprintf(
                        'Reserva gratuita %s - %s (Agremiado)',
                        $area->name,
                        $reservation->starts_at->format('d/m/Y')
                    ),
                ]);

                // Update reservation with factura_id
                $reservation->update(['factura_id' => $factura->id]);
            }

            // Log audit
            $this->logReservationAction($userId, $reservation->id, 'reservation_created', null, [
                'reservation_id' => $reservation->id,
                'area_id' => $reservation->area_id,
                'starts_at' => $reservation->starts_at->toIso8601String(),
                'ends_at' => $reservation->ends_at->toIso8601String(),
                'status' => $reservation->status->value,
                'estatus_pago' => $reservation->estatus_pago->value,
                'title' => $reservation->title,
                'is_free_for_agremiados' => $isFreeForAgremiados,
            ]);

            // Always notify admins (all reservations need approval)
            $this->notificationService->notifyAdminsOfPendingReservation(
                $reservation->id,
                $user->name,
                $area->name,
                $reservation->starts_at->format('Y-m-d H:i:s'),
                $reservation->ends_at->format('Y-m-d H:i:s')
            );

            return $reservation->load(['requester', 'area']);
        });
    }

    /**
     * Update a reservation (only if pending).
     */
    public function updateReservation(int $reservationId, array $data, int $userId): Reservation
    {
        return DB::transaction(function () use ($reservationId, $data, $userId) {
            $reservation = Reservation::findOrFail($reservationId);
            $user = User::findOrFail($userId);
            $this->validateUserCanReserve($user);

            $oldData = $this->getReservationSnapshot($reservation);

            // Update fields
            if (isset($data['starts_at'])) {
                $reservation->starts_at = $data['starts_at'];
            }
            if (isset($data['ends_at'])) {
                $reservation->ends_at = $data['ends_at'];
            }
            if (isset($data['title'])) {
                $reservation->title = $data['title'];
            }
            if (isset($data['notes'])) {
                $reservation->notes = $data['notes'];
            }

            // Validate time windows if times changed
            if (isset($data['starts_at']) || isset($data['ends_at'])) {
                $this->validateTimeWindows($reservation->starts_at->toDateTimeString(), $reservation->ends_at->toDateTimeString());
                $this->validateNoConflicts($reservation->area_id, $reservation->starts_at->toDateTimeString(), $reservation->ends_at->toDateTimeString(), $reservationId);
            }

            $reservation->save();

            // Log audit
            $this->logReservationAction($userId, $reservation->id, 'reservation_updated', $oldData, [
                'reservation_id' => $reservation->id,
                'area_id' => $reservation->area_id,
                'starts_at' => $reservation->starts_at->toIso8601String(),
                'ends_at' => $reservation->ends_at->toIso8601String(),
                'note' => $reservation->note,
            ]);

            return $reservation->load(['requester', 'area']);
        });
    }

    /**
     * Cancel a reservation.
     */
    public function cancelReservation(int $reservationId, int $userId, string $reason = null, bool $isAdmin = false): Reservation
    {
        return DB::transaction(function () use ($reservationId, $userId, $reason, $isAdmin) {
            $reservation = Reservation::findOrFail($reservationId);

            // Validate permissions
            if (!$isAdmin && $reservation->requester_id !== $userId) {
                throw ValidationException::withMessages([
                    'reservation' => 'No tienes permisos para cancelar esta reserva.'
                ]);
            }

            // Validate cancellation rules
            if (!$isAdmin && $reservation->isApproved() && !$reservation->canBeCanceledByUser()) {
                throw ValidationException::withMessages([
                    'reservation' => 'No puedes cancelar esta reserva. Debe cancelarse con al menos 24 horas de anticipación.'
                ]);
            }

            $oldData = $this->getReservationSnapshot($reservation);

            $reservation->update([
                'status' => ReservationStatus::Cancelada,
                'decision_reason' => $reason,
                'approved_by' => $isAdmin ? $userId : null,
                'reviewed_at' => now(),
            ]);

            // Log audit
            $this->logReservationAction($userId, $reservation->id, 'reservation_canceled', $oldData, [
                'reservation_id' => $reservation->id,
                'reason' => $reason,
                'canceled_by_admin' => $isAdmin,
            ]);

            // Notify user if canceled by admin
            if ($isAdmin) {
                $this->notificationService->notifyUserOfReservationCancellation(
                    $reservation->user_id,
                    $reservation->area->name,
                    $reservation->starts_at->format('Y-m-d H:i:s'),
                    $reason
                );
            }

            return $reservation->load(['requester', 'area']);
        });
    }

    /**
     * Approve a reservation.
     */
    public function approveReservation(int $reservationId, int $adminUserId): Reservation
    {
        return DB::transaction(function () use ($reservationId, $adminUserId) {
            $reservation = Reservation::findOrFail($reservationId);

            if (!$reservation->isPending()) {
                throw ValidationException::withMessages([
                    'reservation' => 'Solo se pueden aprobar reservas pendientes.'
                ]);
            }

            // Re-validate no conflicts before approval
            $this->validateNoConflicts($reservation->area_id, $reservation->starts_at->toDateTimeString(), $reservation->ends_at->toDateTimeString(), $reservationId);

            $oldData = $this->getReservationSnapshot($reservation);

            $reservation->update([
                'status' => ReservationStatus::Aprobada,
                'approved_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);

            // Log audit
            $this->logReservationAction($adminUserId, $reservation->id, 'reservation_approved', $oldData, [
                'reservation_id' => $reservation->id,
                'area_id' => $reservation->area_id,
                'starts_at' => $reservation->starts_at->toIso8601String(),
                'ends_at' => $reservation->ends_at->toIso8601String(),
            ]);

            // Notify user
            $this->notificationService->notifyUserOfReservationApproval(
                $reservation->requester_id,
                $reservation->area->name,
                $reservation->starts_at->format('Y-m-d H:i:s'),
                $reservation->ends_at->format('Y-m-d H:i:s')
            );

            return $reservation->load(['requester', 'area']);
        });
    }

    /**
     * Reject a reservation.
     */
    public function rejectReservation(int $reservationId, int $adminUserId, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservationId, $adminUserId, $reason) {
            $reservation = Reservation::findOrFail($reservationId);

            if (!$reservation->isPending()) {
                throw ValidationException::withMessages([
                    'reservation' => 'Solo se pueden rechazar reservas pendientes.'
                ]);
            }

            $oldData = $this->getReservationSnapshot($reservation);

            $reservation->update([
                'status' => ReservationStatus::Rechazada,
                'decision_reason' => $reason,
                'approved_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);

            // Log audit
            $this->logReservationAction($adminUserId, $reservation->id, 'reservation_rejected', $oldData, [
                'reservation_id' => $reservation->id,
                'area_id' => $reservation->area_id,
                'reason' => $reason,
            ]);

            // Notify user
            $this->notificationService->notifyUserOfReservationRejection(
                $reservation->requester_id,
                $reservation->area->name,
                $reservation->starts_at->format('Y-m-d H:i:s'),
                $reservation->ends_at->format('Y-m-d H:i:s'),
                $reason
            );

            return $reservation->load(['requester', 'area']);
        });
    }

    /**
     * Get availability for an area.
     */
    public function getAreaAvailability(int $areaId, string $from, string $to, int $slotMinutes = null): array
    {
        $area = Area::findOrFail($areaId);
        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);

        // Get operating hours for the area
        $operatingHours = $this->getOperatingHours($areaId, $fromDate, $toDate);

        // Get blocks (approved reservations + academy schedules)
        $blocks = $this->getBlocks($areaId, $fromDate, $toDate);

        // Calculate free slots
        $freeSlots = $this->calculateFreeSlots($operatingHours, $blocks, $slotMinutes);

        return [
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'is_reservable' => $area->is_reservable,
            ],
            'range' => [
                'from' => $fromDate->toIso8601String(),
                'to' => $toDate->toIso8601String(),
            ],
            'operating_hours' => $operatingHours,
            'blocks' => $blocks,
            'free' => $freeSlots,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate user can make reservations.
     */
    private function validateUserCanReserve(User $user): void
    {
        // Check role - Profesor, Estudiante, Invitado and Administrador can make reservations
        $allowedRoles = [UserRole::Profesor, UserRole::Estudiante, UserRole::Invitado, UserRole::Administrador];
        if (!in_array($user->role, $allowedRoles)) {
            throw ValidationException::withMessages([
                'user' => 'Tu rol no permite realizar reservas. Solo profesores, estudiantes, invitados y administradores pueden solicitar reservas.'
            ]);
        }

        // Check status
        if ($user->status !== UserStatus::Solvente) {
            throw ValidationException::withMessages([
                'user' => 'Debes estar solvente para realizar reservas.'
            ]);
        }

        // For students and guests, verify responsible professor exists
        if (in_array($user->role, [UserRole::Estudiante, UserRole::Invitado]) && $user->responsible_email) {
            $responsibleProfessor = User::where('email', $user->responsible_email)
                                      ->where('role', UserRole::Profesor)
                                      ->where('status', UserStatus::Solvente)
                                      ->first();

            if (!$responsibleProfessor) {
                throw ValidationException::withMessages([
                    'user' => 'Tu profesor responsable no está registrado o no está activo en el sistema.'
                ]);
            }
        }
    }

    /**
     * Validate area is reservable.
     */
    private function validateAreaIsReservable(Area $area): void
    {
        if (!$area->is_reservable) {
            throw ValidationException::withMessages([
                'area' => 'Esta área no está disponible para reservas.'
            ]);
        }
    }

    /**
     * Validate time windows.
     */
    private function validateTimeWindows(string $startsAt, string $endsAt): void
    {
        $start = Carbon::parse($startsAt);
        $end = Carbon::parse($endsAt);

        // Validate end is after start
        if ($end <= $start) {
            throw ValidationException::withMessages([
                'ends_at' => 'La hora de fin debe ser posterior a la hora de inicio.'
            ]);
        }

        // Validate minimum advance notice
        $minAdvanceHours = config('reservations.min_advance_hours', 2);
        if (now()->diffInHours($start, false) < $minAdvanceHours) {
            throw ValidationException::withMessages([
                'starts_at' => "Debes reservar con al menos {$minAdvanceHours} horas de anticipación."
            ]);
        }

        // Validate maximum advance notice
        $maxAdvanceDays = config('reservations.max_advance_days', 30);
        if ($start->diffInDays(now(), false) > $maxAdvanceDays) {
            throw ValidationException::withMessages([
                'starts_at' => "No puedes reservar con más de {$maxAdvanceDays} días de anticipación."
            ]);
        }

        // Validate maximum duration
        $maxDurationHours = config('reservations.max_duration_hours', 8);
        if ($start->diffInHours($end) > $maxDurationHours) {
            throw ValidationException::withMessages([
                'ends_at' => "La duración máxima de una reserva es de {$maxDurationHours} horas."
            ]);
        }
    }

    /**
     * Validate no conflicts with approved reservations and academy schedules.
     */
    private function validateNoConflicts(int $areaId, string $startsAt, string $endsAt, int $excludeReservationId = null): void
    {
        // Check approved reservations
        $conflictingReservations = Reservation::overlapping($startsAt, $endsAt, $areaId);
        if ($excludeReservationId) {
            $conflictingReservations->where('id', '!=', $excludeReservationId);
        }

        if ($conflictingReservations->exists()) {
            throw ValidationException::withMessages([
                'time' => 'El horario solicitado tiene conflictos con reservas existentes.'
            ]);
        }

        // Check academy schedules
        $conflictingAcademies = AcademySchedule::where('area_id', $areaId)
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where(function ($q) use ($startsAt, $endsAt) {
                    $q->where('start_time', '<', $endsAt)
                      ->where('end_time', '>', $startsAt);
                });
            })
            ->exists();

        if ($conflictingAcademies) {
            throw ValidationException::withMessages([
                'time' => 'El horario solicitado tiene conflictos con sesiones de academias.'
            ]);
        }
    }

    /**
     * Get operating hours for an area.
     */
    private function getOperatingHours(int $areaId, Carbon $from, Carbon $to): array
    {
        $schedules = AreaSchedule::where('area_id', $areaId)
            ->where('is_open', true)
            ->get();

        $operatingHours = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $dayOfWeek = $current->dayOfWeekIso; // 1 = Monday, 7 = Sunday
            $daySchedule = $schedules->where('day_of_week', $dayOfWeek)->first();

            if ($daySchedule) {
                $operatingHours[] = [
                    'date' => $current->toDateString(),
                    'windows' => [
                        [
                            'start' => $current->copy()->setTimeFromTimeString($daySchedule->start_time->format('H:i'))->toIso8601String(),
                            'end' => $current->copy()->setTimeFromTimeString($daySchedule->end_time->format('H:i'))->toIso8601String(),
                        ]
                    ]
                ];
            }

            $current->addDay();
        }

        return $operatingHours;
    }

    /**
     * Get blocks (approved reservations + academy schedules).
     */
    private function getBlocks(int $areaId, Carbon $from, Carbon $to): array
    {
        $blocks = [];

        // Get approved reservations
        $reservations = Reservation::where('area_id', $areaId)
            ->where('status', ReservationStatus::Aprobada)
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('starts_at', [$from, $to])
                      ->orWhereBetween('ends_at', [$from, $to])
                      ->orWhere(function ($q) use ($from, $to) {
                          $q->where('starts_at', '<=', $from)
                            ->where('ends_at', '>=', $to);
                      });
            })
            ->get();

        foreach ($reservations as $reservation) {
            $blocks[] = [
                'start' => $reservation->starts_at->toIso8601String(),
                'end' => $reservation->ends_at->toIso8601String(),
                'kind' => 'reservation',
                'ref' => $reservation->id,
            ];
        }

        // Get academy schedules
        $academySchedules = AcademySchedule::where('area_id', $areaId)
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('start_time', [$from, $to])
                      ->orWhereBetween('end_time', [$from, $to])
                      ->orWhere(function ($q) use ($from, $to) {
                          $q->where('start_time', '<=', $from)
                            ->where('end_time', '>=', $to);
                      });
            })
            ->get();

        foreach ($academySchedules as $schedule) {
            $blocks[] = [
                'start' => $schedule->start_time->toIso8601String(),
                'end' => $schedule->end_time->toIso8601String(),
                'kind' => 'academy',
                'ref' => $schedule->academy_id,
            ];
        }

        return $blocks;
    }

    /**
     * Calculate free slots from operating hours and blocks.
     */
    private function calculateFreeSlots(array $operatingHours, array $blocks, int $slotMinutes = null): array
    {
        $freeSlots = [];

        foreach ($operatingHours as $day) {
            $dayStart = Carbon::parse($day['windows'][0]['start']);
            $dayEnd = Carbon::parse($day['windows'][0]['end']);

            // Get blocks for this day
            $dayBlocks = array_filter($blocks, function ($block) use ($dayStart, $dayEnd) {
                $blockStart = Carbon::parse($block['start']);
                $blockEnd = Carbon::parse($block['end']);
                return $blockStart->lt($dayEnd) && $blockEnd->gt($dayStart);
            });

            // Sort blocks by start time
            usort($dayBlocks, function ($a, $b) {
                return Carbon::parse($a['start'])->lt(Carbon::parse($b['start'])) ? -1 : 1;
            });

            // Calculate free intervals
            $current = $dayStart->copy();
            foreach ($dayBlocks as $block) {
                $blockStart = Carbon::parse($block['start']);
                $blockEnd = Carbon::parse($block['end']);

                if ($current->lt($blockStart)) {
                    $freeSlots[] = [
                        'start' => $current->toIso8601String(),
                        'end' => $blockStart->toIso8601String(),
                    ];
                }

                $current = $current->max($blockEnd);
            }

            // Add remaining time if any
            if ($current->lt($dayEnd)) {
                $freeSlots[] = [
                    'start' => $current->toIso8601String(),
                    'end' => $dayEnd->toIso8601String(),
                ];
            }
        }

        // Convert to slots if requested
        if ($slotMinutes) {
            $slots = [];
            foreach ($freeSlots as $slot) {
                $start = Carbon::parse($slot['start']);
                $end = Carbon::parse($slot['end']);

                while ($start->copy()->addMinutes($slotMinutes)->lte($end)) {
                    $slots[] = [
                        'start' => $start->toIso8601String(),
                        'end' => $start->copy()->addMinutes($slotMinutes)->toIso8601String(),
                    ];
                    $start->addMinutes($slotMinutes);
                }
            }
            return $slots;
        }

        return $freeSlots;
    }

    /**
     * Get reservation snapshot for audit logging.
     */
    private function getReservationSnapshot(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'requester_id' => $reservation->requester_id,
            'area_id' => $reservation->area_id,
            'starts_at' => $reservation->starts_at->toIso8601String(),
            'ends_at' => $reservation->ends_at->toIso8601String(),
            'status' => $reservation->status->value,
            'title' => $reservation->title,
            'notes' => $reservation->notes,
            'decision_reason' => $reservation->decision_reason,
            'approved_by' => $reservation->approved_by,
            'reviewed_at' => $reservation->reviewed_at?->toIso8601String(),
        ];
    }

    /**
     * Log reservation action for audit.
     */
    private function logReservationAction(int $userId, int $reservationId, string $action, array $oldData = null, array $newData = null): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'target_user_id' => $oldData['requester_id'] ?? $newData['requester_id'] ?? null,
            'action' => $action,
            'entity_type' => 'Reservation',
            'entity_id' => $reservationId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Mark a reservation as paid and create associated factura.
     */
    public function markReservationAsPaid(int $reservationId, array $paymentData): Reservation
    {
        return DB::transaction(function () use ($reservationId, $paymentData) {
            $reservation = Reservation::with(['area', 'requester'])->findOrFail($reservationId);

            // Verify reservation is approved
            if (!$reservation->isApproved()) {
                throw ValidationException::withMessages([
                    'reservation' => ['La reserva debe estar aprobada antes de marcarla como pagada.'],
                ]);
            }

            // Calculate cost
            $costData = $this->pricingService->calculateReservationCost($reservation);

            // Create factura
            $factura = Factura::create([
                'user_id' => $reservation->requester_id,
                'tipo' => TipoFactura::PagoReserva,
                'monto' => $costData['costo_final'],
                'moneda' => $paymentData['moneda'] ?? $costData['moneda'],
                'fecha_emision' => now(),
                'fecha_pago' => Carbon::parse($paymentData['fecha_pago']),
                'estatus_pago' => EstatusFactura::Pagado,
                'descripcion' => sprintf(
                    'Pago reserva %s - %s',
                    $reservation->area->name,
                    $reservation->starts_at->format('d/m/Y')
                ),
            ]);

            // Update reservation
            $reservation->update([
                'factura_id' => $factura->id,
                'estatus_pago' => EstatusPago::Pagado,
            ]);

            // Log the action
            AuditLog::create([
                'user_id' => 1, // System user for automatic operations
                'entity_type' => Reservation::class,
                'entity_id' => $reservation->id,
                'action' => 'reservation_marked_as_paid',
                'after' => [
                    'factura_id' => $factura->id,
                    'monto' => $costData['costo_final'],
                    'moneda' => $paymentData['moneda'] ?? $costData['moneda'],
                ],
            ]);

            return $reservation->fresh(['factura', 'area', 'requester']);
        });
    }
}
