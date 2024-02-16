<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
            Schema::create('countries', function (Blueprint $table) {
                $table->id('id');                
                $table->string('short_name');
                $table->string('name');
                $table->integer('phone_code');
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
        Schema::dropIfExists('countries');
        Schema::enableForeignKeyConstraints();
    }
}
