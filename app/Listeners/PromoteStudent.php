<?php

namespace App\Listeners;

use App\Jobs\PromoteStudent as JobsPromoteStudent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class PromoteStudent
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
    public function handle($event)
    {
        $invoice = $event->invoice;
        JobsPromoteStudent::dispatch($invoice);
    }
}

