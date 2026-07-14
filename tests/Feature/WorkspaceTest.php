<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api')->plainTextToken;
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_workspaces()
    {
        Workspace::factory()->count(3)->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/workspaces');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['current_page', 'data'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Workspaces retrieved successfully',
            ]);
    }

    public function test_unauthenticated_user_cannot_list_workspaces()
    {
        $response = $this->getJson('/api/workspaces');

        $response->assertStatus(401);
    }

    public function test_workspace_list_is_paginated()
    {
        Workspace::factory()->count(15)->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/workspaces');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data.data'));
    }

    // ─── STORE ──────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_workspace()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/workspaces', [
                'name' => 'My Test Workspace',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'created_by'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Workspace created successfully',
                'data' => ['name' => 'My Test Workspace'],
            ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'My Test Workspace',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_workspace_fails_without_name()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/workspaces', []);

        $response->assertStatus(422);
    }

    public function test_create_workspace_sets_correct_creator()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/workspaces', [
                'name' => 'My Workspace',
            ]);

        $workspaceId = $response->json('data.id');
        $workspace = Workspace::find($workspaceId);

        $this->assertEquals($this->user->id, $workspace->created_by);
    }

    // ─── SHOW ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_show_workspace()
    {
        $workspace = Workspace::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$workspace->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace retrieved successfully',
                'data' => ['id' => $workspace->id, 'name' => $workspace->name],
            ]);
    }

    public function test_show_workspace_includes_creator_and_boards()
    {
        $workspace = Workspace::factory()
            ->hasBoards(2)
            ->create(['created_by' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$workspace->id}");

        $response->assertStatus(200);
        $this->assertArrayHasKey('creator', $response->json('data'));
        $this->assertArrayHasKey('boards', $response->json('data'));
        $this->assertCount(2, $response->json('data.boards'));
    }

    public function test_show_workspace_fails_for_nonexistent_workspace()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/workspaces/99999');

        $response->assertStatus(404);
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_workspace()
    {
        $workspace = Workspace::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Original Name',
        ]);

        $response = $this->withToken($this->token)
            ->putJson("/api/workspaces/{$workspace->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace updated successfully',
                'data' => ['name' => 'Updated Name'],
            ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_workspace_without_name_is_ok()
    {
        $workspace = Workspace::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Original',
        ]);

        $response = $this->withToken($this->token)
            ->putJson("/api/workspaces/{$workspace->id}", []);

        $response->assertStatus(200);
        $this->assertEquals('Original', $response->json('data.name'));
    }

    // ─── DESTROY ────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_workspace()
    {
        $workspace = Workspace::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/workspaces/{$workspace->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace deleted successfully',
            ]);

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }

    public function test_delete_workspace_cascades_to_boards()
    {
        $workspace = Workspace::factory()
            ->hasBoards(3)
            ->create(['created_by' => $this->user->id]);

        $boardIds = $workspace->boards->pluck('id');

        $this->withToken($this->token)
            ->deleteJson("/api/workspaces/{$workspace->id}");

        foreach ($boardIds as $boardId) {
            $this->assertDatabaseMissing('boards', ['id' => $boardId]);
        }
    }

    public function test_delete_nonexistent_workspace_returns_404()
    {
        $response = $this->withToken($this->token)
            ->deleteJson('/api/workspaces/99999');

        $response->assertStatus(404);
    }
}
