<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BeatMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            [
                'name' => 'Basic Plan',
                'description' => 'Access to gym and cardio area.',
                'price' => 999.00,
                'duration_days' => 30,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Standard Plan',
                'description' => 'Includes gym, cardio, and group classes.',
                'price' => 1499.00,
                'duration_days' => 60,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Premium Plan',
                'description' => 'All-access including swimming and sauna.',
                'price' => 2499.00,
                'duration_days' => 90,
                'status' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('beat_memberships')->insert($memberships);
    }
}
