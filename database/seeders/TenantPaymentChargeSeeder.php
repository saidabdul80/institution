<?php

namespace Database\Seeders;

use App\Models\TenantPaymentCharge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantPaymentChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tenant_payment_charges = array(
            array('id' => '1','tenant_id' => '0','payment_category_id' => '1','payment_type' => 'percent','amount' => '7.00','standard_charge' => '7.00','created_at' => NULL,'updated_at' => NULL),
            array('id' => '2','tenant_id' => '0','payment_category_id' => '2','payment_type' => 'percent','amount' => '7.00','standard_charge' => '7.00','created_at' => NULL,'updated_at' => NULL),
            array('id' => '3','tenant_id' => '0','payment_category_id' => '3','payment_type' => 'percent','amount' => '7.00','standard_charge' => '7.00','created_at' => NULL,'updated_at' => NULL),
            array('id' => '4','tenant_id' => '0','payment_category_id' => '4','payment_type' => 'percent','amount' => '7.00','standard_charge' => '7.00','created_at' => NULL,'updated_at' => NULL),
            array('id' => '5','tenant_id' => '0','payment_category_id' => '5','payment_type' => 'percent','amount' => '7.00','standard_charge' => '7.00','created_at' => NULL,'updated_at' => NULL)
          );
          
        TenantPaymentCharge::insert($tenant_payment_charges);
    }
}
