<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can perform any action on the model.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Admin users can do anything
        if ($user->isAdmin()) {
            return true;
        }

        // Non-admin users cannot perform any actions
        return false;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can disable another user.
     */
    public function disable(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can enable another user.
     */
    public function enable(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reset another user's password.
     */
    public function resetPassword(User $user): bool
    {
        return $user->isAdmin();
    }
}
