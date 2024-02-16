<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;
use App\Models\Tenant;
use Modules\BasicSchoolAPI\Entities\Invoice as EntitiesInvoice;

class InvoicePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $invoice;
    public $tenant;
    public function __construct(Invoice|EntitiesInvoice $invoice, Tenant|null $tenant = null)
    {
        $this->invoice = $invoice;
        $this->tenant = $tenant ?? tenant();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
