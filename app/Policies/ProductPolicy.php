<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
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
     * Determine whether the user can publish/unpublish a product.
     */
    public function publish(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can feature/unfeature a product.
     */
    public function feature(User $user): bool
    {
        return $user->isAdmin();
    }
}
