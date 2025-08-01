<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
            Schema::create('levels', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->enum('order', ['0','1','2','3','4','5','6','7','8','9','spill']);
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
        Schema::table('levels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();        
        Schema::dropIfExists('levels');
        Schema::enableForeignKeyConstraints();
    }
}
