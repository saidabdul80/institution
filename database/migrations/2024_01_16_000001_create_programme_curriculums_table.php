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
        Schema::create('programme_curriculums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curriculums')->onDelete('cascade');
            $table->foreignId('programme_id')->constrained('programmes')->onDelete('cascade');
            $table->string('name'); // Programme name for this curriculum
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('duration_years')->default(4);
            $table->integer('duration_semesters')->default(8);
            $table->decimal('minimum_cgpa', 3, 2)->default(1.00);
            $table->integer('minimum_credit_units')->default(120);
            $table->json('admission_requirements')->nullable();
            $table->json('graduation_requirements')->nullable();
            $table->json('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['curriculum_id', 'is_active']);
            $table->index(['programme_id', 'is_active']);
            $table->unique(['curriculum_id', 'programme_id', 'is_active'], 'unique_active_programme_curriculum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programme_curriculums');
    }
};
