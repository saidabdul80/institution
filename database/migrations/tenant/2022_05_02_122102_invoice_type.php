
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoiceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_types',function (Blueprint $table) {
            $table->id();
            $table->bigInteger('session_id')->nullable()->unsigned();
            $table->bigInteger('semester_id')->nullable()->unsigned();
            $table->string('name');
            $table->float('amount');
            $table->string('description')->nullable();

            $table->enum('owner_type',['applicant','student'])->default("student");
            $table->string('gender')->nullable();
            $table->bigInteger('level_id')->nullable()->unsigned();
            $table->bigInteger('programme_id')->nullable()->unsigned();
            $table->bigInteger('programme_type_id')->nullable()->unsigned();
            $table->bigInteger('department_id')->nullable()->unsigned();
            $table->bigInteger('faculty_id')->nullable()->unsigned();
            $table->bigInteger('entry_mode_id')->nullable()->unsigned();
            $table->bigInteger('state_id')->nullable()->unsigned();
            $table->bigInteger('lga_id')->nullable()->unsigned();
            $table->bigInteger('country_id')->nullable()->unsigned();
            $table->bigInteger('payment_category_id')->unsigned();
            $table->bigInteger("campus_id")->nullable()->unsigned();
            $table->bigInteger('block_id')->nullable()->unsigned();
            $table->bigInteger("room_id")->nullable()->unsigned();
            
            $table->unsignedBigInteger('entry_level_id')->nullable();
            $table->foreign('level_id')->references('id')->on('levels')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('programme_id')->references('id')->on('programmes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('programme_type_id')->references('id')->on('programme_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('faculty_id')->references('id')->on('faculties')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('state_id')->references('id')->on('states')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('lga_id')->references('id')->on('l_g_as')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('date_open')->nullable();
            $table->dateTime('date_close')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->integer("deleted_by")->nullable();
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
        Schema::dropIfEXists('invoice_types');
        Schema::enableForeignKeyConstraints();
    }
}
