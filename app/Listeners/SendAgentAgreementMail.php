<?php

namespace App\Listeners;

use App\Events\AgentRegistered;
use App\Mail\SendAgentWelcome;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendAgentAgreementMail
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
        $data["email"] = $agent->email;
        $data["title"] = "Tespire Agreement";
        $data["body"] = "Tesipire Agreement Document";

        $pdf = Pdf::loadView('emails.attachments.agreement', ['agent' => $agent]);

        Mail::to($agent)->send(new SendAgentWelcome($agent, $pdf));
    }
}
