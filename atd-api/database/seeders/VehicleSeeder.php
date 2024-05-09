<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Vehicle::create([
           'name' => "Martine",
            'license_plate' => "AA-229-AA",
            'average_consumption' => 10.6,
            'partner' => false,
            'fuel_type' => "diesel",
            'id_annexe' => 1
        ]);

        Vehicle::create([
            'name' => "Ginette",
            'license_plate' => "BA-223-HG",
            'average_consumption' => 9.3,
            'partner' => true,
            'fuel_type' => "E75",
            'id_annexe' => 1
        ]);
    }
}
