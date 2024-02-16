<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\PaymentMade;
use App\Jobs\SyncWallet as SyncWalletJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncWallet
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
    public function handle(PaymentMade|InvoicePaid $event)
    {
        SyncWalletJob::dispatch($event);
    }
}
