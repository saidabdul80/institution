<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('course_categories'))
        {
            Schema::create('course_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('short_name');
                $table->enum('status',['Active','Inactive'])->default('Active');
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
        Schema::dropIfExists('course_categories');
    }
}
