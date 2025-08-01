<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSemesterGpaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_semester_gpa', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->integer('semester');
            $table->bigInteger('level_id')->unsigned();
            $table->bigInteger('programme_id')->unsigned();
            
            // Credit Unit Tracking
            $table->integer('registered_credit_units')->default(0); // RCU
            $table->integer('earned_credit_units')->default(0);     // ECU
            $table->integer('total_credit_points')->default(0);     // CP
            
            // GPA Calculation
            $table->decimal('gpa', 3, 2)->default(0.00);
            
            // Cumulative Tracking
            $table->integer('total_registered_credit_units')->default(0); // TRCU
            $table->integer('total_earned_credit_units')->default(0);     // TECU
            $table->integer('total_cumulative_points')->default(0);       // TCP
            $table->integer('total_department_credit_points')->default(0); // TDCP
            $table->decimal('previous_cgpa', 3, 2)->default(0.00);        // PCGPA
            $table->decimal('cgpa', 3, 2)->default(0.00);
            
            // Failed Courses (Carry Overs)
            $table->text('carry_over_courses')->nullable(); // COs
            
            // Academic Status
            $table->integer('number_of_semesters')->default(1); // NSS
            $table->enum('academic_status', ['good_standing', 'probation', 'withdrawal'])->default('good_standing');
            
            // Metadata
            $table->boolean('is_compiled')->default(false);
            $table->timestamp('compiled_at')->nullable();
            $table->bigInteger('compiled_by')->unsigned()->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['student_id', 'session_id', 'semester']);
            $table->unique(['student_id', 'session_id', 'semester']);
            
            // Foreign Keys
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
            $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('cascade');
            $table->foreign('compiled_by')->references('id')->on('staff')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_semester_gpa');
    }
}
