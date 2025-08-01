<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OlevelResults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('olevel_results', function (Blueprint $table) {

            $table->id();
            $table->bigInteger('applicant_id')->unsigned();
            $table->bigInteger('exam_type_id')->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->string('examination_number', 30);
            $table->string('scratch_card', 30)->nullable();
            $table->string('pin', 30)->nullable();
            $table->string('serial_number', 30)->nullable();
            $table->json('subjects_grades')->nullable();
            $table->string('month', 12);
            $table->string('year', 4);
            $table->softDeletes();
            $table->integer("deleted_by")->nullable();
            $table->timestamps();

            $table->foreign('applicant_id')->references('id')->on('applicants')->cascadeOnUpdate()->cascadeOnDelete();            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('olevel_results', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('olevel_results');
        Schema::enableForeignKeyConstraints();
    }
}
