<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmissionBatch extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admission_batches')->insert([
            "name" => 'Batch A',
            "name" => 'Batch B',
            "name" => 'Batch C',
            "name" => 'Batch D',
            "name" => 'Batch E'
        ]);         
    }
}
