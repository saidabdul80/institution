<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing grade settings
        DB::table('grade_settings')->truncate();

        // Insert Nigerian standard grade settings
        $gradeSettings = [
            [
                'programme_id' => null,
                'min_score' => 70,
                'max_score' => 100,
                'grade' => 'A',
                'grade_point' => 5.0,
                'status' => 'pass',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'programme_id' => null,
                'min_score' => 60,
                'max_score' => 69,
                'grade' => 'B',
                'grade_point' => 4.0,
                'status' => 'pass',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'programme_id' => null,
                'min_score' => 50,
                'max_score' => 59,
                'grade' => 'C',
                'grade_point' => 3.0,
                'status' => 'pass',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'programme_id' => null,
                'min_score' => 45,
                'max_score' => 49,
                'grade' => 'D',
                'grade_point' => 2.0,
                'status' => 'pass',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'programme_id' => null,
                'min_score' => 40,
                'max_score' => 44,
                'grade' => 'E',
                'grade_point' => 1.0,
                'status' => 'pass',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'programme_id' => null,
                'min_score' => 0,
                'max_score' => 39,
                'grade' => 'F',
                'grade_point' => 0.0,
                'status' => 'fail',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('grade_settings')->insert($gradeSettings);

        $this->command->info('Grade settings seeded successfully!');
    }
}
