<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentCoursesGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_courses_grades', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->unsigned()->index('student_id');
            $table->bigInteger('programme_type_id');
            $table->bigInteger('faculty_id')->unsigned();
            $table->bigInteger('department_id')->unsigned();
            $table->bigInteger('programme_id')->unsigned();
            $table->bigInteger('programme_curriculum_id')->unsigned();  
            $table->bigInteger('session_id')->unsigned()->index('session_id');
            $table->bigInteger('entry_mode_id')->unsigned();
            $table->bigInteger('level_id')->unsigned()->index('level_id');
            $table->bigInteger('semester_id')->unsigned();
            $table->bigInteger('course_id')->unsigned();
            $table->unsignedBigInteger('course_category_id')->nullable();
            $table->bigInteger('course_department_id')->unsigned()->nullable();
            $table->float('ca_score')->nullable();
            $table->float('exam_score')->nullable();
            $table->bigInteger('grade_id')->nullable();
            $table->enum('status', ['published', 'unpublished'])->default('unpublished');
            $table->enum('grade_status', ['passed', 'failed', 'cleared'])->nullable();
            $table->enum('tp', ['yes', 'no'])->default('no');
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
            $table->string('token',500)->unique();
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
        Schema::dropIndex(['session_id','level_id', 'student_id']);
        Schema::dropIfExists('student_courses_grades');
    }
}
