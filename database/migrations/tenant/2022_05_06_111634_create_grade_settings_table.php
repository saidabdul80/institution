<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGradeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('grade_settings'))
        {
            Schema::create('grade_settings', function (Blueprint $table) {
                $table->id();
                $table->float('min_score');
                $table->float('max_score');
                $table->char('grade');
                $table->float('grade_point');
                $table->enum('status', ['pass', 'fail'])->default('pass');
                $table->bigInteger('created_by')->unsigned();
                $table->bigInteger('updated_by')->unsigned()->nullable();
                $table->bigInteger('deleted_by')->unsigned()->nullable();
                $table->softDeletes();
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
        Schema::dropIfExists('grade_settings');
    }
}
