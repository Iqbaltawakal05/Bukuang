<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_user_can_list_default_and_own_custom_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Custom category owned by $user
        Category::create([
            'user_id' => $user->id,
            'name' => 'User Custom Expense',
            'type' => 'expense',
        ]);

        // Custom category owned by $otherUser (should not be listed for $user)
        Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Category',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Food & Dining']) // System default
            ->assertJsonFragment(['name' => 'User Custom Expense']) // User custom
            ->assertJsonMissing(['name' => 'Other User Category']); // Other user's custom
    }

    public function test_user_can_filter_categories_by_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories?type=income');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Salary'])
            ->assertJsonMissing(['name' => 'Food & Dining']);
    }

    public function test_user_can_create_custom_category(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Crypto Trading',
            'type' => 'income',
            'icon' => 'bitcoin',
            'color' => '#F7931A',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/categories', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Kategori berhasil dibuat.',
                'data' => [
                    'name' => 'Crypto Trading',
                    'type' => 'income',
                    'is_default' => false,
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Crypto Trading',
        ]);
    }

    public function test_user_can_update_own_custom_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Old Category',
            'type' => 'expense',
        ]);

        $payload = [
            'name' => 'Updated Category',
            'type' => 'expense',
            'icon' => 'edit',
            'color' => '#123456',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", $payload);

        $response->assertOk()
            ->assertJson([
                'message' => 'Kategori berhasil diperbarui.',
                'data' => ['name' => 'Updated Category'],
            ]);
    }

    public function test_user_cannot_update_system_default_category(): void
    {
        $user = User::factory()->create();
        $systemCategory = Category::whereNull('user_id')->first();

        $payload = [
            'name' => 'Modified System Category',
            'type' => 'expense',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/categories/{$systemCategory->id}", $payload);

        $response->assertForbidden();
    }

    public function test_user_cannot_update_other_users_custom_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Category',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/categories/{$otherCategory->id}", ['name' => 'Hacked', 'type' => 'expense']);

        $response->assertForbidden();
    }

    public function test_user_can_delete_own_custom_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Temporary Category',
            'type' => 'expense',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Kategori berhasil dihapus.']);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_system_default_category(): void
    {
        $user = User::factory()->create();
        $systemCategory = Category::whereNull('user_id')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/categories/{$systemCategory->id}");

        $response->assertForbidden();
    }
}
