<?php declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserBlock;

class UserBlockPolicy
{
    /**
     * Determine whether the user can view their own blocks.
     */
    public function viewOwn(User $user): bool
    {
        return true; // Users can always view their own blocks
    }

    /**
     * Determine whether the user can create a block.
     */
    public function create(User $user, User $blockedUser): bool
    {
        // Users cannot block themselves
        return $user->id !== $blockedUser->id;
    }

    /**
     * Determine whether the user can delete a block.
     */
    public function delete(User $user, UserBlock $userBlock): bool
    {
        // Users can only delete blocks they created
        return $user->id === $userBlock->blocker_id;
    }
}
