<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\Level;

class LevelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $levels = [
         ["title"=>"100",'order'=>'1'],
         ["title"=>"200",'order'=>'2'],
         ["title"=>"300",'order'=>'3'],
         ["title"=>"400",'order'=>'4'],
         ["title"=>"500",'order'=>'5'],
         ["title"=>"600",'order'=>'6'],
         ["title"=>"700",'order'=>'7'],
         ["title"=>"800",'order'=>'8'],
         ["title"=>"900",'order'=>'9'],
         ["title"=>"spill",'order'=>'spill'],
        ];
        DB::table('levels')->insert($levels);
    }
}
