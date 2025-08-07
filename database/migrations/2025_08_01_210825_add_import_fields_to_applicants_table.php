<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Add JAMB subject scores as JSON column
            $table->json('jamb_subject_scores')->nullable()->after('jamb_number');

            // Add import tracking fields
            $table->boolean('is_imported')->default(false)->after('jamb_subject_scores');
            $table->string('import_batch_id')->nullable()->after('is_imported');
            $table->timestamp('imported_at')->nullable()->after('import_batch_id');

            // Add payment status tracking
            $table->boolean('application_fee_paid')->default(false)->after('imported_at');
            $table->timestamp('application_fee_paid_at')->nullable()->after('application_fee_paid');
            $table->boolean('acceptance_fee_paid')->default(false)->after('application_fee_paid_at');
            $table->timestamp('acceptance_fee_paid_at')->nullable()->after('acceptance_fee_paid');
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
            // Drop the new columns
            $table->dropColumn([
                'jamb_subject_scores',
                'is_imported',
                'import_batch_id',
                'imported_at',
                'application_fee_paid',
                'application_fee_paid_at'
            ]);
        });
    }
};
