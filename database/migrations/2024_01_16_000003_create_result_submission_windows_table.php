<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultSubmissionWindowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_submission_windows', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('session_id')->unsigned();
            $table->integer('semester');
            $table->bigInteger('faculty_id')->unsigned()->nullable();
            $table->bigInteger('department_id')->unsigned()->nullable();
            $table->bigInteger('programme_id')->unsigned()->nullable();
            $table->bigInteger('level_id')->unsigned()->nullable();
            
            // Window Details
            $table->string('window_name');
            $table->text('description')->nullable();
            
            // Timing
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->timestamp('extended_to')->nullable();
            
            // Permissions
            $table->json('allowed_roles')->nullable(); // ['lecturer', 'examiner', 'coordinator']
            $table->boolean('allow_late_submission')->default(false);
            $table->integer('late_submission_penalty_days')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['upcoming', 'open', 'closed', 'extended'])->default('upcoming');
            
            // Notifications
            $table->boolean('send_opening_notification')->default(true);
            $table->boolean('send_closing_notification')->default(true);
            $table->integer('reminder_days_before_closing')->default(3);
            
            // Metadata
            $table->bigInteger('created_by')->unsigned();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['session_id', 'semester'], 'session_semester_index_w');
            $table->index(['opens_at', 'closes_at'], 'opens_at_closes_at_index');
            $table->index(['status', 'is_active'], 'status_is_active_index');
            
            // Foreign Keys
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('staffs')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('staffs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('result_submission_windows');
    }
}
