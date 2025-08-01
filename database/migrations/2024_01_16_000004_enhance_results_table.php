<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            if (!Schema::hasTable('results')) {
            Schema::create('results', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('student_id')->unsigned();
                $table->bigInteger('course_id')->unsigned();
                $table->bigInteger('session_id')->unsigned();
                $table->integer('semester');
                $table->decimal('ca_score', 5, 2)->nullable();
                $table->decimal('exam_score', 5, 2)->nullable();
                $table->decimal('total_score', 5, 2)->nullable();
                $table->string('grade', 2)->nullable();
                $table->timestamps();

                // Basic indexes
                $table->index(['student_id', 'course_id', 'session_id', 'semester']);
                $table->index(['session_id', 'semester']);

                // Basic foreign keys
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
                $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            });
        }


        Schema::table('results', function (Blueprint $table) {
            // Add new columns for enhanced result management
            $table->string('result_token')->nullable()->after('grade');
            $table->decimal('grade_point', 3, 2)->nullable()->after('grade');
            $table->integer('credit_unit')->nullable()->after('grade_point');
            $table->decimal('quality_point', 5, 2)->nullable()->after('credit_unit');
            
            // Result submission tracking
            $table->bigInteger('submitted_by')->unsigned()->nullable()->after('quality_point');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->bigInteger('approved_by')->unsigned()->nullable()->after('submitted_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Result status
            $table->enum('result_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('result_status');
            
            // Audit trail
            $table->json('score_history')->nullable()->after('rejection_reason');
            $table->integer('revision_count')->default(0)->after('score_history');
            
            // Add indexes
            $table->index(['result_token']);
            $table->index(['result_status']);
            $table->index(['submitted_at']);
            $table->index(['approved_at']);
            
            // Add foreign keys
            $table->foreign('submitted_by')->references('id')->on('staffs')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('staffs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('results', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            
            // Drop indexes
            $table->dropIndex(['result_token']);
            $table->dropIndex(['result_status']);
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['approved_at']);
            
            // Drop columns
            $table->dropColumn([
                'result_token',
                'grade_point',
                'credit_unit',
                'quality_point',
                'submitted_by',
                'submitted_at',
                'approved_by',
                'approved_at',
                'result_status',
                'rejection_reason',
                'score_history',
                'revision_count'
            ]);
        });
    }
}
