<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    public function run(): void
    {
        Workspace::all()->each(function (Workspace $workspace) {
            Board::factory()->count(2)->create(['workspace_id' => $workspace->id]);
        });
    }
}
