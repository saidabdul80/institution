<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\InvoiceType;
use App\Models\PaymentCategory;

class InvoiceTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $id = PaymentCategory::paymentId("accommodation_fee");
        InvoiceType::create([
            "name" => "Accommodation Fee",
            "amount" => 13000,
            "owner_type" => "student",
            "payment_category_id" => $id

        ]);

        $id = PaymentCategory::paymentId("application_fee");        
        InvoiceType::create([
            "name" => "Application Fee",
            "amount" => 2000,
            "owner_type" => "applicant",
            "payment_category_id" => $id

        ]);

        $id = PaymentCategory::paymentId("acceptance_fee");
        InvoiceType::create([
            "name" => "Acceptance Fee",
            "amount" => 10000,
            "owner_type" => "applicant",
            "payment_category_id" => $id

        ]);

        $id = PaymentCategory::paymentId("registration_fee");
        InvoiceType::create([
            "name" => "School Fees",
            "amount" => 50000,
            "owner_type" => "applicant",
            "payment_category_id" => $id

        ]);
        // $this->call("OthersTableSeeder");
    }
}
