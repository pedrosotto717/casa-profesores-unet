<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChangeUserStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:change-status 
                            {email : The email of the user to update}
                            {status : The new status (aprobacion_pendiente, solvente, insolvente)}
                            {--admin-email= : Email of the admin performing this action (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the status of a user by email address';

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $status = $this->argument('status');
        $adminEmail = $this->option('admin-email');

        try {
            // Validate status
            $this->validateStatus($status);

            // Find the user
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("User with email '{$email}' not found.");
                return Command::FAILURE;
            }

            // Get admin user for audit logging
            $adminUser = $this->getAdminUser($adminEmail);

            // Show current status
            $this->info("Current user status: {$user->status->value}");
            $this->info("Current user role: {$user->role->value}");

            // Confirm the change
            if (!$this->confirm("Are you sure you want to change the status to '{$status}'?")) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }

            // Perform the status change
            $result = $this->changeUserStatus($user, $status, $adminUser);

            if ($result['success']) {
                $this->info("✅ User status changed successfully!");
                $this->info("New status: {$result['new_status']}");
                if ($result['role_changed']) {
                    $this->info("Role automatically updated to: {$result['new_role']}");
                }
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to change user status: {$result['error']}");
                return Command::FAILURE;
            }

        } catch (ValidationException $e) {
            $this->error("❌ Validation error: " . implode(', ', $e->errors()['status'] ?? []));
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("❌ An error occurred: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Validate the status parameter.
     */
    private function validateStatus(string $status): void
    {
        $validStatuses = array_column(UserStatus::cases(), 'value');
        
        if (!in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status. Valid options are: " . implode(', ', $validStatuses)
            ]);
        }
    }

    /**
     * Get admin user for audit logging.
     */
    private function getAdminUser(?string $adminEmail): ?User
    {
        if ($adminEmail) {
            return User::where('email', $adminEmail)->first();
        }

        // Try to find any admin user
        return User::where('role', 'administrador')->first();
    }

    /**
     * Change the user status using the UserService.
     */
    private function changeUserStatus(User $user, string $status, ?User $adminUser): array
    {
        return DB::transaction(function () use ($user, $status, $adminUser) {
            try {
                $oldStatus = $user->status;
                $oldRole = $user->role;
                $oldAspiredRole = $user->aspired_role;

                // Use UserService to update the user
                $adminUserId = $adminUser?->id ?? 1; // Fallback to user ID 1 if no admin found
                
                $updatedUser = $this->userService->updateUser(
                    $user,
                    ['status' => $status],
                    $adminUserId
                );

                $result = [
                    'success' => true,
                    'new_status' => $updatedUser->status->value,
                    'role_changed' => false,
                    'new_role' => null
                ];

                // Check if role was automatically changed (auto-approval)
                if ($oldStatus === UserStatus::AprobacionPendiente && 
                    ($updatedUser->status === UserStatus::Solvente || $updatedUser->status === UserStatus::Insolvente) &&
                    $oldAspiredRole !== null) {
                    
                    $result['role_changed'] = true;
                    $result['new_role'] = $updatedUser->role->value;
                }

                return $result;

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }
}
