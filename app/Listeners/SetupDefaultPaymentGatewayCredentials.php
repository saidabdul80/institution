<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Stancl\Tenancy\Events\TenantCreated;
use App\Models\TenantPaymentGateway;

class SetupDefaultPaymentGatewayCredentials implements ShouldQueue
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

        TenantPaymentGateway::insert([
            [
                'tenant_id' => $tenant->id,
                'public_key' => 'U09MRHw0MDgxOTUzOHw2ZDU4NGRhMmJhNzVlOTRiYmYyZjBlMmM1YzUyNzYwZTM0YzRjNGI4ZTgyNzJjY2NjYTBkMDM0ZDUyYjZhZWI2ODJlZTZjMjU0MDNiODBlMzI4YWNmZGY2OWQ2YjhiYzM2N2RhMmI1YWEwYTlmMTFiYWI2OWQxNTc5N2YyZDk4NA==',
                'secret_key' => 'U09MRHw0MDgxOTUzOHw2ZDU4NGRhMmJhNzVlOTRiYmYyZjBlMmM1YzUyNzYwZTM0YzRjNGI4ZTgyNzJjY2NjYTBkMDM0ZDUyYjZhZWI2ODJlZTZjMjU0MDNiODBlMzI4YWNmZGY2OWQ2YjhiYzM2N2RhMmI1YWEwYTlmMTFiYWI2OWQxNTc5N2YyZDk4NA==',
                'hash_key' => '',
                'extra' => json_encode([
                    'merchant_id' => '2547916',
                    'api_key' => '1946',
                    'service_type_id' => '4430731',
                ]),
                'payment_gateway_id' => '1',
                'payment_categories' => json_encode([
                    "accommodation_fee" => "4430731",
                    "registration_fee" => "4430731",
                    "miscellaneous_fee" => "4430731",
                    "acceptance_fee" => "4430731",
                    "application_fee" => "4430731"
                ])
                ],
                [
                'tenant_id' => $tenant->id,
                'public_key' => 'pk_test_123456789',
                'secret_key' => 'U09MRHw0MDgxOTUzOHw2ZDU4NGRhMmJhNzVlOTRiYmYyZjBlMmM1YzUyNzYwZTM0YzRjNGI4ZTgyNzJjY2NjYTBkMDM0ZDUyYjZhZWI2ODJlZTZjMjU0MDNiODBlMzI4YWNmZGY2OWQ2YjhiYzM2N2RhMmI1YWEwYTlmMTFiYWI2OWQxNTc5N2YyZDk4NA==',
                'hash_key' => '',
                'extra' => json_encode([
                    'merchant_id' => 'merchant_id_test_123456789',
                    'api_key' => 'api_key_test_123456789',
                    'service_type_id' => 'service_type_id_test_123456789',
                ]),
                'payment_gateway_id' => '2',
                'payment_categories' => json_encode([
                    "accommodation_fee" => "3539",
                    "registration_fee" => "3539",
                    "miscellaneous_fee" => "3539",
                    "acceptance_fee" => "3539",
                    "application_fee" => "3539"
                ])
                ],
                [
                'tenant_id' => $tenant->id,
                'public_key' => 'pk_test_123456789',
                'secret_key' => 'U09MRHw0MDgxOTUzOHw2ZDU4NGRhMmJhNzVlOTRiYmYyZjBlMmM1YzUyNzYwZTM0YzRjNGI4ZTgyNzJjY2NjYTBkMDM0ZDUyYjZhZWI2ODJlZTZjMjU0MDNiODBlMzI4YWNmZGY2OWQ2YjhiYzM2N2RhMmI1YWEwYTlmMTFiYWI2OWQxNTc5N2YyZDk4NA==',
                'hash_key' => '',
                'extra' => json_encode([
                    'merchant_id' => 'merchant_id_test_123456789',
                    'api_key' => 'api_key_test_123456789',
                    'service_type_id' => 'service_type_id_test_123456789',
                ]),
                'payment_gateway_id' => '3',
                'payment_categories' => json_encode([
                    "accommodation_fee" => "3539",
                    "registration_fee" => "3539",
                    "miscellaneous_fee" => "3539",
                    "acceptance_fee" => "3539",
                    "application_fee" => "3539"
                ])
                ]
        ]);
    }
}
