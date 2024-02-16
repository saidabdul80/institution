<?php

namespace App\Listeners\BasicSchool;

use App\Events\BasicSchool\ApplicantAdmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Models\Activity;

class SendApplicantAdmittedNotification
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
     * @param  \App\Events\BasicSchool\\ApplicantAdmitted  $event
     * @return void
     */
    public function handle(ApplicantAdmitted $event)
    {           
        $application_number = $event->applicant->application_number;
        $first_name = $event->applicant->first_name;
        $fullname = $event->applicant->full_name;
        activity("Congratulations! $first_name ($application_number) has been admitted")
        ->performedOn($event->parent)
        ->log("$fullname, $application_number has been admitted and enrollment completed.");
    }
}
