<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Entities\ApplicationStatus;

class ApplicationStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $application_statuses = ["admitted", "rejected", "pending"];

        foreach ($application_statuses as $status) {
            DB::table('application_statuses')->insert([
                "title" => $status
            ]);
        }
    }
}
