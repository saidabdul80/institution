<?php

namespace Modules\Result\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Student\Entities\Student;
use Modules\Staff\Entities\Course;
use Modules\Result\Entities\StudentCourseGrade;
use Modules\Staff\Entities\Session;
use Modules\Staff\Entities\Level;
use Modules\Staff\Entities\Programme;
use Maatwebsite\Excel\Facades\Excel;

class ResultRepository
{
    /**
     * Compute results for students
     */
    public function computeResults($sessionId, $levelId, $programmeId, $semester)
    {
        // Get all students in the specified criteria
        $students = Student::where([
            'session_id' => $sessionId,
            'level_id' => $levelId,
            'programme_id' => $programmeId
        ])->get();

        $computedCount = 0;
        foreach ($students as $student) {
            $this->computeIndividualResult($student->id, $sessionId, $semester);
            $computedCount++;
        }

        return [
            'message' => 'Results computed successfully',
            'students_processed' => $computedCount
        ];
    }

    /**
     * Compute individual student result
     */
    public function computeIndividualResult($studentId, $sessionId, $semester)
    {
        // Get all course grades for the student in the specified session and semester
        $courseGrades = StudentCourseGrade::where([
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'semester' => $semester
        ])->with('course')->get();

        $totalGradePoints = 0;
        $totalCreditUnits = 0;

        foreach ($courseGrades as $grade) {
            if ($grade->course) {
                $creditUnits = $grade->course->credit_unit ?? 0;
                $gradePoint = $grade->grade_point ?? 0;

                $totalGradePoints += ($gradePoint * $creditUnits);
                $totalCreditUnits += $creditUnits;
            }
        }

        // Calculate GPA for the semester
        $gpa = $totalCreditUnits > 0 ? round($totalGradePoints / $totalCreditUnits, 2) : 0.00;

        // Update or create semester result
        DB::table('student_semester_results')->updateOrInsert([
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'semester' => $semester
        ], [
            'gpa' => $gpa,
            'total_credit_units' => $totalCreditUnits,
            'total_grade_points' => $totalGradePoints,
            'updated_at' => now()
        ]);

        // Compute CGPA (all semesters)
        $this->computeCGPA($studentId);

        return [
            'student_id' => $studentId,
            'gpa' => $gpa,
            'total_credit_units' => $totalCreditUnits
        ];
    }

    /**
     * Compute CGPA for a student
     */
    private function computeCGPA($studentId)
    {
        $semesterResults = DB::table('student_semester_results')
            ->where('student_id', $studentId)
            ->get();

        $totalGradePoints = 0;
        $totalCreditUnits = 0;

        foreach ($semesterResults as $result) {
            $totalGradePoints += $result->total_grade_points;
            $totalCreditUnits += $result->total_credit_units;
        }

        $cgpa = $totalCreditUnits > 0 ? round($totalGradePoints / $totalCreditUnits, 2) : 0.00;

        // Update student CGPA
        Student::where('id', $studentId)->update([
            'cgpa' => $cgpa,
            'total_credit_units' => $totalCreditUnits
        ]);

        return $cgpa;
    }

    /**
     * Get students for result computation
     */
    public function getStudentsForComputation($sessionId, $levelId, $programmeId, $semester)
    {
        return Student::where([
            'session_id' => $sessionId,
            'level_id' => $levelId,
            'programme_id' => $programmeId
        ])->with(['programme', 'level'])->paginate(50);
    }

    /**
     * Get students with existing results/scores for a specific course
     */
    public function getStudentsWithResults($sessionId, $levelId, $programmeId, $courseId, $semester)
    {
        $students = Student::where([
            'session_id' => $sessionId,
            'level_id' => $levelId,
            'programme_id' => $programmeId
        ])->get();

        $studentsWithResults = [];
        foreach ($students as $student) {
            $existingGrade = StudentCourseGrade::where([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'semester' => $semester
            ])->first();

            $studentsWithResults[] = [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'middle_name' => $student->middle_name,
                'matric_number' => $student->matric_number,
                'ca_score' => $existingGrade->ca_score ?? 0,
                'exam_score' => $existingGrade->exam_score ?? 0,
                'total_score' => $existingGrade->total_score ?? 0,
                'grade' => $existingGrade->grade ?? 'F',
                'grade_point' => $existingGrade->grade_point ?? 0.0
            ];
        }

        return $studentsWithResults;
    }

    /**
     * Save individual student result
     */
    public function saveStudentResult($data)
    {
        return StudentCourseGrade::updateOrCreate([
            'student_id' => $data['student_id'],
            'course_id' => $data['course_id'],
            'session_id' => $data['session_id'],
            'semester' => $data['semester']
        ], [
            'ca_score' => $data['ca_score'],
            'exam_score' => $data['exam_score'],
            'total_score' => $data['total_score'],
            'grade' => $data['grade'],
            'grade_point' => $data['grade_point'],
            'created_by' => $data['created_by'],
            'updated_at' => now()
        ]);
    }

    /**
     * Bulk upload results from file
     */
    public function bulkUploadResults($file, $sessionId, $levelId, $programmeId, $courseId, $semester)
    {
        try {
            $data = Excel::toArray([], $file)[0];
            $header = array_shift($data); // Remove header row

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                try {
                    // Assuming CSV format: matric_number, ca_score, exam_score
                    $matricNumber = $row[0] ?? '';
                    $caScore = floatval($row[1] ?? 0);
                    $examScore = floatval($row[2] ?? 0);
                    $totalScore = $caScore + $examScore;

                    // Find student by matric number
                    $student = Student::where('matric_number', $matricNumber)->first();
                    if (!$student) {
                        $errors[] = "Row " . ($index + 2) . ": Student with matric number {$matricNumber} not found";
                        $errorCount++;
                        continue;
                    }

                    // Calculate grade and grade point
                    $grade = $this->calculateGrade($totalScore);
                    $gradePoint = $this->calculateGradePoint($totalScore);

                    // Save result
                    $this->saveStudentResult([
                        'student_id' => $student->id,
                        'course_id' => $courseId,
                        'session_id' => $sessionId,
                        'semester' => $semester,
                        'ca_score' => $caScore,
                        'exam_score' => $examScore,
                        'total_score' => $totalScore,
                        'grade' => $grade,
                        'grade_point' => $gradePoint,
                        'created_by' => auth('api-staff')->id()
                    ]);

                    $successCount++;
                } catch (Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            return [
                'message' => 'Bulk upload completed',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to process file: ' . $e->getMessage());
        }
    }

    /**
     * Calculate grade based on total score
     */
    private function calculateGrade($totalScore)
    {
        if ($totalScore >= 70) return 'A';
        if ($totalScore >= 60) return 'B';
        if ($totalScore >= 50) return 'C';
        if ($totalScore >= 45) return 'D';
        if ($totalScore >= 40) return 'E';
        return 'F';
    }

    /**
     * Calculate grade point based on total score
     */
    private function calculateGradePoint($totalScore)
    {
        if ($totalScore >= 70) return 5.0;
        if ($totalScore >= 60) return 4.0;
        if ($totalScore >= 50) return 3.0;
        if ($totalScore >= 45) return 2.0;
        if ($totalScore >= 40) return 1.0;
        return 0.0;
    }
}
