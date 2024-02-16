<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\ExamType;
class ExamTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('exam_types')->insert([
            "name" => "NECO",            
            "description" => "National Examination Council",            
        ]);
        DB::table('exam_types')->insert([
            "name" => "WAEC",            
            "description" => "West African Examination Council",            
        ]);
        DB::table('exam_types')->insert([
            "name" => "JAMB",            
            "description" => "Joint Admissions and Matriculation Board",            
        ]);
        DB::table('exam_types')->insert([
            "name" => "NABTEB",            
            "description" => "National Business and Technical Examinations Board",            
        ]);

        // $this->call("OthersTableSeeder");
    }
}
