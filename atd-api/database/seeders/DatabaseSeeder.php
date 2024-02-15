<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Role::factory()->create([
            'name' => 'admin',
            'archive' => false
        ]);
        Role::factory()->create([
            'name' => 'volunteer',
            'archive' => false
        ]);
        Role::factory()->create([
            'name' => 'beneficiary',
            'archive' => false
        ]);
        Role::factory()->create([
            'name' => 'partner',
            'archive' => false
        ]);
    }
}
