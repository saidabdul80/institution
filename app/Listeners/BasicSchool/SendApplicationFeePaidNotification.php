<?php

namespace App\Listeners\BasicSchool;

use App\Events\BasicSchool\ApplicationFeePaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApplicationFeePaidNotification
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
     * @param  \App\Events\BasicSchool\\ApplicationFeePaid  $event
     * @return void
     */
    public function handle(ApplicationFeePaid $event)
    {
        $application_number = $event->applicant->application_number;
        $first_name = $event->applicant->first_name;
        $fullname = $event->applicant->full_name;
        activity("$first_name ($application_number) application fee paid")
        ->performedOn($event->parent)
        ->log("$fullname, $application_number application fee has been paid. Please complete their profile to continue.");
    }
}
