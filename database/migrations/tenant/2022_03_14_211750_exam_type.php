<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExamType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();                        
            $table->string('name');
            $table->string('description');       
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
        //
        Schema::dropIfEXists('exam_types');
    }
}
