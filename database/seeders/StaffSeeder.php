<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Staff::create([
            'id' => '1',
            'staff_number' => 'staff/001',
            'email' => 'staff/001',
            'first_name' => 'staff',
            'middle_name' => NULL,
            'surname' => 'staff',
            'gender' => 'male',
            'phone_number' => NULL,
            'address' => NULL,
            'password' => Hash::make('1234'),
            'staff_role_id' => NULL,
            'faculty_id' => NULL,
            'department_id' => NULL,
            'type' => 'academic',
            'first_login' => 'true',
            'logged_in_time' => '2024-04-06 16:32:04',
            'logged_in_count' => NULL,
            'deleted_at' => NULL,
            'created_at' => NULL,
            'updated_at' => '2024-04-06 16:32:04'
        ]);
    }
}
