<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InvitationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Create a new invitation.
     */
    public function createInvitation(array $data, int $inviterUserId): Invitation
    {
        return DB::transaction(function () use ($data, $inviterUserId) {
            // Validate that email doesn't exist in users table
            if (User::where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Este correo electrónico ya está registrado en el sistema.'
                ]);
            }

            // Validate that invitation doesn't already exist for this email
            if (Invitation::where('email', $data['email'])
                ->whereIn('status', [InvitationStatus::Pendiente, InvitationStatus::Aceptada])
                ->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Ya existe una invitación pendiente o aprobada para este correo electrónico.'
                ]);
            }

            // Create invitation
            $invitation = Invitation::create([
                'inviter_user_id' => $inviterUserId,
                'name' => $data['name'],
                'email' => $data['email'],
                'message' => $data['message'] ?? null,
                'token' => Str::random(64),
                'status' => InvitationStatus::Pendiente,
                'expires_at' => now()->addDays(30), // Invitations expire in 30 days
            ]);

            // Create notification for all admins
            $this->notificationService->notifyAdminsOfPendingInvitation(
                $invitation->id,
                $invitation->inviterUser->name,
                $invitation->email,
                $invitation->name
            );

            // Audit log
            $this->logInvitationAction($inviterUserId, $invitation->id, 'invitation_created', null, [
                'invitation_id' => $invitation->id,
                'invitee_name' => $invitation->name,
                'invitee_email' => $invitation->email,
                'message' => $invitation->message,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ]);

            // TODO: Send email to the person being invited with invitation details and link to accept
            // This should be implemented in a future iteration
            // Email should include: inviter name, invitation message, acceptance link, expiration date

            return $invitation;
        });
    }

    /**
     * Approve an invitation and create the user.
     */
    public function approveInvitation(int $invitationId, int $adminUserId): User
    {
        return DB::transaction(function () use ($invitationId, $adminUserId) {
            $invitation = Invitation::findOrFail($invitationId);

            if ($invitation->status !== InvitationStatus::Pendiente) {
                throw ValidationException::withMessages([
                    'invitation' => 'Esta invitación ya ha sido procesada.'
                ]);
            }

            if ($invitation->expires_at < now()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Esta invitación ha expirado.'
                ]);
            }

            // Create the user
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'role' => UserRole::Invitado, // New users start as "invitado"
                'password' => null, // No password for invited users initially
                'status' => UserStatus::Insolvente, // Default to insolvente (active but not up to date with contributions)
            ]);

            // Update invitation status
            $invitation->update([
                'status' => InvitationStatus::Aceptada,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);

            // Notify the inviter
            $this->notificationService->notifyInviterOfApproval(
                $invitation->inviter_user_id,
                $invitation->name,
                $invitation->email
            );


            // TODO: Send email to the person being invited notifying them of account creation
            // This should be implemented in a future iteration
            // Email should include: account details, login instructions, system welcome message

            
            // Audit logs
            $this->logInvitationAction($adminUserId, $invitation->id, 'invitation_approved', [
                'status' => 'pending',
            ], [
                'status' => 'approved',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
            ]);

            $this->logUserAction($adminUserId, $user->id, 'user_created', null, [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'created_via' => 'invitation_approval',
                'invitation_id' => $invitation->id,
                'created_at' => $user->created_at->toIso8601String(),
            ]);

            return $user;
        });
    }

    /**
     * Reject an invitation.
     */
    public function rejectInvitation(int $invitationId, int $adminUserId, string $rejectionReason): Invitation
    {
        return DB::transaction(function () use ($invitationId, $adminUserId, $rejectionReason) {
            $invitation = Invitation::findOrFail($invitationId);

            if ($invitation->status !== InvitationStatus::Pendiente) {
                throw ValidationException::withMessages([
                    'invitation' => 'Esta invitación ya ha sido procesada.'
                ]);
            }

            // Update invitation status
            $invitation->update([
                'status' => InvitationStatus::Rechazada,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            // Notify the inviter
            $this->notificationService->notifyInviterOfRejection(
                $invitation->inviter_user_id,
                $invitation->name,
                $invitation->email,
                $rejectionReason
            );

            // Audit log
            $this->logInvitationAction($adminUserId, $invitation->id, 'invitation_rejected', [
                'status' => 'pending',
            ], [
                'status' => 'rejected',
                'rejection_reason' => $rejectionReason,
            ]);

            return $invitation;
        });
    }

    /**
     * Get all pending invitations (admin only).
     */
    public function getPendingInvitations(): \Illuminate\Database\Eloquent\Collection
    {
        return Invitation::with(['inviterUser', 'reviewedBy'])
            ->where('status', InvitationStatus::Pendiente)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all invitations (admin only).
     */
    public function getAllInvitations(): \Illuminate\Database\Eloquent\Collection
    {
        return Invitation::with(['inviterUser', 'reviewedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Log invitation action in audit log.
     */
    private function logInvitationAction(int $userId, int $invitationId, string $action, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'Invitation',
            'entity_id' => $invitationId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }

    /**
     * Log user action in audit log.
     */
    private function logUserAction(int $adminUserId, int $targetUserId, string $action, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id' => $adminUserId,
            'entity_type' => 'User',
            'entity_id' => $targetUserId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
