<?php

namespace Modules\Result\Services;

use App\Models\Result;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentCourseRegistration as ModelsStudentCourseRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Result\Entities\GradeSetting;
use Modules\Result\Entities\ResultCompilationLog;
use Modules\Result\Entities\StudentSemesterGpa;

class ResultComputationService
{
    /**
     * Compile results for a specific session, semester, and level
     */
    public function compileResults($sessionId, $semester, $levelId, $programmeId = null, $programmeCurriculumId = null, $departmentId = null, $compiledBy = null)
    {
        $startTime = now();

        // Create compilation log
        $compilationLog = ResultCompilationLog::create([
            'session_id' => $sessionId,
            'semester' => $semester,
            'level_id' => $levelId,
            'programme_id' => $programmeId,
            'programme_curriculum_id' => $programmeCurriculumId,
            'department_id' => $departmentId,
            'compilation_type' => 'semester',
            'status' => 'processing',
            'started_at' => $startTime,
            'compiled_by' => $compiledBy,
            'compilation_parameters' => [
                'session_id' => $sessionId,
                'semester' => $semester,
                'level_id' => $levelId,
                'programme_id' => $programmeId,
                'department_id' => $departmentId
            ]
        ]);

        try {
            DB::beginTransaction();

            // Get all students registered for courses in this session/semester/level
            $studentsQuery = ModelsStudentCourseRegistration::with(['student', 'course'])
                ->where('session_id', $sessionId)
                ->where('semester', $semester)
                ->where('level_id', $levelId);

            if ($programmeId) {
                $studentsQuery->whereHas('student', function ($q) use ($programmeCurriculumId) {
                    $q->where('programme_curriculum_id', $programmeCurriculumId);
                });
            }

            if ($departmentId) {
                $studentsQuery->whereHas('student', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            }

            $registrations = $studentsQuery->get();

            // Group by student
            $studentRegistrations = $registrations->groupBy('student_id');

            $studentsProcessed = 0;
            $resultsProcessed = 0;

            foreach ($studentRegistrations as $studentId => $studentCourses) {
                $student = $studentCourses->first()->student;

                // Calculate GPA for this student
                $gpaData = $this->calculateStudentGPA($studentId, $sessionId, $semester, $levelId, $studentCourses);

                if ($gpaData) {
                    // Update or create semester GPA record
                    StudentSemesterGpa::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'session_id' => $sessionId,
                            'semester' => $semester
                        ],
                        array_merge($gpaData, [
                            'level_id' => $levelId,
                            'programme_id' => $student->programme_id,
                            'programme_curriculum_id' => $student->programme_curriculum_id,
                            'is_compiled' => true,
                            'compiled_at' => now(),
                            'compiled_by' => $compiledBy
                        ])
                    );

                    $studentsProcessed++;
                    $resultsProcessed += $studentCourses->count();
                }
            }

            // Update compilation log
            $compilationLog->update([
                'status' => 'completed',
                'students_processed' => $studentsProcessed,
                'results_processed' => $resultsProcessed,
                'completed_at' => now(),
                'processing_time_seconds' => now()->diffInSeconds($startTime),
                'compilation_summary' => "Successfully compiled results for {$studentsProcessed} students and {$resultsProcessed} course results."
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Results compiled successfully',
                'students_processed' => $studentsProcessed,
                'results_processed' => $resultsProcessed,
                'compilation_log_id' => $compilationLog->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            // Update compilation log with error
            $compilationLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'processing_time_seconds' => now()->diffInSeconds($startTime)
            ]);

            Log::error('Result compilation failed', [
                'session_id' => $sessionId,
                'semester' => $semester,
                'level_id' => $levelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Result compilation failed: ' . $e->getMessage(),
                'compilation_log_id' => $compilationLog->id
            ];
        }
    }

    /**
     * Calculate GPA for a specific student
     */
    private function calculateStudentGPA($studentId, $sessionId, $semester, $levelId, $studentCourses)
    {
        $totalCreditUnits = 0;
        $totalCreditPoints = 0;
        $earnedCreditUnits = 0;
        $carryOverCourses = [];

        // Get student's programme ID for programme-specific grade settings
        $student = Student::find($studentId);
        $programmeId = $student ? $student->programme_id : null;

        foreach ($studentCourses as $registration) {
            $course = $registration->course;
            $creditUnit = $course->credit_unit;
            $totalCreditUnits += $creditUnit;

            // Get result for this course
            $result = Result::where('student_id', $studentId)
                ->where('course_id', $course->id)
                ->where('session_id', $sessionId)
                ->where('semester', $semester)
                ->first();

            if ($result && $result->total_score !== null) {
                // Calculate grade and grade point using programme-specific settings
                $gradeData = GradeSetting::calculateGrade($result->total_score, $programmeId);
                $gradePoint = $gradeData['grade_point'];
                $grade = $gradeData['grade'];
                $status = $gradeData['status'];

                // Update result with calculated values
                $result->update([
                    'grade' => $grade,
                    'grade_point' => $gradePoint,
                    'credit_unit' => $creditUnit,
                    'quality_point' => $gradePoint * $creditUnit
                ]);

                $totalCreditPoints += ($gradePoint * $creditUnit);

                if ($status === 'pass') {
                    $earnedCreditUnits += $creditUnit;
                } else {
                    $carryOverCourses[] = $course->course_code;
                }
            } else {
                // No result found - treat as carry over
                $carryOverCourses[] = $course->course_code;
            }
        }

        // Calculate GPA
        $gpa = $totalCreditUnits > 0 ? round($totalCreditPoints / $totalCreditUnits, 2) : 0.00;

        // Get previous semester data for CGPA calculation
        $previousGpaRecord = StudentSemesterGpa::where('student_id', $studentId)
            ->where('session_id', '<', $sessionId)
            ->orWhere(function ($q) use ($sessionId, $semester) {
                $q->where('session_id', $sessionId)
                    ->where('semester', '<', $semester);
            })
            ->orderBy('session_id', 'desc')
            ->orderBy('semester', 'desc')
            ->first();

        // Calculate cumulative values
        $totalRegisteredCreditUnits = $totalCreditUnits;
        $totalEarnedCreditUnits = $earnedCreditUnits;
        $totalCumulativePoints = $totalCreditPoints;
        $numberOfSemesters = 1;
        $previousCgpa = 0.00;

        if ($previousGpaRecord) {
            $totalRegisteredCreditUnits += $previousGpaRecord->total_registered_credit_units;
            $totalEarnedCreditUnits += $previousGpaRecord->total_earned_credit_units;
            $totalCumulativePoints += $previousGpaRecord->total_cumulative_points;
            $numberOfSemesters = $previousGpaRecord->number_of_semesters + 1;
            $previousCgpa = $previousGpaRecord->cgpa;
        }

        // Calculate CGPA
        $cgpa = $totalRegisteredCreditUnits > 0 ? round($totalCumulativePoints / $totalRegisteredCreditUnits, 2) : 0.00;

        // Determine academic status
        $academicStatus = $this->determineAcademicStatus($cgpa, $numberOfSemesters);

        return [
            'registered_credit_units' => $totalCreditUnits,
            'earned_credit_units' => $earnedCreditUnits,
            'total_credit_points' => $totalCreditPoints,
            'gpa' => $gpa,
            'total_registered_credit_units' => $totalRegisteredCreditUnits,
            'total_earned_credit_units' => $totalEarnedCreditUnits,
            'total_cumulative_points' => $totalCumulativePoints,
            'total_department_credit_points' => $totalRegisteredCreditUnits, // Assuming same as total registered
            'previous_cgpa' => $previousCgpa,
            'cgpa' => $cgpa,
            'carry_over_courses' => implode(', ', $carryOverCourses),
            'number_of_semesters' => $numberOfSemesters,
            'academic_status' => $academicStatus
        ];
    }



    /**
     * Determine academic status based on CGPA
     */
    private function determineAcademicStatus($cgpa, $numberOfSemesters)
    {
        if ($cgpa >= 1.50) {
            return 'good_standing';
        } elseif ($cgpa >= 1.00) {
            return 'probation';
        } else {
            // Check if student should be withdrawn (usually after 2 consecutive semesters below 1.0)
            return $numberOfSemesters >= 2 ? 'withdrawal' : 'probation';
        }
    }

    /**
     * Generate result token for a course
     */
    public function generateResultToken($sessionId, $semester, $courseCode)
    {
        $session = Session::find($sessionId);
        $sessionName = str_replace('/', '-', $session->name);

        return "{$sessionName}-{$semester}-{$courseCode}";
    }
}
