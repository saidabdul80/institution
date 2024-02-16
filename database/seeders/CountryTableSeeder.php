<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CountryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        //$json = File::get('database/data/countries.json');
        $json = File::get(base_path('database/data/countries.json'));
        $countries = json_decode($json);
        $countries_array = [];
        foreach($countries as $key => $value) {
            foreach($value as $val) {

                $countries_array[] = [
                    "id"  => $val->country_id,
                    "name" => $val->name,
                    "short_name" => $val->short_name,
                    "phone_code" => $val->phone_code
                ];
            }
        }
        DB::table('countries')->upsert($countries_array, ['id']);
       // $this->call("OthersTableSeeder");
    }
}
