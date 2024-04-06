<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Warehouse::create([
            'name' => 'Saint-Quentin',
            'address' => '6 boulevard Gambetta',
            'zipcode' => '02100',
            'capacity' => 10000
        ]);

        Warehouse::create([
            'name' => 'Laon',
            'address' => '50 Rue Saint-Jean',
            'zipcode' => '02000',
            'capacity' => 4500
        ]);
    }
}
