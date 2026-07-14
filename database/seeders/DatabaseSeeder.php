<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
        ]);
        User::factory()->count(5)->create();
        $this->call([
            AuthControllerSeeder::class,
            RoleSeeder::class,
        ]);

        // Create a test workspace for the first admin user
        $admin = User::where('email', 'admin@gmail.com')->first();
        if ($admin) {
            Workspace::create([
                'name' => 'My Workspace',
                'created_by' => $admin->id,
            ]);
        }
    }
}
