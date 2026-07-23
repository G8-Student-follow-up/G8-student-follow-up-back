<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Label;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_returns_student_with_relations(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $classroom = ClassRoom::factory()->create();
        $label = Label::create([
            'board_id' => $classroom->board_id,
            'name' => 'Needs follow up',
            'color' => '#f59e0b',
        ]);

        $student = Student::create([
            'class_id' => $classroom->id,
            'trainer_id' => $trainer->id,
            'name' => 'Old Name',
            'priority' => 'Medium',
            'status' => 'Pending',
            'position' => 3,
        ]);
        $student->labels()->attach($label);

        $response = $this
            ->actingAs($trainer, 'sanctum')
            ->putJson("/api/students/{$student->id}", [
                'name' => 'New Name',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('position', 3)
            ->assertJsonStructure([
                'classroom',
                'trainer',
                'labels',
                'comments',
                'attachments',
            ]);

        $this->assertSame('New Name', $student->fresh()->name);
    }
}
