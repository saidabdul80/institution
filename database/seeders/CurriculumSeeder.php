<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curriculum;
use App\Models\ProgrammeCurriculum;
use App\Models\Programme;

class CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default curriculum
        $curriculum = Curriculum::create([
            'name' => '2024/2025 Academic Curriculum',
            'description' => 'Default curriculum for the 2024/2025 academic session',
            'academic_year' => 2024,
            'is_active' => true,
            'effective_date' => now(),
            'expiry_date' => null,
            'metadata' => [
                'version' => '1.0',
                'created_reason' => 'Initial curriculum setup'
            ],
            'created_by' => 'System'
        ]);

        // Get all existing programmes and create programme curriculums
        $programmes = Programme::all();
        
        foreach ($programmes as $programme) {
            ProgrammeCurriculum::create([
                'curriculum_id' => $curriculum->id,
                'programme_id' => $programme->id,
                'name' => $programme->name,
                'description' => $programme->description ?? 'Programme curriculum for ' . $programme->name,
                'is_active' => true,
                'duration_years' => 4, // Default duration
                'duration_semesters' => 8,
                'minimum_cgpa' => 1.00,
                'minimum_credit_units' => 120,
                'admission_requirements' => [
                    'minimum_jamb_score' => 180,
                    'required_subjects' => [],
                    'additional_requirements' => []
                ],
                'graduation_requirements' => [
                    'minimum_cgpa' => 1.00,
                    'minimum_credit_units' => 120,
                    'required_courses' => [],
                    'additional_requirements' => []
                ],
                'metadata' => [
                    'migrated_from_programme' => $programme->id,
                    'migration_date' => now()
                ],
                'created_by' => 'System'
            ]);
        }

        $this->command->info('Curriculum and Programme Curriculums seeded successfully!');
        $this->command->info("Created 1 curriculum with {$programmes->count()} programme curriculums");
    }
}
