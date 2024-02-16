<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentEnrollments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('student_enrollments'))
        {
            Schema::create('student_enrollments', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('owner_id');
                $table->enum('owner_type',['student', 'applicant'])->default('student');
                $table->bigInteger('session_id');
                $table->bigInteger('level_id_from')->nullable();
                $table->bigInteger('level_id_to')->nullable();
                $table->string('token')->unique();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
