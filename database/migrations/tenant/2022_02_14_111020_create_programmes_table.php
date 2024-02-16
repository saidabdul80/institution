<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProgrammesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
            Schema::create('programmes', function (Blueprint $table) {
                $table->id();
                $table->bigInteger("option_selection_level_id")->nullable();
                $table->bigInteger('faculty_id')->unsigned()->nullable(); 
                $table->bigInteger('department_id')->unsigned()->nullable(); 
                $table->bigInteger('programme_type_id')->unsigned()->nullable(); 
                $table->bigInteger('entry_mode_id')->unsigned()->nullable(); 
                $table->string("name");       
                $table->string("code")->nullable();       
                $table->integer("maximum_credit_unit");       
                $table->integer("minimum_credit_unit");       
                $table->integer("max_duration")->nullable();    
                $table->unsignedBigInteger('graduation_level_id')->nullable();  
                $table->integer('tp_max_carry_over')->nullable();  
                $table->integer("duration");       
                $table->string("required_subjects");       
                $table->string("accepted_grades");                   
                $table->enum("study_mode",['Full Time', 'Part Time'])->default('Full Time');
                $table->enum("status",['Active', 'Inactive'])->default('Active');
                $table->string('department_combination')->nullable();
                $table->float('min_credit_unit_req')->nullable();                       
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
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();        
        Schema::dropIfExists('programmes');
        Schema::enableForeignKeyConstraints();
    }
}
