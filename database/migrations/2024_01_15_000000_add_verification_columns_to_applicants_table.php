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
            // Document verification status
            // $table->enum('verification_status', ['pending', 'under_review', 'verified', 'rejected'])
            //       ->default('pending')
            //       ->after('admission_status');
            
            // // Document verification timestamps and staff
            // $table->timestamp('documents_verified_at')->nullable()->after('verification_status');
            // $table->unsignedBigInteger('documents_verified_by')->nullable()->after('documents_verified_at');
            
            // // Admission letter issuance tracking
            // $table->boolean('admission_letter_issued')->default(false)->after('documents_verified_by');
            // $table->timestamp('admission_letter_issued_at')->nullable()->after('admission_letter_issued');
            // $table->unsignedBigInteger('admission_letter_issued_by')->nullable()->after('admission_letter_issued_at');
            // $table->enum('admission_letter_type', ['standard', 'conditional', 'provisional'])
            //       ->nullable()
            //       ->after('admission_letter_issued_by');
            // $table->text('admission_letter_notes')->nullable()->after('admission_letter_type');
            
            // Foreign key constraints
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
            // Drop foreign keys first
            $table->dropForeign(['documents_verified_by']);
            $table->dropForeign(['admission_letter_issued_by']);
            
            // Drop columns
            $table->dropColumn([
                'verification_status',
                'documents_verified_at',
                'documents_verified_by',
                'admission_letter_issued',
                'admission_letter_issued_at',
                'admission_letter_issued_by',
                'admission_letter_type',
                'admission_letter_notes'
            ]);
        });
    }
};
