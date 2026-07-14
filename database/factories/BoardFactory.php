<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => 'Student Follow-up ' . fake()->year(),
            'is_favorite' => fake()->boolean(20),
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn(array $attributes) => ['is_archived' => true]);
    }
}
