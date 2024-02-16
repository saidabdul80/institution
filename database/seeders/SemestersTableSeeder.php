<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemestersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $semesters = [
            ["name" => "first"],
            ["name" => "second"],
            ["name" => "third"],
        ];

        foreach ($semesters as $semester) {
            DB::table('semesters')->insert($semester);
        }
    }
}
