<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api')->plainTextToken;
        $this->workspace = Workspace::factory()->create([
            'created_by' => $this->user->id,
        ]);
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_boards_in_workspace()
    {
        Board::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$this->workspace->id}/boards");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['current_page', 'data'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Boards retrieved successfully',
            ]);
    }

    public function test_unauthenticated_user_cannot_list_boards()
    {
        $response = $this->getJson("/api/workspaces/{$this->workspace->id}/boards");

        $response->assertStatus(401);
    }

    public function test_board_list_is_scoped_to_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();

        Board::factory()->count(2)->create(['workspace_id' => $this->workspace->id]);
        Board::factory()->count(3)->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$this->workspace->id}/boards");

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_list_boards_fails_for_nonexistent_workspace()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/workspaces/99999/boards');

        $response->assertStatus(404);
    }

    // ─── STORE ──────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_board()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/workspaces/{$this->workspace->id}/boards", [
                'title' => 'My Test Board',
                'is_favorite' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'workspace_id', 'title', 'is_favorite', 'is_archived'],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Board created successfully',
                'data' => [
                    'title' => 'My Test Board',
                    'workspace_id' => $this->workspace->id,
                    'is_favorite' => true,
                    'is_archived' => false,
                ],
            ]);

        $this->assertDatabaseHas('boards', [
            'title' => 'My Test Board',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    public function test_create_board_fails_without_title()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/workspaces/{$this->workspace->id}/boards", []);

        $response->assertStatus(422);
    }

    public function test_create_board_with_defaults()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/workspaces/{$this->workspace->id}/boards", [
                'title' => 'Default Board',
            ]);

        $response->assertStatus(201);
        $this->assertFalse($response->json('data.is_favorite'));
        $this->assertFalse($response->json('data.is_archived'));
    }

    // ─── SHOW ───────────────────────────────────────────────────────

    public function test_authenticated_user_can_show_board()
    {
        $board = Board::factory()->create([
            'workspace_id' => $this->workspace->id,
            'title' => 'My Board',
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Board retrieved successfully',
                'data' => ['id' => $board->id, 'title' => 'My Board'],
            ]);
    }

    public function test_show_board_includes_classes()
    {
        $board = Board::factory()
            ->hasClasses(2)
            ->create(['workspace_id' => $this->workspace->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}");

        $response->assertStatus(200);
        $this->assertArrayHasKey('classes', $response->json('data'));
        $this->assertCount(2, $response->json('data.classes'));
    }

    public function test_show_board_fails_if_board_not_in_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        $board = Board::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}");

        $response->assertStatus(404);
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_board()
    {
        $board = Board::factory()->create([
            'workspace_id' => $this->workspace->id,
            'title' => 'Original Title',
            'is_favorite' => false,
        ]);

        $response = $this->withToken($this->token)
            ->putJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}", [
                'title' => 'Updated Title',
                'is_favorite' => true,
                'is_archived' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Board updated successfully',
                'data' => [
                    'title' => 'Updated Title',
                    'is_favorite' => true,
                    'is_archived' => true,
                ],
            ]);

        $this->assertDatabaseHas('boards', [
            'id' => $board->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_board_fails_if_board_not_in_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        $board = Board::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->withToken($this->token)
            ->putJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}", [
                'title' => 'Hacked',
            ]);

        $response->assertStatus(404);
    }

    // ─── DESTROY ────────────────────────────────────────────────────

    public function test_authenticated_user_can_delete_board()
    {
        $board = Board::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Board deleted successfully',
            ]);

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_delete_board_fails_if_board_not_in_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        $board = Board::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('boards', ['id' => $board->id]);
    }

    public function test_delete_board_cascades_to_classes()
    {
        $board = Board::factory()
            ->hasClasses(2)
            ->create(['workspace_id' => $this->workspace->id]);

        $classIds = $board->classes->pluck('id');

        $this->withToken($this->token)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/boards/{$board->id}");

        foreach ($classIds as $classId) {
            $this->assertDatabaseMissing('classes', ['id' => $classId]);
        }
    }
}
