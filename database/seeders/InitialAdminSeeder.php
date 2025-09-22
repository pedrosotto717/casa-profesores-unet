<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class InitialAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('app.initial_admin_email');
        $name  = config('app.initial_admin_name', 'CPU Admin');
        $pass  = config('app.initial_admin_password');

        if (!$email || !$pass) return;

        $user = User::firstOrCreate(['email' => $email], [
            'name'     => $name,
            'password' => Hash::make($pass),
            'role'     => UserRole::Docente, // create as docente for security
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'sso_uid' => null,
        ]);
    }
}
