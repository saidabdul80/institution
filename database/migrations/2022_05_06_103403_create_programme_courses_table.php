<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgrammeCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programme_courses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('programme_id')->unsigned();
            $table->bigInteger('programme_curriculum_id')->unsigned();
            $table->bigInteger('course_id')->unsigned();
            $table->unsignedBigInteger('course_category_id')->nullable();
            $table->unsignedBigInteger('course_department_id')->nullable();
            $table->bigInteger('level_id')->unsigned();
            $table->enum('status',['core','elective'])->nullable();
            $table->enum('tp', ['yes', 'no'])->default('no');
            $table->bigInteger("programme_option_id")->nullable();
            $table->string("require_invoice_type_payment_ids")->nullable();
            $table->enum('special_course', ["None","All","Blind","Deaf","Dumb","Handicapped","Others"])->default("None");
            $table->bigInteger('semester_id')->unsigned();
            $table->bigInteger('session_id')->nullable();
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('programme_courses');
    }
}
