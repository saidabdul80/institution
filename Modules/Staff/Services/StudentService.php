<?php
namespace Modules\Staff\Services;

use App\Exports\Export;
use App\Models\Student;
use App\Models\StudentExport;
use Modules\Staff\Repositories\StudentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class StudentService{
    private $studentRepository;

    public function __construct( StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function updateStudent($request){

        $studentInfo = $request->all();
        $id = $request->get('id');
        unset($studentInfo['id']);
        //$rejected = [];
        /* 
        $params = ['matric_number','application_id','entry_session_id','applied_level_id','applied_programme_id','programme_type_id','programme_id','level_id','mode_of_entry_id','department_id','faculty_id'];
        foreach ($params as $param ) {
            if(array_key_exists($param, $studentInfo)){
                unset($studentInfo[$param]);
                $rejected[] = $param;
            }
        }         */        
        return $this->studentRepository->update($studentInfo, $id);        

    }

    public function getStudent($request){
        $filters = [];
        if(!is_null( $request->matric_number)){
           $filters['matric_number'] = $request->matric_number;
        }

        $students = $this->studentRepository->getStudent($filters);        
        
        // Select only specific columns from the collection
        $cleanedStudents = $students->map(function ($student) {
            return collect($student)->only(
            [
                "first_name",
                "middle_name",
                "surname",
                "phone_number",
                "gender",
                "email",
                "matric_number",
                "date_of_birth",
                "present_address",
                "permanent_address",
                "guardian_full_name",
                "guardian_phone_number",
                "guardian_address",
                "guardian_email",
                "guardian_relationship",
                "sponsor_full_name",
                "sponsor_type",
                "sponsor_address",
                "next_of_kin_full_name",
                "next_of_kin_address",
                "next_of_kin_phone_number",
                "next_of_kin_relationship",
                "religion",
                "marital_status",
                "picture",
                "status",
                "programme_name",
                "programme_type_name",
                "lga_name",
                "faculty",
                "department",
                "mode_of_entry",
                "level",
                "state",
                "country"
            ]);
        });
        
        // Create a new pagination instance with the modified collection and the original pagination properties
        $modifiedPagination = new LengthAwarePaginator(
            $cleanedStudents,
            $students->total(),
            $students->perPage(),
            $students->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return $modifiedPagination;
        
    }

    public function exportStudents($request){
        $filters = $request->get('filters') ?? [];
        $filters['custom_fields'] = true;

        $students= $this->studentRepository->getStudentsWithoutAppends($filters);
        if ($students->isEmpty()) {
            throw new \Exception('No records found', 404);
        }

        // Split the applicants into chunks of 1000 or less
        $response = Excel::download(new Export($students), 'students.xlsx');
        ob_end_clean();
        return  $response;

    }

    /**
     * Get student statistics
     */
    public function getStudentStats($request)
    {
        $sessionId = $request->get('session_id');
        return $this->studentRepository->getStudentStats($sessionId);
    }

    /**
     * Create new student
     */
    public function createStudent($request)
    {
        return $this->studentRepository->createStudent($request->all());
    }

    /**
     * Bulk upload students
     */
    public function bulkUploadStudents($request)
    {
        $file = $request->file('file');
        $sessionId = $request->get('session_id');
        $levelId = $request->get('level_id');
        $programmeId = $request->get('programme_id');

        return $this->studentRepository->bulkUploadStudents($file, $sessionId, $levelId, $programmeId);
    }

    /**
     * Get student by ID
     */
    public function getStudentById($studentId)
    {
        return $this->studentRepository->getStudentById($studentId);
    }

    /**
     * Get student courses
     */
    public function getStudentCourses($studentId, $sessionId = null, $semester = null)
    {
        return $this->studentRepository->getStudentCourses($studentId, $sessionId, $semester);
    }

    /**
     * Get student results
     */
    public function getStudentResults($studentId, $sessionId = null, $semester = null)
    {
        return $this->studentRepository->getStudentResults($studentId, $sessionId, $semester);
    }

    /**
     * Search students
     */
    public function searchStudents($request)
    {
        $searchType = $request->get('search_type');
        $query = $request->get('query');
        $limit = $request->get('limit', 50);

        return $this->studentRepository->searchStudents($searchType, $query, $limit);
    }

    /**
     * Get student academic records
     */
    public function getStudentAcademicRecords($studentId)
    {
        return $this->studentRepository->getStudentAcademicRecords($studentId);
    }

    

}

?>
