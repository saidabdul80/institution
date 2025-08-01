<?php

namespace Modules\Staff\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Student;

class TranscriptRepository
{
    /**
     * Get student academic records for transcript
     */
    public function getStudentAcademicRecords($studentId, $options = [])
    {
        $query = DB::table('student_course_grades')
                   ->join('courses', 'student_course_grades.course_id', '=', 'courses.id')
                   ->join('sessions', 'student_course_grades.session_id', '=', 'sessions.id')
                   ->where('student_course_grades.student_id', $studentId);

        // Apply session filters if provided
        if (isset($options['from_session']) && $options['from_session']) {
            $query->where('student_course_grades.session_id', '>=', $options['from_session']);
        }

        if (isset($options['to_session']) && $options['to_session']) {
            $query->where('student_course_grades.session_id', '<=', $options['to_session']);
        }

        $records = $query->select([
            'student_course_grades.*',
            'courses.code as course_code',
            'courses.title as course_title',
            'courses.credit_unit',
            'sessions.name as session_name'
        ])
        ->orderBy('sessions.name')
        ->orderBy('student_course_grades.semester')
        ->orderBy('courses.code')
        ->get();

        // Group by session and semester
        $groupedRecords = [];
        foreach ($records as $record) {
            $sessionKey = $record->session_name;
            $semesterKey = $this->getSemesterName($record->semester);
            
            if (!isset($groupedRecords[$sessionKey])) {
                $groupedRecords[$sessionKey] = [];
            }
            
            if (!isset($groupedRecords[$sessionKey][$semesterKey])) {
                $groupedRecords[$sessionKey][$semesterKey] = [];
            }
            
            $groupedRecords[$sessionKey][$semesterKey][] = $record;
        }

        return [
            'records' => $groupedRecords,
            'summary' => $this->calculateTranscriptSummary($records, $options)
        ];
    }

    /**
     * Calculate transcript summary (GPA, CGPA, etc.)
     */
    private function calculateTranscriptSummary($records, $options)
    {
        $totalGradePoints = 0;
        $totalCreditUnits = 0;
        $semesterSummaries = [];

        // Group by session and semester for GPA calculation
        $sessionSemesters = [];
        foreach ($records as $record) {
            $key = $record->session_name . '_' . $record->semester;
            if (!isset($sessionSemesters[$key])) {
                $sessionSemesters[$key] = [
                    'session' => $record->session_name,
                    'semester' => $record->semester,
                    'records' => []
                ];
            }
            $sessionSemesters[$key]['records'][] = $record;
        }

        // Calculate GPA for each semester
        foreach ($sessionSemesters as $key => $semesterData) {
            $semesterGradePoints = 0;
            $semesterCreditUnits = 0;

            foreach ($semesterData['records'] as $record) {
                $creditUnits = $record->credit_unit ?? 0;
                $gradePoint = $record->grade_point ?? 0;

                $semesterGradePoints += ($gradePoint * $creditUnits);
                $semesterCreditUnits += $creditUnits;
            }

            $semesterGPA = $semesterCreditUnits > 0 ? round($semesterGradePoints / $semesterCreditUnits, 2) : 0.00;

            $semesterSummaries[] = [
                'session' => $semesterData['session'],
                'semester' => $this->getSemesterName($semesterData['semester']),
                'gpa' => $semesterGPA,
                'credit_units' => $semesterCreditUnits,
                'courses_count' => count($semesterData['records'])
            ];

            $totalGradePoints += $semesterGradePoints;
            $totalCreditUnits += $semesterCreditUnits;
        }

        // Calculate overall CGPA
        $cgpa = $totalCreditUnits > 0 ? round($totalGradePoints / $totalCreditUnits, 2) : 0.00;

        // Determine degree classification
        $degreeClass = $this->getDegreeClassification($cgpa);

        return [
            'cgpa' => $cgpa,
            'total_credit_units' => $totalCreditUnits,
            'total_courses' => count($records),
            'degree_classification' => $degreeClass,
            'semester_summaries' => $semesterSummaries
        ];
    }

    /**
     * Get semester name
     */
    private function getSemesterName($semester)
    {
        $names = [
            1 => 'First Semester',
            2 => 'Second Semester',
            3 => 'Third Semester'
        ];
        return $names[$semester] ?? 'Unknown Semester';
    }

    /**
     * Get degree classification based on CGPA
     */
    private function getDegreeClassification($cgpa)
    {
        if ($cgpa >= 4.50) return 'First Class';
        if ($cgpa >= 3.50) return 'Second Class Upper';
        if ($cgpa >= 2.40) return 'Second Class Lower';
        if ($cgpa >= 1.50) return 'Third Class';
        if ($cgpa >= 1.00) return 'Pass';
        return 'Fail';
    }
}
