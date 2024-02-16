<?php

namespace App\Listeners\BasicSchool;

use App\Events\BasicSchool\ApplicationCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApplicationCompletedNotification
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
     * @param  \App\Events\BasicSchool\\ApplicationCompleted  $event
     * @return void
     */
    public function handle(ApplicationCompleted $event)
    {
        $application_number = $event->applicant->application_number;
        $first_name = $event->applicant->first_name;
        $fullname = $event->applicant->full_name;
        activity("$first_name ($application_number) application completed")
        ->performedOn($event->parent)
        ->log("$fullname, $application_number application is complete. Kindly await admission.");
    }
}
