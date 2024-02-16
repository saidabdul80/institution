<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Stancl\Tenancy\Events\TenantCreated;
use App\Models\TenantPortal;

class SetupDefaultActivePortals implements ShouldQueue
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
     * @param  TenantCreated  $event
     * @return void
     */
    public function handle(TenantCreated $event)
    {
        $tenant = $event->tenant;

        TenantPortal::insert([
            [
                "portal_id" => 1,
                "tenant_id" => $tenant->id,
                "active" => true
            ],
            [
                "portal_id" => 2,
                "tenant_id" => $tenant->id,
                "active" => true
            ],
            [
                "portal_id" => 3,
                "tenant_id" => $tenant->id,
                "active" => true
            ],
            [
                "portal_id" => 4,
                "tenant_id" => $tenant->id,
                "active" => true
            ],
            [
                "portal_id" => 5,
                "tenant_id" => $tenant->id,
                "active" => true
            ],
            [
                "portal_id" => 6,
                "tenant_id" => $tenant->id,
                "active" => true
            ]
            ]);
        }
}
