<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserService
{
    /**
     * Register a new user with local authentication.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            // SSO users won't have password; this is local registration
            'password' => Hash::make($data['password']),
            // safe initial role (NOT admin). Using enum value
            'role'     => UserRole::Usuario,
        ]);

        // SPA token with Sanctum
        $token = $user->createToken('spa')->plainTextToken;

        return [
            'user' => $user, 
            'token' => $token,
            'message' => 'Usuario registrado exitosamente.'
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
            // Calculate solvency based on solvent_until if provided
            $isSolvent = $this->calculateSolvency($data['is_solvent'] ?? null, $data['solvent_until'] ?? null);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => UserRole::from($data['role']),
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'is_solvent' => $isSolvent,
                'solvent_until' => $data['solvent_until'] ?? null,
            ]);

            // Audit log for user creation
            $this->logUserAction($adminUserId, $user->id, 'user_created', null, [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_solvent' => $user->is_solvent,
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

            // Handle solvency
            $solvencyChanged = false;
            if (isset($data['solvent_until'])) {
                $user->solvent_until = $data['solvent_until'];
                $changes['solvent_until'] = $data['solvent_until'];
                $solvencyChanged = true;
            }

            if (isset($data['is_solvent']) || isset($data['solvent_until'])) {
                $newSolvency = $this->calculateSolvency($data['is_solvent'] ?? null, $data['solvent_until'] ?? null);
                if ($user->is_solvent !== $newSolvency) {
                    $user->is_solvent = $newSolvency;
                    $changes['is_solvent'] = $newSolvency;
                    $solvencyChanged = true;
                }
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

                if ($solvencyChanged) {
                    $this->logUserAction($adminUserId, $user->id, 'user_solvency_changed',
                        [
                            'is_solvent' => $before['is_solvent'],
                            'solvent_until' => $before['solvent_until']
                        ],
                        [
                            'is_solvent' => $after['is_solvent'],
                            'solvent_until' => $after['solvent_until']
                        ]
                    );
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
            'is_solvent' => $user->is_solvent,
            'solvent_until' => $user->solvent_until?->toDateString(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ];
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
