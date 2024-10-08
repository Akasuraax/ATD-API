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
            'checkout_session' => 'cs_test_a1PvXi9cplVM20QSA1WNfj85lh3rUMRFO3W2ppxA3Z0JQKoDB7JnuKZXB7',
            'user_id' => 1
        ]);
    }
}
