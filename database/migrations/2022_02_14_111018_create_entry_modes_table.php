<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntryModesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        if (!Schema::hasTable('entry_modes')) {
            Schema::create('entry_modes', function (Blueprint $table) {
                $table->id();
                $table->string("title");
                $table->string("code");
                $table->mediumText("description")->nullable();
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
         Schema::table('entry_modes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('entry_modes');
    }
}
