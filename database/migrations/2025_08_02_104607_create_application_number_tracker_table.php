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
       // In the migration file
        // Schema::create('application_number_tracker', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('session_id');
        //     $table->string('prefix');
        //     $table->unsignedInteger('last_number')->default(0);
        //     $table->timestamps();

        //     $table->foreign('session_id')->references('id')->on('sessions');
        //     $table->unique(['session_id', 'prefix']);
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application_number_tracker');
    }
};
