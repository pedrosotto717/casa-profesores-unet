<?php declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Beneficiario;
use App\Models\User;

final class BeneficiarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo Admin puede ver la lista completa (index)
        return $user->role === UserRole::Administrador;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Beneficiario $beneficiario): bool
    {
        // Admin o el Profesor dueño
        return $user->role === UserRole::Administrador || 
               $user->id === $beneficiario->agremiado_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo Profesores pueden crear
        return $user->role === UserRole::Profesor;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Beneficiario $beneficiario): bool
    {
        // Admin o el Profesor dueño
        return $user->role === UserRole::Administrador || 
               $user->id === $beneficiario->agremiado_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Beneficiario $beneficiario): bool
    {
        // Admin o el Profesor dueño
        return $user->role === UserRole::Administrador || 
               $user->id === $beneficiario->agremiado_id;
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Beneficiario $beneficiario): bool
    {
        return $user->role === UserRole::Administrador;
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, Beneficiario $beneficiario): bool
    {
        return $user->role === UserRole::Administrador;
    }
}
