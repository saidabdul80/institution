<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
            Schema::create('application_types', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->mediumText("description")->nullable();
                $table->softDeletes();
                $table->integer("deleted_by")->nullable();
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
        Schema::table('application_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('application_types');
    }
}
