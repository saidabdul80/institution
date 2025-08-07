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
        Schema::table('documents', function (Blueprint $table) {
            // Document verification status
            $table->enum('verification_status', ['pending', 'approved', 'rejected', 'resubmit'])
                  ->default('pending')
                  ->after('file_path');
            
            // Verification details
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verification_notes');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            
            // Foreign key constraint
            $table->foreign('verified_by')->references('id')->on('staffs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['verified_by']);
            
            // Drop columns
            $table->dropColumn([
                'verification_status',
                'verification_notes',
                'verified_by',
                'verified_at'
            ]);
        });
    }
};
