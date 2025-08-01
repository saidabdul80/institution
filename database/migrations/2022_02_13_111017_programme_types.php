<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProgrammeTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('programme_types')) 
        {
            Schema::create('programme_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');               
                $table->string('short_name');               
                $table->softDeletes();
                $table->integer("deleted_by")->nullable();           
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
        Schema::dropIfExists('programme_types');
    }
}
