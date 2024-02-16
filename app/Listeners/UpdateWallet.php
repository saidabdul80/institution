<?php

namespace App\Listeners;

use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class UpdateWallet
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
        $payment = $event->payment;        
        $school_id = Redis::hget($payment->payment_reference, 'tenant_id');        
        $tenant = Tenant::find($school_id);
        $tenant->run(function() use($payment){    
            $owner = $payment->owner();
            if ($payment->status == 'successful') {
                $owner->credit($payment->amount);
            }
        });
       
    }
}
