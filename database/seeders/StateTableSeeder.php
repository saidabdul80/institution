<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\ApplicationPortalAPI\Entities\State;

class StateTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $json = File::get(base_path('database/data/states.json'));
        //$json = File::get('database/data/states.json');
        $states = json_decode($json);
        $states_array = [];
        foreach($states as $key => $value) {
            foreach($value as $val) {

                $states_array[] = [
                    "id" => $val->state_id,
                    "name" => $val->name,
                    "country_id" => $val->country_id
                ];
            }
        }
        DB::table('states')->upsert($states_array, ['id']);
        // $this->call("OthersTableSeeder");
    }
}
