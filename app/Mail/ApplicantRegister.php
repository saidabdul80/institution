<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicantRegister extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $applicant;
    public $school_name;
    public $school_subdomain;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($applicant)
    {
        $this->applicant = $applicant;
        $this->school_name = tenant('name');
        $this->school_subdomain = tenant('sub_domain');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'),$this->tenant->name)->markdown('emails.applicant_register')
        ->subject('Applicant Registration')
        ->with([
            'applicant' => $this->applicant,
            'school_name' => $this->school_name,
            'school_subdomain' => $this->school_subdomain,
        ]);
    }
}
