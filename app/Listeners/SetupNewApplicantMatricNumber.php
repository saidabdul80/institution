<?php

namespace App\Listeners;

use App\Events\PaymentMade;
use App\Events\InvoicePaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\SecuredPanelAPI\Services\Utilities;

class SetupNewApplicantMatricNumber implements  ShouldQueue
{
    use InteractsWithQueue;
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
     * @param  \App\Events\PaymentMade  $event
     * @return void
     */
    public function handle(PaymentMade|InvoicePaid $event)
    {
        
        if ($event instanceof PaymentMade) {
            $payment = $event->payment;
            if ($payment->status == 'successful' && $payment->invoice->payment_category == 'registration_fee' && $payment->invoice->owner_type == 'applicant' && $payment->owner->admission_status =='admitted') {
                // student matric number setup, create a student from the applicant records
                Utilities::makeNewStudent($payment->owner);
            }
        } else if ($event instanceof InvoicePaid) {
            $invoice = $event->invoice;
            if ($invoice->status == 'paid' 
                && $invoice->payment_category =='registration_fee' 
                && $invoice->owner_type == 'applicant' 
                && $invoice->owner->admission_status =='admitted') {                    
                // student matric number setup, create a student from the applicant records
                Utilities::makeNewStudent($invoice->owner);
            }
        }
    }
}
