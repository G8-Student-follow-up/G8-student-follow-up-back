<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\ClassRoom;
use Illuminate\Database\Seeder;

class ClassRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Board::all()->each(function (Board $board) {
            ClassRoom::factory()
                ->count(3)
                ->sequence(fn ($sequence) => ['position' => $sequence->index])
                ->create(['board_id' => $board->id]);
        });
    }
}
