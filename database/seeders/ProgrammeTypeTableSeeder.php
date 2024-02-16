<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgrammeTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $programme_tyes = [
            ["name" => 'Full Time', "short_name" => 'full_time'],
            ["name" => 'Part Time', "short_name" => 'part_time']
        ];

        foreach ($programme_tyes as $programme_tye)
        {
            DB::table('programme_types')->insert($programme_tye);
        }
    }
}
