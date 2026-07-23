<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerEmailSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_email_suggestions_return_matching_trainers_only(): void
    {
        $currentUser = User::factory()->create(['role' => 'trainer']);
        $matchingTrainer = User::factory()->create([
            'name' => 'Sophy Trainer',
            'email' => 'sophy.trainer@example.com',
            'role' => 'trainer',
        ]);
        User::factory()->create([
            'name' => 'Sophy Admin',
            'email' => 'sophy.admin@example.com',
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Other Trainer',
            'email' => 'other.trainer@example.com',
            'role' => 'trainer',
        ]);

        $response = $this
            ->actingAs($currentUser, 'sanctum')
            ->getJson('/api/trainers/email-suggestions?search=sophy&limit=5');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'suggestions')
            ->assertJsonPath('suggestions.0.id', $matchingTrainer->id)
            ->assertJsonPath('suggestions.0.email', 'sophy.trainer@example.com');
    }
}
