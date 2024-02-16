<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\Qualification;
class QualificationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();    
        DB::table('qualifications')->insert([
            ["name"=>"Bachelor's Degree", "short_name"=>"bsc"],
            ["name"=>"HSG/GCE AL", "short_name"=>"hsg"],
            ["name"=>"Masters", "short_name"=>"msc"],
            ["name"=>"Nigerian Certificate of Education", "short_name"=>"nce"],
            ["name"=>"National Democratic Alliance", "short_name"=>"nda"],
            ["name"=>"National Diploma", "short_name"=>"ond_hnd"],
            ["name"=>"Postgraduate Diploma", "short_name"=>"pgd"],
            ["name"=>"Doctor of Philosophy", "short_name"=>"phd"],
            ["name"=>"SSCE (WAEC/NECO/NABTEB)", "short_name"=>"ssce"],
            ["name"=>"TCI/ACE", "short_name"=>"tci_ace"],
            ["name"=>"WASC /GCE OL", "short_name"=>"wasc_gce_ol"],
            ["name"=>"Others", "short_name"=>""],            
        ]);        
        // $this->call("OthersTableSeeder");
    }
}
