<?php

namespace Database\Seeders;

use App\Models\Annexe;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnnexeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Annexe::create([
           'name' => "ESGI",
           'address' => "242 Rue du Faubourg Saint-Antoine",
           'zipcode' => "75012",
        ]);

        Annexe::create([
            'name' => "Erard",
            'address' => "21 rue Erard",
            'zipcode' => "75012",
        ]);
    }
}
