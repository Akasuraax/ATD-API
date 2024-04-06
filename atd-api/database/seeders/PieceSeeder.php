<?php

namespace Database\Seeders;

use App\Models\Piece;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PieceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Piece::create([
            'expired_date' => "2024-08-05 15:00",
            'count' => 90,
            'location' => 1,
            'id_warehouse' => 1,
            'id_product' => 1
        ]);

        Piece::create([
            'expired_date' => "2024-04-10 15:00",
            'count' => 120,
            'location' => 12,
            'id_warehouse' => 1,
            'id_product' => 3
        ]);

        Piece::create([
            'expired_date' => "2024-05-10 15:00",
            'count' => 75,
            'location' => 35,
            'id_warehouse' => 1,
            'id_product' => 7
        ]);

        Piece::create([
            'expired_date' => "2024-04-24 15:00",
            'count' => 24,
            'location' => 31,
            'id_warehouse' => 1,
            'id_product' => 10
        ]);

        Piece::create([
            'expired_date' => "2024-04-25 15:00",
            'count' => 48,
            'location' => 9,
            'id_warehouse' => 2,
            'id_product' => 10
        ]);

        Piece::create([
            'expired_date' => "2024-06-29 15:00",
            'count' => 200,
            'location' => 1,
            'id_warehouse' => 2,
            'id_product' => 5
        ]);

        Piece::create([
            'expired_date' => "2024-04-29 15:00",
            'count' => 123,
            'location' => 23,
            'id_warehouse' => 2,
            'id_product' => 9
        ]);

        Piece::create([
            'expired_date' => "2024-09-29 15:00",
            'count' => 700,
            'location' => 99,
            'id_warehouse' => 2,
            'id_product' => 7
        ]);
    }
}
