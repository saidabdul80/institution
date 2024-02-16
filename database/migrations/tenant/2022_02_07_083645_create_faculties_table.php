<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacultiesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
            Schema::create('faculties', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('abbr');                   
                $table->softDeletes();
                $table->integer("deleted_by")->nullable();                
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
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();        
        Schema::dropIfExists('faculties');
        Schema::enableForeignKeyConstraints();
    }

}
