<?php

namespace App\Listeners\BasicSchool;

use App\Events\BasicSchool\ApplicantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApplicantCreatedNotification
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
     * @param  \App\Events\BasicSchool\\ApplicantCreated  $event
     * @return void
     */
    public function handle(ApplicantCreated $event)
    {        
        $application_number = $event->applicant->application_number;
        $first_name = $event->applicant->first_name;
        $fullname = $event->applicant->full_name;
        activity("$first_name ($application_number) enrollment started")
        ->performedOn($event->parent)
        ->log("Enrollment process for $fullname, $application_number has been initiated. Please make payment and complete their profile to continue.");
    }
}
