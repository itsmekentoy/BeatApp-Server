<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            'name' => "BeatFitNess",
            "email" => "admin@beatapp.com",
            "email_verified_at" => Carbon::now(),
            "password" => bcrypt('admin123'), // Corrected password hashing
            "role" => 'Owner',
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ];

        DB::table('users')->insert($users);
    }
}
