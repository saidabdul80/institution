<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\EntryMode;

class EntryModeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $entry_modes = [
            ["title"=>"UTME", "code"=>"UTME" , "description"=>""],
            ["title"=>"DE", "code"=>"DE" , "description"=>""],
            ["title"=>"PRE NCE", "code"=>"PRE" , "description"=>""],
            ["title"=>"NCE DIRECT", "code"=>"NCED" , "description"=>""],
            ["title"=>"NCE JAMB", "code"=>"NCEJ" , "description"=>""],
            ["title"=>"DEFERMENT", "code"=>"DEF" , "description"=>""],
            ["title"=>"OTHERS", "code"=>"OTH" , "description"=>""],
        ];
        DB::table('entry_modes')->insert($entry_modes);        
    }
}
