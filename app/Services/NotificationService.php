<?php declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class NotificationService
{
    /**
     * Create a notification for a specific user.
     */
    public function createForUser(int $userId, string $title, string $message, string $type, ?array $data = null): Notification
    {
        return Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'target_type' => 'user',
            'target_id' => (string) $userId,
        ]);
    }

    /**
     * Create a notification for all users with a specific role.
     */
    public function createForRole(string $role, string $title, string $message, string $type, ?array $data = null): Notification
    {
        return Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'target_type' => 'role',
            'target_id' => $role,
        ]);
    }

    /**
     * Get notifications for a user (both direct and role-based).
     */
    public function getForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $user = User::findOrFail($userId);
        
        return Notification::where(function ($query) use ($userId, $user) {
            // Direct notifications to user
            $query->where(function ($q) use ($userId) {
                $q->where('target_type', 'user')
                  ->where('target_id', (string) $userId);
            })
            // Role-based notifications
            ->orWhere(function ($q) use ($user) {
                $q->where('target_type', 'role')
                  ->where('target_id', $user->role->value);
            });
        })
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getForUser($userId)->whereNull('read_at');
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::findOrFail($notificationId);
        
        // Verify the user has access to this notification
        if (!$this->userHasAccessToNotification($userId, $notification)) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        $user = User::findOrFail($userId);
        
        return Notification::where(function ($query) use ($userId, $user) {
            // Direct notifications to user
            $query->where(function ($q) use ($userId) {
                $q->where('target_type', 'user')
                  ->where('target_id', (string) $userId);
            })
            // Role-based notifications
            ->orWhere(function ($q) use ($user) {
                $q->where('target_type', 'role')
                  ->where('target_id', $user->role->value);
            });
        })
        ->whereNull('read_at')
        ->update(['read_at' => now()]);
    }

    /**
     * Get notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->getUnreadForUser($userId)->count();
    }

    /**
     * Check if user has access to a notification.
     */
    private function userHasAccessToNotification(int $userId, Notification $notification): bool
    {
        $user = User::findOrFail($userId);
        
        // Direct notification to user
        if ($notification->target_type === 'user' && $notification->target_id === (string) $userId) {
            return true;
        }
        
        // Role-based notification
        if ($notification->target_type === 'role' && $notification->target_id === $user->role->value) {
            return true;
        }
        
        return false;
    }

    /**
     * Create invitation pending notification for all admins.
     */
    public function notifyAdminsOfPendingInvitation(int $invitationId, string $inviterName, string $inviteeEmail, string $inviteeName): Notification
    {
        return $this->createForRole(
            'administrador',
            'Nueva Invitación Pendiente',
            "El usuario {$inviterName} ha invitado a {$inviteeName} ({$inviteeEmail}) al sistema.",
            'invitation_pending',
            [
                'invitation_id' => $invitationId,
                'inviter_name' => $inviterName,
                'invitee_email' => $inviteeEmail,
                'invitee_name' => $inviteeName,
            ]
        );
    }

    /**
     * Create invitation approved notification for the inviter.
     */
    public function notifyInviterOfApproval(int $inviterUserId, string $inviteeName, string $inviteeEmail): Notification
    {
        return $this->createForUser(
            $inviterUserId,
            'Invitación Aprobada',
            "Tu invitación a {$inviteeName} ({$inviteeEmail}) ha sido aprobada por el administrador.",
            'invitation_approved',
            [
                'invitee_name' => $inviteeName,
                'invitee_email' => $inviteeEmail,
            ]
        );
    }

    /**
     * Create invitation rejected notification for the inviter.
     */
    public function notifyInviterOfRejection(int $inviterUserId, string $inviteeName, string $inviteeEmail, string $rejectionReason): Notification
    {
        return $this->createForUser(
            $inviterUserId,
            'Invitación Rechazada',
            "Tu invitación a {$inviteeName} ({$inviteeEmail}) ha sido rechazada. Razón: {$rejectionReason}",
            'invitation_rejected',
            [
                'invitee_name' => $inviteeName,
                'invitee_email' => $inviteeEmail,
                'rejection_reason' => $rejectionReason,
            ]
        );
    }

    /**
     * Create registration pending notification for all admins.
     */
    public function notifyAdminsOfPendingRegistration(int $userId, string $userName, string $userEmail, ?string $aspiredRole, ?string $responsibleEmail): Notification
    {
        $message = "Nueva solicitud de registro de {$userName} ({$userEmail})";
        if ($aspiredRole) {
            $message .= " aspirando a ser {$aspiredRole}";
        }
        if ($responsibleEmail) {
            $message .= " con profesor responsable: {$responsibleEmail}";
        }
        $message .= ".";

        return $this->createForRole(
            'administrador',
            'Nueva Solicitud de Registro',
            $message,
            'registration_pending',
            [
                'user_id' => $userId,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'aspired_role' => $aspiredRole,
                'responsible_email' => $responsibleEmail,
            ]
        );
    }

    /**
     * Create user approval notification for the user.
     */
    public function notifyUserOfApproval(int $userId, string $userName, string $userRole): Notification
    {
        return $this->createForUser(
            $userId,
            'Cuenta Aprobada',
            "¡Felicidades {$userName}! Tu cuenta ha sido aprobada y ahora tienes el rol de {$userRole}. Ya puedes iniciar sesión y utilizar todas las funcionalidades del sistema.",
            'user_approved',
            [
                'user_name' => $userName,
                'user_role' => $userRole,
            ]
        );
    }

    /**
     * Create pending reservation notification for admins.
     */
    public function notifyAdminsOfPendingReservation(int $reservationId, string $userName, string $areaName, string $startsAt, string $endsAt): Notification
    {
        $message = "Nueva solicitud de reserva de {$userName} para el área {$areaName} el {$startsAt} hasta {$endsAt}.";

        return $this->createForRole(
            'administrador',
            'Nueva Solicitud de Reserva',
            $message,
            'reservation_pending',
            [
                'reservation_id' => $reservationId,
                'user_name' => $userName,
                'area_name' => $areaName,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );
    }

    /**
     * Create reservation approval notification for the user.
     */
    public function notifyUserOfReservationApproval(int $userId, string $areaName, string $startsAt, string $endsAt): Notification
    {
        return $this->createForUser(
            $userId,
            'Reserva Aprobada',
            "Tu reserva para el área {$areaName} del {$startsAt} al {$endsAt} ha sido aprobada.",
            'reservation_approved',
            [
                'area_name' => $areaName,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );
    }

    /**
     * Create reservation rejection notification for the user.
     */
    public function notifyUserOfReservationRejection(int $userId, string $areaName, string $startsAt, string $endsAt, string $reason): Notification
    {
        return $this->createForUser(
            $userId,
            'Reserva Rechazada',
            "Tu reserva para el área {$areaName} del {$startsAt} al {$endsAt} ha sido rechazada. Razón: {$reason}",
            'reservation_rejected',
            [
                'area_name' => $areaName,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create reservation cancellation notification for the user.
     */
    public function notifyUserOfReservationCancellation(int $userId, string $areaName, string $startsAt, string $reason = null): Notification
    {
        $message = "Tu reserva para el área {$areaName} del {$startsAt} ha sido cancelada.";
        if ($reason) {
            $message .= " Razón: {$reason}";
        }

        return $this->createForUser(
            $userId,
            'Reserva Cancelada',
            $message,
            'reservation_canceled',
            [
                'area_name' => $areaName,
                'starts_at' => $startsAt,
                'reason' => $reason,
            ]
        );
    }
}
