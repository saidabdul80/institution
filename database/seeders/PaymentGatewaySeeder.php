<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySeeder extends Seeder
{
    public function run()
    {
        $gateways = [
            [
                'id' => 1,
                'name' => 'paystack',
                'description' => 'Paystack',
                'charge_amount_flat' => 100,
                'cap_amount' => 1000,
                'charge_amount_percentage' => 1,
                'logo' => '/paystack.jpg',
                'value' => 'paystack',
                'bg' => 'white',
                'created_at' => '2024-09-02 17:08:39',
                'updated_at' => '2024-09-02 17:08:39',
                'position' => 1
            ],
            [
                'id' => 2,
                'name' => 'wallet',
                'description' => 'Use Wallet Balance',
                'charge_amount_flat' => 0,
                'cap_amount' => 0,
                'charge_amount_percentage' => 0,
                'logo' => '/wallet.png',
                'value' => 'wallet',
                'bg' => '#D1EFDF',
                'created_at' => '2024-09-02 17:08:39',
                'updated_at' => '2025-01-13 17:18:31',
                'position' => 4
            ],
            [
                'id' => 3,
                'name' => 'remita',
                'description' => 'Pay with Remita',
                'charge_amount_flat' => 100,
                'cap_amount' => 1000,
                'charge_amount_percentage' => 1,
                'logo' => '/remita.png',
                'value' => 'remita',
                'bg' => 'white',
                'created_at' => null,
                'updated_at' => '2025-01-13 17:18:31',
                'position' => 2
            ],
            [
                'id' => 4,
                'name' => 'Pay with Reference',
                'description' => 'Pay with Reference',
                'charge_amount_flat' => 0,
                'cap_amount' => 0,
                'charge_amount_percentage' => 0,
                'logo' => '/star.png',
                'value' => 'Pay with Reference',
                'bg' => 'white',
                'created_at' => null,
                'updated_at' => '2025-01-13 17:18:31',
                'position' => 5
            ],
            [
                'id' => 5,
                'name' => 'interswitch',
                'description' => 'Interswitch',
                'charge_amount_flat' => 0,
                'cap_amount' => 0,
                'charge_amount_percentage' => 0,
                'logo' => '/interswitch.png',
                'value' => 'Interswitch',
                'bg' => 'white',
                'created_at' => null,
                'updated_at' => null,
                'position' => 4
            ],
            [
                'id' => 7,
                'name' => 'Paymish',
                'description' => 'Paymish',
                'charge_amount_flat' => 0,
                'cap_amount' => 0,
                'charge_amount_percentage' => 0,
                'logo' => '/paymish.png',
                'value' => 'Paymish',
                'bg' => 'white',
                'created_at' => null,
                'updated_at' => null,
                'position' => 3
            ],
            [
                'id' => 9,
                'name' => 'etransact',
                'description' => 'Etranzact',
                'charge_amount_flat' => 0,
                'cap_amount' => 0,
                'charge_amount_percentage' => 0,
                'logo' => '/etranzact.png',
                'value' => 'Etranzact',
                'bg' => 'white',
                'created_at' => '2025-06-19 04:34:13',
                'updated_at' => '2025-06-19 04:34:13',
                'position' => 4
            ]
        ];

        DB::table('payment_gateways')->insert($gateways);
    }
}