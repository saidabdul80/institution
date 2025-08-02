<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentCategory;
class PaymentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       
        $categories = [
            [
                "name"=>"Accommodation Fee",
                "short_name"=>"accommodation_fee",
                "charges" => 500.00,
                "charge_type" => "fixed",
                "charge_description" => "Processing fee for accommodation payment"
            ],
            [
                "name"=>"Registration Fee",
                "short_name"=>"registration_fee",
                "charges" => 300.00,
                "charge_type" => "fixed",
                "charge_description" => "Processing fee for registration payment"
            ],
            [
                "name"=>"Miscellaneous Fee",
                "short_name"=>"miscellaneous_fee",
                "charges" => 200.00,
                "charge_type" => "fixed",
                "charge_description" => "Processing fee for miscellaneous payment"
            ],
            [
                "name"=>"Acceptance Fee",
                "short_name"=>"acceptance_fee",
                "charges" => 2.5,
                "charge_type" => "percentage",
                "charge_description" => "2.5% processing fee for acceptance payment"
            ],
            [
                "name"=>"Application Fee",
                "short_name"=>"application_fee",
                "charges" => 100.00,
                "charge_type" => "fixed",
                "charge_description" => "Processing fee for application payment"
            ],
        ];

        foreach ($categories as $category) {
            PaymentCategory::updateOrCreate(
                ['short_name' => $category['short_name']],
                $category
            );
        }

    }
}