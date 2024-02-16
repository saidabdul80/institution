<?php

namespace Database\Seeders;

use Modules\ApplicationPortalAPI\Entities\CertificateType;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CertificateTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('certificate_types')->insert(
        [       
        ["name" =>"Technology Certificates","description" => "Technology Certificates"],
        ["name" =>"Health Care Certificates ","description" => "Health Care Certificates "]
        ]);
        // $this->call("OthersTableSeeder");
    }
}
