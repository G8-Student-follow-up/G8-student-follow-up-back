<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => fake()->sentence(3),
            'is_favorite' => fake()->boolean(),
            'is_archived' => false,
        ];
    }
}
