<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Type::create([
            'name' => 'Maraude',
            'access_to_warehouse' => true,
            'access_to_journey' => true,
            'display' => false
        ]);

        Type::create([
            'name' => 'Services admnistratifs',
            'access_to_warehouse' => false,
            'access_to_journey' => false,
            'display' => false
        ]);

        Type::create([
            'name' => 'Navettes/délplacements',
            'access_to_warehouse' => false,
            'access_to_journey' => true,
            'display' => false
        ]);

        Type::create([
            'name' => 'Cours d\'alphabétisation',
            'access_to_warehouse' => false,
            'access_to_journey' => true,
            'display' => false
        ]);

        Type::create([
            'name' => 'Soutien scolaire pour enfant',
            'access_to_warehouse' => false,
            'access_to_journey' => false,
            'display' => false
        ]);

        Type::create([
            'name' => 'Récolte de fonds',
            'access_to_warehouse' => false,
            'access_to_journey' => true,
            'display' => false
        ]);

        Type::create([
            'name' => 'Visite',
            'access_to_warehouse' => false,
            'access_to_journey' => false,
            'display' => false
        ]);

        Type::create([
            'name' => 'Autre',
            'access_to_warehouse' => false,
            'access_to_journey' => false,
            'display' => false
        ]);

        Type::create([
            'name' => 'Rôle',
            'access_to_warehouse' => false,
            'access_to_journey' => false,
            'display' => false
        ]);
    }
}
