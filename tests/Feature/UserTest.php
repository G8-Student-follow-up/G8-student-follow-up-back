<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_admin_can_list_users()
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['current_page', 'data'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved successfully',
            ]);
    }

    public function test_trainer_can_list_users()
    {
        $response = $this->actingAs($this->trainer, 'sanctum')
            ->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_list_users()
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    // ─── SHOW ───────────────────────────────────────────────────────

    public function test_admin_can_show_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => ['id' => $user->id, 'name' => $user->name],
            ]);
    }

    public function test_trainer_can_show_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->trainer, 'sanctum')
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200);
    }

    public function test_show_user_fails_for_nonexistent()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/users/99999');

        $response->assertStatus(404);
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function test_admin_can_update_user_name_and_email()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => ['name' => 'Updated Name', 'email' => 'updated@example.com'],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_update_user_fails_for_nonexistent()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/users/99999', [
                'name' => 'Nobody',
            ]);

        $response->assertStatus(404);
    }

    // ─── DESTROY ────────────────────────────────────────────────────

    public function test_admin_can_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_user_fails_for_nonexistent()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/users/99999');

        $response->assertStatus(404);
    }
}
