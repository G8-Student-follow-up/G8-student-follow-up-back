<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardMovementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function card_can_be_moved_to_different_column()
    {
        $user = User::factory()->create();
        $board = Board::factory()->create();
        $board->members()->attach($user->id, ['role' => 'admin']);

        $column1 = Column::factory()->create(['board_id' => $board->id, 'position' => 0]);
        $column2 = Column::factory()->create(['board_id' => $board->id, 'position' => 1]);

        $card = Card::factory()->create([
            'board_id' => $board->id,
            'column_id' => $column1->id,
            'position' => 0,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/cards/{$card->id}/move", [
                'column_id' => $column2->id,
                'position' => 0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'card' => [
                    'id' => $card->id,
                    'column_id' => $column2->id,
                    'position' => 0,
                ]
            ]);

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'column_id' => $column2->id,
            'position' => 0,
        ]);
    }
}