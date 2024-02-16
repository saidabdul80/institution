<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Events\TenantCreated;

class DatabaseSeeder
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
        Artisan::call("db:seed --class=SemestersTableSeeder");
        Artisan::call("db:seed --class=SessionsTableSeeder");
        Artisan::call("db:seed --class=CountryTableSeeder");
        Artisan::call("db:seed --class=StateTableSeeder");
        Artisan::call("db:seed --class=LGATableSeeder");
        Artisan::call("db:seed --class=ProgrammeTypeTableSeeder");
        Artisan::call("db:seed --class=EntryModeTableSeeder");
        Artisan::call("db:seed --class=LevelTableSeeder");
        Artisan::call("db:seed --class=ApplicationStatusTableSeeder");
        Artisan::call("db:seed --class=QualificationsTableSeeder");
        Artisan::call("db:seed --class=ExamTypeTableSeeder");
        Artisan::call("db:seed --class=CertificateTypesTableSeeder");
        Artisan::call("db:seed --class=PaymentCategorySeeder");
        Artisan::call("db:seed --class=ConfigurationTableSeeder");
        Artisan::call("db:seed --class=StaffTableSeeder");
        Artisan::call("db:seed --class=PermissionSeederTable");
        Artisan::call("db:seed --class=SubjectSeeder");
        Artisan::call("db:seed --class=AdmissionBatch");
        Artisan::call("db:seed --class=ApplicationPortalMenuSeeder");
        Artisan::call("db:seed --class=ApplicationPortalSubMenuSeeder");
        Artisan::call("db:seed --class=SummerPortalMenuSeeder");
        Artisan::call("db:seed --class=SummerPortalSubMenuSeeder");
        Artisan::call("db:seed --class=StudentPortalMenuSeeder");
        Artisan::call("db:seed --class=StudentPortalSubMenuSeeder");
    }   
}
