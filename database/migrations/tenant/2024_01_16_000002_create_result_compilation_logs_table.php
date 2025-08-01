<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultCompilationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_compilation_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('session_id')->unsigned();
            $table->integer('semester');
            $table->bigInteger('level_id')->unsigned();
            $table->bigInteger('programme_id')->unsigned()->nullable();
            $table->bigInteger('department_id')->unsigned()->nullable();
            $table->bigInteger('faculty_id')->unsigned()->nullable();
            
            // Compilation Details
            $table->enum('compilation_type', ['semester', 'session', 'level', 'programme'])->default('semester');
            $table->integer('students_processed')->default(0);
            $table->integer('results_processed')->default(0);
            $table->text('compilation_summary')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_seconds')->nullable();
            
            // Metadata
            $table->bigInteger('compiled_by')->unsigned();
            $table->json('compilation_parameters')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['session_id', 'semester', 'level_id']);
            $table->index(['status', 'created_at']);
            
            // Foreign Keys
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
            $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('cascade');
            $table->foreign('compiled_by')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('result_compilation_logs');
    }
}
