<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassRoom>
 */
class ClassRoomFactory extends Factory
{
    protected $model = ClassRoom::class;

    public function definition(): array
    {
        return [
            'title' => fake()->word() . ' Class',
            'position' => fake()->numberBetween(0, 10),
            'board_id' => Board::factory(),
        ];
    }
}
