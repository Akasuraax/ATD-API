<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $currentTimestamp = Carbon::now();


        $products = [
            [
                'name' => 'Sucre',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Eau',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Pain',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Lait',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Farine',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Pomme de terre',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Riz',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Pomme',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Tomate',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Oeufs',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Pâtes',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Yaourt',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Huile',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Fromage',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Café',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Thé',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Jus d\'orange',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Poulet',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Boeuf',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Poisson',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Sel',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Poivre',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Vinaigre',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Miel',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Légumes surgelés',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Chocolat',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Biscuits',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Soda',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Lentilles',
                'measure' => 'kg',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Miel',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Vodka',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Vin rouge',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Vin blanc',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Papier toilette',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Savon',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Shampooing',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Lessive',
                'measure' => 'l',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Sachet de thé',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
            [
                'name' => 'Sachet de café',
                'measure' => 'u',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert($product);
        }
    }
}
