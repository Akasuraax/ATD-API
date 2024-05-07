<?php

namespace Database\Seeders;

use App\Models\Problem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProblemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Problem::create([
            'name' => 'Bug',
        ]);

        Problem::create([
            'name' => 'Problème avec une activité',
        ]);

        Problem::create([
            'name' => 'Autre'
        ]);
    }
}
