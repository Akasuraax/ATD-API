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
            'name' => "Soissons",
            'address' => "44 Avenue de Paris",
            'zipcode' => "02200",
        ]);

        Annexe::create([
           'name' => "Villers Cotteret",
           'address' => "100 Rue Demoustier",
           'zipcode' => "02600",
        ]);

    }
}
