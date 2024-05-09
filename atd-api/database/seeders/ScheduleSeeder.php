<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 0; $i<8; $i++){
            Schedule::create([
                'day' => $i,
                'start_hour' => '11:00:00',
                'end_hour' => '12:00:00',
                'user_id' => 6
            ]);
        }

        for($i = 0; $i<8; $i++){
            Schedule::create([
                'day' => $i,
                'start_hour' => '11:00:00',
                'end_hour' => '12:00:00',
                'user_id' => 7
            ]);
        }

        for($i = 0; $i<8; $i++){
            Schedule::create([
                'day' => $i,
                'start_hour' => '11:00:00',
                'end_hour' => '12:00:00',
                'user_id' => 8
            ]);
        }

    }
}
