<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('department_id')->unsigned()->nullable();
            $table->bigInteger('level_id')->unsigned()->nullable();
            $table->bigInteger('course_category_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('code');
            $table->float('credit_unit');
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->enum('tp', ['yes', 'no'])->default('no');
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('level_id')->references('id')->on('levels')->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::dropIfExists('courses');
    }
}
