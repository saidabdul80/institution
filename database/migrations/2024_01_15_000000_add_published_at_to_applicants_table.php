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
            $table->timestamp('published_at')->nullable()->after('admission_status');
            $table->string('published_by')->nullable()->after('published_at');
            $table->text('publication_notes')->nullable()->after('published_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'published_by', 'publication_notes']);
        });
    }
};
