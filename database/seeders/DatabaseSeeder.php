<?php

namespace Database\Seeders;

use App\Models\About;
use App\Models\Content;
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

        User::create([
            'name' => 'admin',
            'login' => 'admin',
            'password' => 'admin',
            'role'=>'admin'
        ]);
        User::create([
            'name' => fake()->fullname(),
            'login' => 'ivanov',
            'password' => 'ivanov',
            'role'=>'ceo'
        ]);
        Tree::create([
            'name' => 'New course',
            'user_id' => 2,
            'type' => 'folder',
        ]);
        Tree::create([
            'name' => 'New course2',
            'user_id' => 2,
            'tree_id' => 2,
            'type' => 'file',
        ]);
        Content::create([
            'tree_id' => 2,
            'accessibility' => 1,
        ]);
        About::create([
            'name'=>fake()->paragraph(),
            'logo'=>'notfound.webp',
        ]);
    }
}
