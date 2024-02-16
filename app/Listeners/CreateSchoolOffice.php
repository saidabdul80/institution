<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

class CreateSchoolOffice
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TenantCreated $event)
    {
        $tenant = $event->tenant;
        if($tenant->type == 'university'){
            Artisan::call("db:seed --class=University_Seeder");
        }else if($tenant->type == 'polytechnic'){
            Artisan::call("db:seed --class=Polytechnic_Seeder");
        }else if($tenant->type == 'coe'){
            Artisan::call("db:seed --class=COE_Seeder");
        }
    }
}
