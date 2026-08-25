<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Get all categories accessible by the user (System default + Custom categories).
     */
    public function getCategoriesForUser(User $user, ?string $type = null): Collection
    {
        $query = Category::forUser($user->id);

        if ($type && in_array($type, ['income', 'expense'])) {
            $query->where('type', $type);
        }

        return $query->orderBy('user_id', 'asc') // System default first (null)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Create a new custom category for the user.
     */
    public function createCategory(User $user, array $data): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
        ]);
    }

    /**
     * Update a user's custom category.
     */
    public function updateCategory(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? $category->icon,
            'color' => $data['color'] ?? $category->color,
        ]);

        return $category;
    }

    /**
     * Soft delete a user's custom category.
     */
    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }
}
