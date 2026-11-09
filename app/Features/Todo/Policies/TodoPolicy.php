<?php

namespace App\Features\Todo\Policies;

use App\Features\Todo\Models\Todo;
use App\Models\User;

class TodoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Admin can view any user's todo (read-only); member can only view their own.
     */
    public function view(User $user, Todo $todo): bool
    {
        return $user->isAdmin() || $user->id === $todo->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only the owner can update their todo (admin cannot edit).
     */
    public function update(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Only the owner can delete their todo (admin cannot delete).
     */
    public function delete(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }
}

