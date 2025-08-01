<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffCourseAllocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_course_allocations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('staff_id')->unsigned();
            $table->bigInteger('course_id')->unsigned();
            $table->bigInteger('session_id')->unsigned();
            $table->integer('semester');
            $table->bigInteger('programme_id')->unsigned();
            $table->bigInteger('level_id')->unsigned();
            
            // Allocation Details
            $table->enum('allocation_type', ['lecturer', 'coordinator', 'examiner'])->default('lecturer');
            $table->text('remarks')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->bigInteger('allocated_by')->unsigned();
            $table->timestamp('allocated_at')->useCurrent();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['staff_id', 'session_id', 'semester'],'staff_session_semester_index');
            $table->index(['course_id', 'session_id', 'semester'],'course_session_semester_index');
            $table->unique(['staff_id', 'course_id', 'session_id', 'semester'],'staff_course_allocation_unique');
            
            // Foreign Keys
            // $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            // $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            // $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            // $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('cascade');
            // $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
            // $table->foreign('allocated_by')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_course_allocations');
    }
}
