<?php declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Academy;
use App\Models\AcademyStudent;
use App\Models\User;

final class AcademyStudentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Academy $academy): bool
    {
        // Admin can view all academy students
        if ($user->role === UserRole::Administrador) {
            return true;
        }

        // Instructor can only view students from their own academy
        if ($user->role === UserRole::Instructor) {
            return $academy->instructor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Academy $academy): bool
    {
        // Admin can create students in any academy
        if ($user->role === UserRole::Administrador) {
            return true;
        }

        // Instructor can only create students in their own academy
        if ($user->role === UserRole::Instructor) {
            return $academy->instructor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AcademyStudent $academyStudent): bool
    {
        // Admin can update any student
        if ($user->role === UserRole::Administrador) {
            return true;
        }

        // Instructor can only update students from their own academy
        if ($user->role === UserRole::Instructor) {
            return $academyStudent->academy->instructor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademyStudent $academyStudent): bool
    {
        // Admin can delete any student
        if ($user->role === UserRole::Administrador) {
            return true;
        }

        // Instructor can only delete students from their own academy
        if ($user->role === UserRole::Instructor) {
            return $academyStudent->academy->instructor_id === $user->id;
        }

        return false;
    }
}

