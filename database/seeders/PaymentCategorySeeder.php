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
       
        PaymentCategory::insert([
            [
                "name"=>"Accommodation Fee",
                "short_name"=>"accommodation_fee"
            ],
            [
                "name"=>"Registration Fee",
                "short_name"=>"registration_fee"
            ],
            [
                "name"=>"Miscellaneous Fee",
                "short_name"=>"miscellaneous_fee"
            ],
            [
                "name"=>"Acceptance Fee",
                "short_name"=>"acceptance_fee"
            ],
            [
                "name"=>"Application Fee",
                "short_name"=>"application_fee"
            ],
        ]);

    }
}