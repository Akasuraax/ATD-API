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
        Schedule::create([
            'day' => 1,
            'start_hour' => '11:00:00',
            'end_hour' => '12:00:00',
            'user_id' => 6
        ]);

        Schedule::create([
            'day' => 4,
            'start_hour' => '16:00:00',
            'end_hour' => '18:30:00',
            'user_id' => 6
        ]);

        Schedule::create([
            'day' => 2,
            'start_hour' => '14:45:00',
            'end_hour' => '16:30:00',
            'user_id' => 7
        ]);
    }
}
