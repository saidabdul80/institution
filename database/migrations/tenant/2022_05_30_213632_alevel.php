<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alevels', function (Blueprint $table) {
            $table->id();
            $table->string('institution_attended', 150);
            $table->string('programme_studied', 150);
            $table->date('from');
            $table->date('to');
            $table->bigInteger('applicant_id')->unsigned();
            $table->bigInteger('certificate_id')->unsigned()->nullable();
            $table->string('class_of_certificate')->nullable();
            $table->bigInteger('qualification_id')->nullable()->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->double('cgpa', 8, 2)->nullable();
            $table->integer("deleted_by")->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('applicant_id')->references('id')->on('applicants')->cascadeOnUpdate()->cascadeOnDelete();
            // $table->foreign('certificate_id')->references('id')->on('applicants_certificates')->cascadeOnUpdate()->cascadeOnDelete();
            // $table->foreign('qualification_id')->references('id')->on('applicants_qualifications')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('alevels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('alevels');
        Schema::enableForeignKeyConstraints();
    }
}
