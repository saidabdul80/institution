<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Invoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_id')->unsigned();
            $table->enum('owner_type', ['applicant', 'student']);
            $table->bigInteger('invoice_type_id')->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->bigInteger('invoice_number');
            $table->float('amount');
            $table->float('charges')->default('0.00');
            $table->float('expected_charges')->default('0.00');
            $table->string('description')->nullable();
            $table->enum('status', ['paid', 'unpaid'])->default("unpaid");
            $table->string('paid_at')->nullable();
            $table->bigInteger('confirmed_by')->nullable();
            $table->foreign('invoice_type_id')->references('id')->on('invoice_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer("deleted_by")->nullable();
            $table->json('meta_data')->nullable();
            $table->enum('payment_channel', ['wallet', 'gateway'])->default("wallet");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfEXists('applicant_invoices');
        Schema::enableForeignKeyConstraints();
    }
}
