<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\Session;
class SessionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('sessions')->insert([
            "name" => "2020/2021",          
        ]);
        DB::table('sessions')->insert([
            "name" => "2021/2022",          
        ]);
        DB::table('sessions')->insert([
            "name" => "2022/2023",          
        ]);

        // $this->call("OthersTableSeeder");
    }
}
