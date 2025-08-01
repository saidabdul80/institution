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
        Schema::create('payment_splittings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('beneficiary_id');
            $table->decimal('share', 10, 2); // Assuming share is a decimal value
            $table->decimal('collected_share', 10, 2)->default(0); // Default to 0
            $table->string('status')->default('pending'); // Default status
            $table->string('transfer_reference')->nullable(); // Can be null
            $table->timestamps(); // created_at and updated_at
            
            // Foreign key constraints
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('payment_id');
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
        Schema::dropIfExists('payment_splittings');
    }
};
