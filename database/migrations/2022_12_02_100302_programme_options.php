<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProgrammeOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programme_options', function(Blueprint $table){
            $table->id();
            $table->string('title');
            $table->bigInteger('programme_id');
            $table->string('code')->nullable();
            $table->enum('status',['Active', 'Inactive']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programme_options');
    }
}
