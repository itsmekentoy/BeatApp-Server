<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BeatCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $regions = ['NCR', 'Region IV-A', 'Region III', 'Region VI'];
        $provinces = ['Cavite', 'Laguna', 'Batangas', 'Bulacan'];
        $cities = ['Manila', 'Quezon City', 'Caloocan', 'Taguig', 'Pasig', 'Makati'];
        $barangays = ['San Isidro', 'San Roque', 'Poblacion', 'San Jose', 'Bagong Silang'];
        $streets = ['Rizal Ave', 'Mabini St', 'Del Pilar St', 'Bonifacio St'];

        $data = [];

        for ($i = 1; $i <= 20; $i++) {
            $gender = fake()->randomElement([0, 1, 2]);
            $birthdate = fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d');
            $age = Carbon::parse($birthdate)->age;
            $membershipId = fake()->numberBetween(1, 3); // corresponds to beat_memberships

            $membershipStart = fake()->dateTimeBetween('-1 years', 'now')->format('Y-m-d');
            $membershipEnd = Carbon::parse($membershipStart)->addDays(fake()->randomElement([30, 60, 90]))->format('Y-m-d');

            $data[] = [
                'firstname' => fake()->firstName(),
                'lastname' => fake()->lastName(),
                'middlename' => Str::upper(Str::random(1)),
                'gender' => $gender,
                'birthdate' => $birthdate,
                'age' => $age,
                'a_region' => fake()->randomElement($regions),
                'a_province' => fake()->randomElement($provinces),
                'a_city' => fake()->randomElement($cities),
                'a_barangay' => fake()->randomElement($barangays),
                'a_street' => fake()->randomElement($streets),
                'a_zipcode' => fake()->numberBetween(1000, 9999),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->numerify('09#########'),
                'phone2' => fake()->optional()->numerify('09#########'),
                'keypab' => strtoupper(Str::random(8)),
                'membership_id' => $membershipId,
                'membership_start' => $membershipStart,
                'membership_end' => $membershipEnd,
                'status' => fake()->randomElement([0, 1]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('beat_customers')->insert($data);
    }
}
