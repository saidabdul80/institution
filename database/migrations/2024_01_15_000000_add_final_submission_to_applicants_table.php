<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->timestamp('final_submitted_at')->nullable()->after('published_at');
            $table->boolean('is_final_submitted')->default(false)->after('final_submitted_at');
            $table->text('final_submission_notes')->nullable()->after('is_final_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['final_submitted_at', 'is_final_submitted', 'final_submission_notes']);
        });
    }
};
