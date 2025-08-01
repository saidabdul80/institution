<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLGAsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
            Schema::create('l_g_as', function (Blueprint $table) {
                $table->id();         
                $table->bigInteger('state_id')->unsigned();                 
                $table->string('name');
                $table->string('slogan')->nullable();
                $table->foreign('state_id')->references('id')->on('states')->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::dropIfExists('l_g_as');
        Schema::enableForeignKeyConstraints();
    }
}
