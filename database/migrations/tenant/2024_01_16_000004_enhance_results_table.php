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
        // Check if results table exists, if not create it first
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

        // Now enhance the results table with new columns
        Schema::table('results', function (Blueprint $table) {
            // Check if columns don't already exist before adding them
            if (!Schema::hasColumn('results', 'result_token')) {
                $table->string('result_token')->nullable()->after('grade');
            }
            if (!Schema::hasColumn('results', 'grade_point')) {
                $table->decimal('grade_point', 3, 2)->nullable()->after('grade');
            }
            if (!Schema::hasColumn('results', 'credit_unit')) {
                $table->integer('credit_unit')->nullable()->after('grade_point');
            }
            if (!Schema::hasColumn('results', 'quality_point')) {
                $table->decimal('quality_point', 5, 2)->nullable()->after('credit_unit');
            }

            // Result submission tracking
            if (!Schema::hasColumn('results', 'submitted_by')) {
                $table->bigInteger('submitted_by')->unsigned()->nullable()->after('quality_point');
            }
            if (!Schema::hasColumn('results', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('results', 'approved_by')) {
                $table->bigInteger('approved_by')->unsigned()->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('results', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            // Result status
            if (!Schema::hasColumn('results', 'result_status')) {
                $table->enum('result_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->after('approved_at');
            }
            if (!Schema::hasColumn('results', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('result_status');
            }

            // Audit trail
            if (!Schema::hasColumn('results', 'score_history')) {
                $table->json('score_history')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('results', 'revision_count')) {
                $table->integer('revision_count')->default(0)->after('score_history');
            }
        });

        // Add indexes and foreign keys in a separate schema call to avoid conflicts
        Schema::table('results', function (Blueprint $table) {
            // Add indexes only if they don't exist
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('results');

            if (!isset($indexes['results_result_token_index'])) {
                $table->index(['result_token']);
            }
            if (!isset($indexes['results_result_status_index'])) {
                $table->index(['result_status']);
            }
            if (!isset($indexes['results_submitted_at_index'])) {
                $table->index(['submitted_at']);
            }
            if (!isset($indexes['results_approved_at_index'])) {
                $table->index(['approved_at']);
            }

            // Add foreign keys only if they don't exist
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('results');
            $foreignKeyNames = array_map(function($fk) { return $fk->getName(); }, $foreignKeys);

            if (!in_array('results_submitted_by_foreign', $foreignKeyNames)) {
                $table->foreign('submitted_by')->references('id')->on('staff')->onDelete('set null');
            }
            if (!in_array('results_approved_by_foreign', $foreignKeyNames)) {
                $table->foreign('approved_by')->references('id')->on('staff')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('results')) {
            Schema::table('results', function (Blueprint $table) {
                // Drop foreign keys first (only if they exist)
                $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('results');
                $foreignKeyNames = array_map(function($fk) { return $fk->getName(); }, $foreignKeys);

                if (in_array('results_submitted_by_foreign', $foreignKeyNames)) {
                    $table->dropForeign(['submitted_by']);
                }
                if (in_array('results_approved_by_foreign', $foreignKeyNames)) {
                    $table->dropForeign(['approved_by']);
                }

                // Drop indexes (only if they exist)
                $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('results');

                if (isset($indexes['results_result_token_index'])) {
                    $table->dropIndex(['result_token']);
                }
                if (isset($indexes['results_result_status_index'])) {
                    $table->dropIndex(['result_status']);
                }
                if (isset($indexes['results_submitted_at_index'])) {
                    $table->dropIndex(['submitted_at']);
                }
                if (isset($indexes['results_approved_at_index'])) {
                    $table->dropIndex(['approved_at']);
                }

                // Drop columns (only if they exist)
                $columns = Schema::getColumnListing('results');
                $columnsToRemove = [];

                $enhancedColumns = [
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
                ];

                foreach ($enhancedColumns as $column) {
                    if (in_array($column, $columns)) {
                        $columnsToRemove[] = $column;
                    }
                }

                if (!empty($columnsToRemove)) {
                    $table->dropColumn($columnsToRemove);
                }
            });
        }
    }
}
