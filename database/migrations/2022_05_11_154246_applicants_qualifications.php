<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ApplicantsQualifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicants_qualifications', function (Blueprint $table) {
            $table->id();            
            $table->bigInteger('applicant_id')->unsigned(); 
            $table->bigInteger('qualification_id')->unsigned(); 
            $table->foreign('applicant_id')->references('id')->on('applicants')->cascadeOnUpdate()->cascadeOnDelete();            
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
        Schema::table('applicants_qualifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();        
        Schema::dropIfExists('applicants_qualifications');
        Schema::enableForeignKeyConstraints();
    }
}
