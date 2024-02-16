<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Stancl\Tenancy\Events\TenantCreated;
use App\Models\Beneficiaries;

class SetupDefaultBeneficiaries implements ShouldQueue
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

        Beneficiaries::insert([
            [
                'tenant_id' => $tenant->id,
                'type' => "main",
                'first_name' => 'JD LAB',
                'account_number' => '0123456440',
                'bank_name' => 'Access Bank',
                'bank_code' => '0976',
                'commission_type' => 'percentage',
                'commission_amount' => '3',
                'middle_name' => '',
                'surname' => '',
                'phone_number' => '',
                'email' => '',
                'address' => '',
                'active' => true
            ]
            ]);
        }
}
