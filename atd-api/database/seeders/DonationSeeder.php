<?php

namespace Database\Seeders;

use App\Models\Donation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Donation::create([
            'amount' => 7500.50,
            'user_id' => 1,
        ]);

        Donation::create([
            'amount' => 20,
            'user_id' => 23,
        ]);

        Donation::create([
            'amount' => 50,
            'user_id' => 22,
        ]);
    }
}
