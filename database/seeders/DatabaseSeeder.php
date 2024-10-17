<?php

namespace Database\Seeders;

use App\Models\Tree;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'admin',
            'email' => 'test@example.com',
            'password' => bcrypt('admin'),
            'role'=>'admin'
        ]);
        Tree::create([
            'name' => 'New course',
            'user_id' => 1,
            // 'tree_id' => null,
            'type' => 'folder',
        ]);
        Tree::create([
            'name' => 'New course2',
            'user_id' => 1,
            'tree_id' => 1,
            'type' => 'folder',
        ]);
    }
}
