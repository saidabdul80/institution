<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSemesterResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_semester_results', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('student_id')->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->integer('semester');
            $table->decimal('gpa', 3, 2)->default(0.00);
            $table->integer('total_credit_units')->default(0);
            $table->decimal('total_grade_points', 8, 2)->default(0.00);
            $table->timestamps();

            // Indexes
            $table->index(['student_id', 'session_id', 'semester']);
            $table->unique(['student_id', 'session_id', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_semester_results');
    }
}
