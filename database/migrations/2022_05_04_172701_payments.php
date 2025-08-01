<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Payments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("payments")) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->float('amount');
                $table->float('paid_amount')->comment("amount paid to us")->nullable();
                $table->string('payment_reference')->nullable();
                $table->string('payment_channel')->nullable();
                $table->json('gateway_response')->nullable();
                $table->string('transaction_id')->nullable();
                $table->string('ourTrxRef');
                $table->enum('status',['successful', 'failed','pending'])->default('pending');
                $table->bigInteger('invoice_id')->unsigned()->nullable();
                $table->bigInteger('owner_id')->unsigned();
                $table->enum('owner_type', ['applicant', 'student']);
                $table->float('charges')->default('0.00');            
                $table->bigInteger('session_id')->unsigned();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_mode')->nullable();
                $table->softDeletes();
                $table->integer("deleted_by")->nullable();
                $table->timestamps();
                $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::disableForeignKeyConstraints();
        Schema::dropIfEXists('payments');
        Schema::enableForeignKeyConstraints();
    }
}
