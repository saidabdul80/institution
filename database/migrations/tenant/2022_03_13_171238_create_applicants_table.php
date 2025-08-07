<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

            Schema::create('applicants', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('surname');
                $table->string('phone_number')->nullable();
                $table->enum('gender',['male', 'female','other'])->nullable();
                $table->string('email')->unique();                
                $table->string('application_number')->unique();       
                $table->integer('batch_id')->nullable();            
                $table->bigInteger('session_id')->unsigned();                 
                $table->bigInteger('lga_id')->nullable()->unsigned();                 
                $table->bigInteger('country_id')->nullable()->unsigned();                 
                $table->bigInteger('state_id')->nullable()->unsigned();                 
                $table->bigInteger('applied_level_id')->nullable()->unsigned();                 
                $table->bigInteger('level_id')->nullable()->unsigned();                 
                $table->bigInteger('applied_programme_curriculum_id')->unsigned();    
                $table->bigInteger('programme_id')->unsigned()->nullable();   
                $table->bigInteger('programme_curriculum_id')->unsigned()->nullable();   
                $table->bigInteger('programme_type_id')->unsigned()->nullable();             
                $table->bigInteger('mode_of_entry_id')->unsigned()->nullable();                 
                $table->bigInteger('application_status_id')->unsigned()->nullable();                 
                $table->bigInteger('department_id')->unsigned()->nullable();                 
                $table->bigInteger('faculty_id')->unsigned()->nullable();                 
                $table->dateTime('date_of_birth')->nullable();                
                $table->string('years_of_experience')->nullable();                
                $table->enum('working_class',['Private', 'Public', 'NGO', 'Self Employed', 'None'])->nullable();  
                $table->enum('category',['Full Time', 'Part Time'])->nullable();                              
                $table->string('present_address')->nullable();
                $table->string('permanent_address')->nullable();
                $table->string('guardian_full_name',60)->nullable();
                $table->string('guardian_phone_number',14)->nullable();
                $table->string('guardian_address')->nullable();
                $table->string('guardian_email',60)->nullable();
                $table->string('guardian_relationship',30)->nullable();
                $table->string('sponsor_full_name',60)->nullable();
                $table->string('sponsor_type',30)->nullable();
                $table->string('sponsor_address')->nullable();
                $table->string('next_of_kin_full_name',60)->nullable();
                $table->string('next_of_kin_address')->nullable();
                $table->string('next_of_kin_phone_number',14)->nullable();
                $table->string('next_of_kin_relationship',30)->nullable();
                $table->bigInteger('wallet_number')->unsigned()->nullable();
                $table->string('prev_institution',150)->nullable();
                $table->string('prev_year_of_graduation',4)->nullable();
                $table->string('health_status',30)->nullable();
                $table->string('health_status_description',500)->nullable();
                $table->string('blood_group',5)->nullable();
                $table->enum('disability',["None","Blind","Deaf","Dumb","Handicapped","Others"])->default('None');
                $table->enum('religion',['Islam','Christian', 'Other'])->nullable();
                $table->enum('marital_status',['Single','Married','Divorced','Widowed'])->nullable();
                $table->enum('admission_status',['admitted','rejected', 'not admitted'])->default('not admitted');
                $table->string('admission_serial_number')->nullable();
                $table->enum('qualified_status',['qualified','not qualified','not verified'])->default('not verified');
                $table->enum('final_submission',['0','1'])->default('0');
                $table->float('application_progress')->nullable();
                $table->dateTime('logged_in_time')->nullable();
                $table->integer('logged_in_count')->nullable();
                $table->string('picture',200)->nullable();
                $table->string('signatuare',200)->nullable();            
                $table->string('jamb_number',200)->nullable();
                $table->string('scratch_card',200)->nullable();    
                $table->string('entrance_exam_score')->nullable();
                $table->enum('entrance_exam_status',['passed','failed','pending'])->default('pending');
                $table->softDeletes();
                $table->integer("deleted_by")->nullable();
                $table->string('password');
                $table->timestamps();
                $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('lga_id')->references('id')->on('l_g_as')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('country_id')->references('id')->on('countries')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('state_id')->references('id')->on('states')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('applied_level_id')->references('id')->on('levels')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('level_id')->references('id')->on('levels')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('applied_programme_curriculum_id')->references('id')->on('programmes')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('programme_id')->references('id')->on('programmes')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('programme_type_id')->references('id')->on('programme_types')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('mode_of_entry_id')->references('id')->on('entry_modes')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('application_status_id')->references('id')->on('application_statuses')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('department_id')->references('id')->on('departments')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('faculty_id')->references('id')->on('faculties')->cascadeOnUpdate()->cascadeOnDelete();                

            });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('applicants');
        Schema::enableForeignKeyConstraints();
    }
}
