<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\ApplicationType;

class ApplicationTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $application_types = ["full time", "part time"];
        foreach ($application_types as $type) {
            DB::table('application_types')->insert([
                "title" => $type
            ]);
        }
    }
}
