<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\ApplicationPortalAPI\Entities\LGA;

class LGATableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        //$json = File::get('database/data/l_g_as.json');
        $json = File::get(base_path('database/data/l_g_as.json'));
        $lgas = json_decode($json);
        $lgas_array = [];
        foreach($lgas as $key => $value) {
            foreach($value as $val) {
                $lgas_array[] = [
                    "id" => $val->id,
                    "state_id" => $val->state_id,
                    "name" => $val->name
                ];
            }
        }
        DB::table('l_g_as')->upsert($lgas_array, ['id']);

        // $this->call("OthersTableSeeder");
    }
}
