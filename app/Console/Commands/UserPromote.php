<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

final class UserPromote extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:promote {email} {role=administrador} {--force}';
    
    /**
     * The console command description.
     */
    protected $description = 'Promote/demote a user to a specific role (CLI-only)';

    /**
     * Execute the console command.
     */
    public function handle(UserService $users): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('In production use --force to confirm.');
            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $role  = (string) $this->argument('role');

        $user = $users->promoteToRole($email, $role);
$this->info("User {$user->email} is now '{$user->role->value}'.");
        return self::SUCCESS;
    }
}
