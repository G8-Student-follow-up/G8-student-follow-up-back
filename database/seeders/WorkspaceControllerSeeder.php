<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkspaceControllerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Workspace::factory()
        ->count(3)
        ->create(['owner_id' => User::inRandomOrder()->first()?->id ?? User::factory()]);
    }
}
