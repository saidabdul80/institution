<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Staff extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staffs', function (Blueprint $table) {
            $table->id();
            $table->string('staff_number')->unique();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('surname');
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('password');
            $table->bigInteger('staff_role_id')->unsigned()->nullable(); 
            $table->bigInteger('faculty_id')->unsigned()->nullable(); 
            $table->bigInteger('department_id')->unsigned()->nullable(); 
            $table->enum('type', ['academic','non-academic'])->default('academic');
            $table->enum('first_login', ['true','false'])->default('true');            
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnUpdate()->cascadeOnDelete();                            
            $table->foreign('faculty_id')->references('id')->on('faculties')->cascadeOnUpdate()->cascadeOnDelete();                            
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('staffs');
        Schema::enableForeignKeyConstraints();
    }
}
