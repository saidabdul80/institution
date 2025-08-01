<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number');
            $table->string('bank_code');
            $table->string('currency', 3)->default('NGN'); // 3-letter currency code
            $table->string('recipient_code')->nullable();
            $table->decimal('share', 10, 2)->default(0);
            $table->string('nuban')->nullable();
            $table->string('beneficiary_type')->nullable();
            $table->string('account_name');
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->decimal('cap_share', 10, 2)->nullable();
            $table->string('subaccount_code')->nullable();
            $table->string('status')->default('active');
            $table->json('options')->nullable();
            $table->json('gateways')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('account_number');
            $table->index('bank_code');
            $table->index('recipient_code');
            $table->index('beneficiary_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
           Schema::dropIfExists('beneficiaries');
    }
};
