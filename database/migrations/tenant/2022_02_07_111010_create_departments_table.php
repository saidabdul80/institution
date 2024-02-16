<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('faculty_id')->unsigned();                 
                $table->string('name');
                $table->string('abbr');                
                $table->foreign('faculty_id')->references('id')->on('faculties')->cascadeOnUpdate()->cascadeOnDelete();                
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
        Schema::table('departments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();        
        Schema::dropIfExists('departments');
        Schema::enableForeignKeyConstraints();
    }
}
