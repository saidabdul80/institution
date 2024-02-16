<?php

namespace App\Listeners;

use App\Events\AgentRegistered;
use App\Mail\SendVerification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendAgentVerificationMail
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
    public function handle(AgentRegistered $event)
    {
        $agent = $event->agent;
        Mail::to($agent)->send(new SendVerification($agent));
    }
}
