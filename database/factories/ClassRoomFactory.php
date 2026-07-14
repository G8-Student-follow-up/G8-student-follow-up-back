<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassRoomFactory extends Factory
{
    protected $model = ClassRoom::class;

    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'title' => fake()->words(3, true),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
