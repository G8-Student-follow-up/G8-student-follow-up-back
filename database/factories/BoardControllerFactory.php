<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Workspace;

class BoardControllerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => 'Student Follow-up' . fake()->year(),
            'is_favorite' => fake()->boolean(20),
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn(array $attributes) => ['is_archived' => true]);
    }
}
