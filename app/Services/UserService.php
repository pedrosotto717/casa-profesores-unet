<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'role'     => UserRole::Docente,
        ]);

        // SPA token with Sanctum
        $token = $user->createToken('spa')->plainTextToken;

        return ['user' => $user, 'token' => $token];
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
}
