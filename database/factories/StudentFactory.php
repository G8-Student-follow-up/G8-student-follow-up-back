<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'class_id' => ClassRoom::factory(),
            'trainer_id' => User::factory(),
            'name' => fake()->name(),
            'photo' => null,
            'description' => fake()->sentence(),
            'follow_up_date' => fake()->optional()->date(),
            'priority' => fake()->randomElement(['Low', 'Medium', 'High']),
            'status' => fake()->randomElement(['Pending', 'In Progress', 'Completed', 'Archived']),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
