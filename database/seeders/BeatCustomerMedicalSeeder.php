<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BeatCustomerMedicalSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            'Hypertension',
            'Diabetes',
            'Asthma',
            'None',
            'Heart Condition',
            'Back Pain',
            'Arthritis',
            'High Cholesterol'
        ];

        $data = [];
        for ($i = 1; $i <= 20; $i++) {
            $data[] = [
                'beat_customer_id' => $i,
                'medical_condition' => fake()->randomElement($conditions),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        DB::table('beat_customer_medicals')->insert($data);
    }
}
