<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRemarkSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('remark_settings'))
        {
            Schema::create('remark_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('programme_type_id');
                $table->unsignedBigInteger('session_id');
                $table->decimal('min_cgpa', 5,2);
                $table->decimal('max_cgpa',5,2);
                $table->string('remark');
                $table->string('grade');
                $table->enum('status', ['pass', 'fail']);
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
        Schema::dropIfExists('remark_settings');
    }
}
