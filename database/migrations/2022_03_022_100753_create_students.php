<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudents extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('surname');
                $table->string('phone_number');
                $table->string('gender',7)->nullable();
                $table->string('email')->unique();
                $table->string('matric_number')->unique();

                $table->bigInteger('application_id')->unsigned();
                $table->bigInteger('entry_session_id')->unsigned();
                $table->bigInteger('lga_id')->nullable()->unsigned();
                $table->bigInteger('country_id')->nullable()->unsigned();
                $table->bigInteger('state_id')->nullable()->unsigned();
                $table->bigInteger('applied_level_id')->nullable()->unsigned();
                $table->bigInteger('applied_programme_id')->unsigned();
                $table->bigInteger('programme_type_id')->unsigned();
                $table->bigInteger('programme_id')->unsigned();
                $table->bigInteger("programme_option_id")->nullable();
                $table->bigInteger('level_id')->unsigned()->index('level_id');
                $table->bigInteger('entry_level_id');                
                $table->bigInteger('mode_of_entry_id')->unsigned();
                $table->bigInteger('department_id')->unsigned();
                $table->bigInteger('faculty_id')->unsigned();
                $table->bigInteger('wallet_number')->unsigned()->nullable();
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
                $table->string('prev_institution',150)->nullable();
                $table->string('prev_year_of_graduation',4)->nullable();
                $table->string('health_status',30)->nullable();
                $table->string('health_status_description',500)->nullable();
                $table->string('blood_group',5)->nullable();
                $table->enum('disability',["None","Blind","Deaf","Dumb","Handicapped","Others"])->default('None');
                $table->enum('religion',['Islam','Christian', 'Other'])->nullable();
                $table->enum('marital_status',['Single','Married','Divorced','Widowed'])->nullable();
                $table->dateTime('logged_in_time')->nullable();
                $table->integer('logged_in_count')->nullable();
                $table->string('picture',200)->nullable();
                $table->string('signature',200)->nullable();                
                $table->integer('batch_id');
                $table->softDeletes();
                $table->integer("deleted_by")->nullable();
                $table->integer("updated_by")->nullable();
                $table->string('password');
                $table->integer('promote_count')->default(0);
                $table->enum('status', ['active','expel','rusticated','voluntary withdraw','academic withdrawal','death','deferment','suspension','graduated'])->default('active');
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
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('students');
        Schema::dropIndex('level_id');
    }

}
