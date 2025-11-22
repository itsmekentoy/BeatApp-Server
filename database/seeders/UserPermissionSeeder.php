<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserPermission;

class UserPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionList = [
            "1" => "View Financial Overview",
            "2" => "Membership Plan",
            "3" => "Add Customer",
            "4" => "Edit Customer",
            "5" => "Delete Customer",
            "6" => "Add Transaction",
            "7" => "Add Expense",
            "8" => "Edit Expense",
            "9" => "Delete Expense",
            "10" => "Product Management",
            "11" => "Email Management",
        ];

        foreach ($permissionList as $code => $name) {
            UserPermission::create([
                'user_id' => 1, // change this if needed
                'permission' => $code,
                'permission_name' => $name,
                'is_granted' => true,
            ]);
        }
    }
}
