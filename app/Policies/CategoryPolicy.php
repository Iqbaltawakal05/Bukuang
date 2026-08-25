<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
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
     */
    public function view(User $user, Category $category): bool
    {
        return is_null($category->user_id) || $category->user_id === $user->id;
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
     * Only custom categories owned by the user can be updated. System categories cannot be modified.
     */
    public function update(User $user, Category $category): bool
    {
        return ! is_null($category->user_id) && $category->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     * Only custom categories owned by the user can be deleted. System categories cannot be deleted.
     */
    public function delete(User $user, Category $category): bool
    {
        return ! is_null($category->user_id) && $category->user_id === $user->id;
    }
}
