<?php
namespace Modules\Staff\Repositories;

use App\Models\Invoice;
use App\Models\ProgrammeCourses;
use App\Models\Result;
use App\Models\Student;
use App\Models\StudentCoursesGrades;
use Database\Seeders\Courses;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Services\Utilities;

class StudentRepository{

    private $student;    
    private $courseRegistration;
    private $programmeCourses;
    private $invoice;
    private $result;
    private $paymentRepository;
    public function __construct( Student $student, StudentCoursesGrades $courseRegistration, ProgrammeCourses $programmeCourses, Invoice $invoice, Result $result)
    {        
        $this->student = $student;        
        $this->courseRegistration = $courseRegistration;
        $this->programmeCourses = $programmeCourses;
        $this->invoice = $invoice;
        $this->result = $result;     
    }


    public function update($studentInfo, $id)
    {                
        $this->student->where('id',$id)->update($studentInfo);                                        
        return "Updated successfuly";
    }
  
    public function getStudent($filters)
    {
        return $this->student->filter($filters)->paginate(100);     
    }

    public function getStudentWithoutPaginate($filters)
    {
        return $this->student->filter($filters)->get();     
    }

    public function getStudentsWithoutAppends($filters=[])
    {
        $this->student::$withoutAppends = true;
        return $this->student->filter($filters)->get();
    }

    /**
     * Get student statistics
     */
    public function getStudentStats($sessionId = null)
    {
        $query = $this->student->query();

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $total = $query->count();
        $active = (clone $query)->where('status', 'active')->count();
        $inactive = (clone $query)->where('status', 'inactive')->count();
        $graduated = (clone $query)->where('status', 'graduated')->count();
        $suspended = (clone $query)->where('status', 'suspended')->count();

        // Get statistics by level
        $byLevel = (clone $query)->select('level_id', DB::raw('count(*) as count'))
                                 ->join('levels', 'students.level_id', '=', 'levels.id')
                                 ->groupBy('level_id', 'levels.title')
                                 ->selectRaw('levels.title as level_name')
                                 ->get();

        // Get statistics by programme
        $byProgramme = (clone $query)->select('programme_id', DB::raw('count(*) as count'))
                                     ->join('programmes', 'students.programme_id', '=', 'programmes.id')
                                     ->groupBy('programme_id', 'programmes.name')
                                     ->selectRaw('programmes.name as programme_name')
                                     ->get();

        // Get statistics by gender
        $byGender = (clone $query)->select('gender', DB::raw('count(*) as count'))
                                  ->groupBy('gender')
                                  ->get();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'graduated' => $graduated,
            'suspended' => $suspended,
            'by_level' => $byLevel,
            'by_programme' => $byProgramme,
            'by_gender' => $byGender,
            'session_id' => $sessionId
        ];
    }

    /**
     * Create new student
     */
    public function createStudent($data)
    {
        // Generate matric number if not provided
        if (!isset($data['matric_number'])) {
            $data['matric_number'] = $this->generateMatricNumber($data['programme_id'], $data['session_id']);
        }

        return $this->student->create($data);
    }

    /**
     * Generate matric number
     */
    private function generateMatricNumber($programmeId, $sessionId)
    {
        // Get programme abbreviation
        $programme = DB::table('programmes')->where('id', $programmeId)->first();
        $programmeAbbr = $programme->abbr ?? 'STU';

        // Get session year
        $session = DB::table('sessions')->where('id', $sessionId)->first();
        $sessionYear = substr($session->name ?? date('Y'), 0, 4);

        // Get next sequence number
        $lastMatric = $this->student->where('matric_number', 'like', "{$programmeAbbr}/{$sessionYear}/%")
                                   ->orderBy('matric_number', 'desc')
                                   ->first();

        $sequence = 1;
        if ($lastMatric) {
            $parts = explode('/', $lastMatric->matric_number);
            $sequence = intval(end($parts)) + 1;
        }

        return sprintf('%s/%s/%03d', $programmeAbbr, $sessionYear, $sequence);
    }

    /**
     * Bulk upload students
     */
    public function bulkUploadStudents($file, $sessionId, $levelId, $programmeId)
    {
        // Implementation for bulk upload would go here
        // This is a placeholder
        return [
            'message' => 'Bulk upload functionality not yet implemented',
            'success_count' => 0,
            'error_count' => 0
        ];
    }

    /**
     * Get student by ID
     */
    public function getStudentById($studentId)
    {
        return $this->student->with(['programme', 'level', 'session'])->find($studentId);
    }

    /**
     * Get student courses
     */
    public function getStudentCourses($studentId, $sessionId = null, $semester = null)
    {
        $query = $this->courseRegistration->where('student_id', $studentId);

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($semester) {
            $query->where('semester', $semester);
        }

        $courses = $query->with([
            'course' => function($q) {
                $q->select('id', 'code', 'title', 'credit_unit', 'course_type');
            }
        ])->get();

        // Group courses by semester for better organization
        $groupedCourses = $courses->groupBy('semester');

        // Calculate total credit units
        $totalCreditUnits = $courses->sum(function($registration) {
            return $registration->course ? $registration->course->credit_unit : 0;
        });

        return [
            'courses' => $courses,
            'grouped_by_semester' => $groupedCourses,
            'total_credit_units' => $totalCreditUnits,
            'total_courses' => $courses->count(),
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'semester' => $semester
        ];
    }

    /**
     * Get student results
     */
    public function getStudentResults($studentId, $sessionId = null, $semester = null)
    {
        $query = DB::table('student_course_grades')
                   ->join('courses', 'student_course_grades.course_id', '=', 'courses.id')
                   ->leftJoin('sessions', 'student_course_grades.session_id', '=', 'sessions.id')
                   ->where('student_course_grades.student_id', $studentId);

        if ($sessionId) {
            $query->where('student_course_grades.session_id', $sessionId);
        }

        if ($semester) {
            $query->where('student_course_grades.semester', $semester);
        }

        $results = $query->select([
            'student_course_grades.*',
            'courses.code as course_code',
            'courses.title as course_title',
            'courses.credit_unit',
            'courses.course_type',
            'sessions.name as session_name'
        ])->orderBy('student_course_grades.session_id')
          ->orderBy('student_course_grades.semester')
          ->get();

        // Calculate GPA and other statistics
        $totalCreditUnits = 0;
        $totalGradePoints = 0;
        $passedCourses = 0;
        $failedCourses = 0;

        foreach ($results as $result) {
            $creditUnit = $result->credit_unit ?? 0;
            $grade = $result->grade ?? 0;

            $totalCreditUnits += $creditUnit;
            $totalGradePoints += ($grade * $creditUnit);

            if ($grade >= 40) { // Assuming 40 is pass mark
                $passedCourses++;
            } else {
                $failedCourses++;
            }
        }

        $gpa = $totalCreditUnits > 0 ? round($totalGradePoints / $totalCreditUnits, 2) : 0;

        // Group results by session and semester
        $groupedResults = $results->groupBy(['session_name', 'semester']);

        return [
            'results' => $results,
            'grouped_results' => $groupedResults,
            'statistics' => [
                'total_courses' => $results->count(),
                'passed_courses' => $passedCourses,
                'failed_courses' => $failedCourses,
                'total_credit_units' => $totalCreditUnits,
                'total_grade_points' => $totalGradePoints,
                'gpa' => $gpa
            ],
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'semester' => $semester
        ];
    }

    /**
     * Search students
     */
    public function searchStudents($searchType, $query, $limit = 50)
    {
        $students = $this->student->query();

        switch ($searchType) {
            case 'matric_number':
                $students->where('matric_number', 'like', "%{$query}%");
                break;
            case 'name':
                $students->where(function($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('middle_name', 'like', "%{$query}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                      ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
                });
                break;
            case 'email':
                $students->where('email', 'like', "%{$query}%");
                break;
            case 'phone':
                $students->where('phone', 'like', "%{$query}%");
                break;
            case 'all':
                $students->where(function($q) use ($query) {
                    $q->where('matric_number', 'like', "%{$query}%")
                      ->orWhere('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('middle_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
                });
                break;
        }

        $results = $students->with([
            'programme' => function($q) {
                $q->select('id', 'name', 'abbr');
            },
            'level' => function($q) {
                $q->select('id', 'name');
            },
            'session' => function($q) {
                $q->select('id', 'name');
            },
            'faculty' => function($q) {
                $q->select('id', 'name');
            },
            'department' => function($q) {
                $q->select('id', 'name');
            }
        ])->select([
            'id', 'matric_number', 'first_name', 'last_name', 'middle_name',
            'email', 'phone', 'gender', 'status', 'programme_id', 'level_id',
            'session_id', 'faculty_id', 'department_id', 'created_at'
        ])->limit($limit)->get();

        return [
            'students' => $results,
            'total_found' => $results->count(),
            'search_type' => $searchType,
            'search_query' => $query,
            'limit' => $limit
        ];
    }

    /**
     * Get student academic records
     */
    public function getStudentAcademicRecords($studentId)
    {
        // Get student basic information
        $student = $this->student->with([
            'programme' => function($q) {
                $q->select('id', 'name', 'abbr', 'duration');
            },
            'level' => function($q) {
                $q->select('id', 'name');
            },
            'session' => function($q) {
                $q->select('id', 'name');
            },
            'faculty' => function($q) {
                $q->select('id', 'name');
            },
            'department' => function($q) {
                $q->select('id', 'name');
            }
        ])->find($studentId);

        if (!$student) {
            throw new \Exception('Student not found');
        }

        // Get all academic records
        $academicRecords = DB::table('student_course_grades')
                 ->join('courses', 'student_course_grades.course_id', '=', 'courses.id')
                 ->join('sessions', 'student_course_grades.session_id', '=', 'sessions.id')
                 ->leftJoin('levels', 'student_course_grades.level_id', '=', 'levels.id')
                 ->where('student_course_grades.student_id', $studentId)
                 ->select([
                     'student_course_grades.*',
                     'courses.code as course_code',
                     'courses.title as course_title',
                     'courses.credit_unit',
                     'courses.course_type',
                     'sessions.name as session_name',
                     'levels.name as level_name'
                 ])
                 ->orderBy('sessions.name')
                 ->orderBy('student_course_grades.level_id')
                 ->orderBy('student_course_grades.semester')
                 ->get();

        // Calculate semester and cumulative GPAs
        $semesterGPAs = [];
        $cumulativeGPA = 0;
        $totalCreditUnits = 0;
        $totalGradePoints = 0;

        // Group by session and semester for GPA calculation
        $groupedRecords = $academicRecords->groupBy(['session_name', 'semester']);

        foreach ($groupedRecords as $sessionName => $semesters) {
            foreach ($semesters as $semester => $courses) {
                $semesterCreditUnits = 0;
                $semesterGradePoints = 0;

                foreach ($courses as $course) {
                    $creditUnit = $course->credit_unit ?? 0;
                    $grade = $course->grade ?? 0;

                    $semesterCreditUnits += $creditUnit;
                    $semesterGradePoints += ($grade * $creditUnit);

                    $totalCreditUnits += $creditUnit;
                    $totalGradePoints += ($grade * $creditUnit);
                }

                $semesterGPA = $semesterCreditUnits > 0 ? round($semesterGradePoints / $semesterCreditUnits, 2) : 0;
                $semesterGPAs[] = [
                    'session' => $sessionName,
                    'semester' => $semester,
                    'gpa' => $semesterGPA,
                    'credit_units' => $semesterCreditUnits,
                    'courses_count' => $courses->count()
                ];
            }
        }

        $cumulativeGPA = $totalCreditUnits > 0 ? round($totalGradePoints / $totalCreditUnits, 2) : 0;

        return [
            'student' => $student,
            'academic_records' => $academicRecords,
            'grouped_records' => $groupedRecords,
            'semester_gpas' => $semesterGPAs,
            'summary' => [
                'total_courses' => $academicRecords->count(),
                'total_credit_units' => $totalCreditUnits,
                'cumulative_gpa' => $cumulativeGPA,
                'total_semesters' => count($semesterGPAs)
            ]
        ];
    }
}
?>