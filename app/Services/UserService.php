<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\AspiredRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SendPulseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SendPulseService $sendPulseService
    ) {}
    /**
     * Register a new user with local authentication.
     * Implements the auto-registration flow with aspired_role and responsible_email.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            // SSO users won't have password; this is local registration
            'password' => Hash::make($data['password']),
            // Auto-registration creates user with 'usuario' role and 'aprobacion_pendiente' status
            'role'     => UserRole::Usuario,
            'status'   => UserStatus::AprobacionPendiente,
            'aspired_role' => isset($data['aspired_role']) ? AspiredRole::from($data['aspired_role']) : null,
            'responsible_email' => $data['responsible_email'] ?? null,
        ]);

        // Notify all admins about the new registration request
        $this->notificationService->notifyAdminsOfPendingRegistration(
            $user->id,
            $user->name,
            $user->email,
            $user->aspired_role?->value,
            $user->responsible_email
        );

        // TODO: Send email to the user confirming registration and explaining approval process
        // This should be implemented in a future iteration

        return [
            'user' => $user, 
            'message' => 'Usuario registrado exitosamente. Su cuenta está pendiente de aprobación administrativa.'
        ];
    }

    /**
     * Promote or demote a user to a specific role.
     */
    public function promoteToRole(string $email, string $role): User
    {
        // Get all valid role values from the enum
        $allowedRoles = array_column(UserRole::cases(), 'value');
        
        if (!in_array($role, $allowedRoles, true)) {
            throw ValidationException::withMessages(['role' => 'Invalid role.']);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->role = $role;
        $user->save();

        return $user;
    }

    /**
     * Create a new user with admin privileges.
     */
    public function createUser(array $data, int $adminUserId): User
    {
        return DB::transaction(function () use ($data, $adminUserId) {
            // Determine status based on provided data or default to 'insolvente'
            $status = isset($data['status']) ? UserStatus::from($data['status']) : UserStatus::Insolvente;

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => UserRole::from($data['role']),
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'status' => $status,
                'solvent_until' => $data['solvent_until'] ?? null,
            ]);

            // Audit log for user creation
            $this->logUserAction($adminUserId, $user->id, 'user_created', null, [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'solvent_until' => $user->solvent_until?->toDateString(),
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
            ]);

            return $user;
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data, int $adminUserId): User
    {
        return DB::transaction(function () use ($user, $data, $adminUserId) {
            $before = $this->getUserSnapshot($user);
            $changes = [];

            // Check for self-demotion protection
            if (isset($data['role']) && $user->id === $adminUserId) {
                $this->validateAdminSelfDemotion($user, $data['role']);
            }

            // Update basic fields
            if (isset($data['name'])) {
                $user->name = $data['name'];
                $changes['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $user->email = $data['email'];
                $changes['email'] = $data['email'];
            }

            if (isset($data['role'])) {
                $oldRole = $user->role;
                $user->role = UserRole::from($data['role']);
                $changes['role'] = $data['role'];
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $changes['password'] = '[HIDDEN]';
            }

            // Handle status
            $statusChanged = false;
            $oldStatus = $user->status;
            if (isset($data['status'])) {
                $user->status = UserStatus::from($data['status']);
                $changes['status'] = $data['status'];
                $statusChanged = true;

                // Auto-approval logic: If user was pending approval and is being approved,
                // automatically promote them to their aspired role
                if ($oldStatus === UserStatus::AprobacionPendiente && 
                    ($user->status === UserStatus::Solvente || $user->status === UserStatus::Insolvente) &&
                    $user->aspired_role !== null) {
                    
                    $oldRole = $user->role;
                    $user->role = UserRole::from($user->aspired_role->value);
                    $changes['role'] = $user->aspired_role->value;
                    
                    // Clear aspired_role since it's no longer needed
                    $user->aspired_role = null;
                    $changes['aspired_role'] = null;
                }
            }

            // Handle solvency date
            if (isset($data['solvent_until'])) {
                $user->solvent_until = $data['solvent_until'];
                $changes['solvent_until'] = $data['solvent_until'];
            }

            $user->save();
            $after = $this->getUserSnapshot($user);

            // Audit logs
            if (!empty($changes)) {
                $this->logUserAction($adminUserId, $user->id, 'user_updated', $before, $after);

                // Specific audit logs for role and solvency changes
                if (isset($changes['role'])) {
                    $this->logUserAction($adminUserId, $user->id, 'user_role_changed', 
                        ['role' => $oldRole->value], 
                        ['role' => $changes['role']]
                    );
                }

                // Special audit log for auto-approval
                if ($statusChanged && $oldStatus === UserStatus::AprobacionPendiente && 
                    ($user->status === UserStatus::Solvente || $user->status === UserStatus::Insolvente)) {
                    $this->logUserAction($adminUserId, $user->id, 'user_approved', 
                        [
                            'status' => $oldStatus->value,
                            'role' => $before['role'],
                            'aspired_role' => $before['aspired_role'] ?? null
                        ], 
                        [
                            'status' => $user->status->value,
                            'role' => $user->role->value,
                            'aspired_role' => null
                        ]
                    );
                }

                if ($statusChanged) {
                    $this->logUserAction($adminUserId, $user->id, 'user_status_changed',
                        [
                            'status' => $before['status'],
                            'solvent_until' => $before['solvent_until']
                        ],
                        [
                            'status' => $after['status'],
                            'solvent_until' => $after['solvent_until']
                        ]
                    );

                    // Only notify if user was pending approval and status changed to solvente or insolvente; or if status changed to rejected
                    $this->handleStatusChangeNotifications($user, $oldStatus, $user->status, $adminUserId);
                }
            }

            return $user;
        });
    }

    /**
     * Send invitation to user (stub implementation).
     */
    public function inviteUser(User $user, int $adminUserId): array
    {
        // Audit log for invitation request
        $this->logUserAction($adminUserId, $user->id, 'user_invite_requested', null, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // TODO: Implement actual email sending in future iteration
        return [
            'message' => 'Invitación registrada. El envío de correo será implementado en una futura iteración.',
            'user_id' => $user->id,
            'email' => $user->email,
        ];
    }

    /**
     * Calculate solvency based on provided values.
     */
    private function calculateSolvency(?bool $isSolvent, ?string $solventUntil): bool
    {
        // If solvent_until is provided, calculate based on date
        if ($solventUntil !== null) {
            return now()->toDateString() <= $solventUntil;
        }

        // Otherwise, use the provided is_solvent value or default to false
        return $isSolvent ?? false;
    }

    /**
     * Validate that admin is not demoting themselves if they're the only admin.
     */
    private function validateAdminSelfDemotion(User $user, string $newRole): void
    {
        if ($user->role === UserRole::Administrador && $newRole !== 'administrador') {
            $adminCount = User::where('role', UserRole::Administrador)->count();
            
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No se puede degradar el único administrador del sistema.'
                ]);
            }
        }
    }

    /**
     * Get a snapshot of user data for audit logging.
     */
    private function getUserSnapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'aspired_role' => $user->aspired_role?->value,
            'responsible_email' => $user->responsible_email,
            'solvent_until' => $user->solvent_until?->toDateString(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ];
    }

    /**
     * Handle notifications for status changes (approval/rejection).
     */
    private function handleStatusChangeNotifications(User $user, UserStatus $oldStatus, UserStatus $newStatus, int $adminUserId): void
    {
        // Only notify if user was pending approval and status changed
        if ($oldStatus === UserStatus::AprobacionPendiente) {
            if ($newStatus === UserStatus::Solvente || $newStatus === UserStatus::Insolvente) {
                // User was approved
                $this->notificationService->notifyUserOfApproval(
                    $user->id,
                    $user->name,
                    $user->role->value
                );

                // Send email to the user notifying them of approval
                $this->sendPulseService->sendAccountApprovedEmail(
                    $user->email,
                    $user->name,
                    $user->role->value
                );
            } else if ($newStatus === UserStatus::Rechazado) {
                // User was rejected - send rejection email
                $this->sendPulseService->sendAccountRejectedEmail(
                    $user->email,
                    $user->name
                );
            }
        }
    }

    /**
     * Delete a user (soft delete).
     */
    public function deleteUser(User $user, int $adminUserId): bool
    {
        return DB::transaction(function () use ($user, $adminUserId) {
            // Prevent admin from deleting themselves
            if ($user->id === $adminUserId) {
                throw ValidationException::withMessages([
                    'user' => 'No se puede eliminar su propia cuenta.'
                ]);
            }

            // Prevent deletion of the last admin
            if ($user->role === UserRole::Administrador) {
                $adminCount = User::where('role', UserRole::Administrador)->count();
                
                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'No se puede eliminar el único administrador del sistema.'
                    ]);
                }
            }

            // Get user snapshot before deletion for audit log
            $before = $this->getUserSnapshot($user);

            // Perform soft delete
            $deleted = $user->delete();

            if ($deleted) {
                // Audit log for user deletion
                $this->logUserAction($adminUserId, $user->id, 'user_deleted', $before, null);
            }

            return $deleted;
        });
    }

    /**
     * Update the authenticated user's own profile.
     * Only allows updating basic information (name, email, password).
     * Role and status cannot be modified through this method.
     */
    public function updateMe(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $before = $this->getUserSnapshot($user);
            $changes = [];

            // Update basic fields only
            if (isset($data['name'])) {
                $user->name = $data['name'];
                $changes['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $user->email = $data['email'];
                $changes['email'] = $data['email'];
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $changes['password'] = '[HIDDEN]';
            }

            $user->save();
            $after = $this->getUserSnapshot($user);

            // Audit log for self-update
            if (!empty($changes)) {
                $this->logUserAction($user->id, $user->id, 'user_self_updated', $before, $after);
            }

            return $user;
        });
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
