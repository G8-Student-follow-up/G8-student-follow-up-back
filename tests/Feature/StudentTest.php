<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $trainer;
    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);

        // Build hierarchy: workspace → board → class
        $workspace = Workspace::factory()->create(['created_by' => $this->admin->id]);
        $board = Board::factory()->create(['workspace_id' => $workspace->id]);
        $this->classRoom = ClassRoom::factory()->create(['board_id' => $board->id]);
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_admin_can_list_students()
    {
        Student::factory()->count(3)->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/students');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_trainer_can_list_students()
    {
        Student::factory()->count(2)->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->actingAs($this->trainer, 'sanctum')
            ->getJson('/api/students');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_unauthenticated_user_cannot_list_students()
    {
        $response = $this->getJson('/api/students');
        $response->assertStatus(401);
    }

    // ─── STORE (Admin only) ─────────────────────────────────────────

    public function test_admin_can_create_student()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/students', [
                'class_id' => $this->classRoom->id,
                'trainer_id' => $this->trainer->id,
                'name' => 'John Doe',
                'priority' => 'Medium',
                'status' => 'Pending',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'John Doe',
                'class_id' => $this->classRoom->id,
                'trainer_id' => $this->trainer->id,
                'priority' => 'Medium',
                'status' => 'Pending',
            ]);

        $this->assertDatabaseHas('students', ['name' => 'John Doe']);
    }

    public function test_trainer_cannot_create_student()
    {
        $response = $this->actingAs($this->trainer, 'sanctum')
            ->postJson('/api/students', [
                'class_id' => $this->classRoom->id,
                'trainer_id' => $this->trainer->id,
                'name' => 'John Doe',
                'priority' => 'Medium',
                'status' => 'Pending',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_student_fails_without_required_fields()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/students', []);

        $response->assertStatus(422);
    }

    public function test_create_student_fails_with_invalid_priority()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/students', [
                'class_id' => $this->classRoom->id,
                'trainer_id' => $this->trainer->id,
                'name' => 'John Doe',
                'priority' => 'Urgent', // Invalid
                'status' => 'Pending',
            ]);

        $response->assertStatus(422);
    }

    // ─── SHOW ───────────────────────────────────────────────────────

    public function test_admin_can_show_student()
    {
        $student = Student::factory()->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
            'name' => 'Jane Doe',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $student->id,
                'name' => 'Jane Doe',
            ]);
    }

    public function test_trainer_can_show_student()
    {
        $student = Student::factory()->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->actingAs($this->trainer, 'sanctum')
            ->getJson("/api/students/{$student->id}");

        $response->assertStatus(200);
    }

    public function test_show_student_fails_for_nonexistent()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/students/99999');

        $response->assertStatus(404);
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function test_admin_can_update_student()
    {
        $student = Student::factory()->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
            'name' => 'Original Name',
            'priority' => 'Low',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/students/{$student->id}", [
                'name' => 'Updated Name',
                'priority' => 'High',
                'status' => 'In Progress',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name',
                'priority' => 'High',
                'status' => 'In Progress',
            ]);
    }

    public function test_trainer_can_update_student()
    {
        $student = Student::factory()->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->trainer, 'sanctum')
            ->putJson("/api/students/{$student->id}", [
                'name' => 'Updated by Trainer',
            ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Updated by Trainer']);
    }

    // ─── DESTROY (Admin only) ───────────────────────────────────────

    public function test_admin_can_delete_student()
    {
        $student = Student::factory()->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/students/{$student->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_trainer_cannot_delete_student()
    {
        $student = Student::factory()->create([
            'class_id' => $this->classRoom->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->actingAs($this->trainer, 'sanctum')
            ->deleteJson("/api/students/{$student->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_delete_nonexistent_student_returns_404()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/students/99999');

        $response->assertStatus(404);
    }
}
