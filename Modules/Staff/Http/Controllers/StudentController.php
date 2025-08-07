<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;
use Modules\Staff\Services\StudentService;
use Modules\Staff\Transformers\UtilResource;

class StudentController extends Controller
{    
    private $studentService;    
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;        
    }

    public function updateStudent(Request $request)
    {

        try{
            $request->validate([
                'id' => 'required'
            ]);
            $response = $this->studentService->updateStudent($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getStudent(Request $request)
    {

        try{           
            $response = $this->studentService->getStudent($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }
    
    public function exportStudents(Request $request){
        try{
            return $this->studentService->exportStudents($request);
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    /**
     * Get student statistics
     */
    public function getStudentStats(Request $request)
    {
        try {
            $response = $this->studentService->getStudentStats($request);
            return new APIResource($response, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Create new student
     */
    public function createStudent(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:students,email',
                'programme_id' => 'required',
                'level_id' => 'required',
                'session_id' => 'required'
            ]);

            // $response = $this->studentService->createStudent($request);
            // return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Bulk upload students
     */
    public function bulkUploadStudents(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls',
                'session_id' => 'required',
                'level_id' => 'required',
                'programme_id' => 'required'
            ]);

            $response = $this->studentService->bulkUploadStudents($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get student by ID
     */
    public function getStudentById(Request $request)
    {
        try {
            $studentId = $request->route('id') ?? $request->get('id');
            if (!$studentId) {
                throw new Exception('Student ID is required', 400);
            }

            $response = $this->studentService->getStudentById($studentId);
            return new APIResource($response, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get student courses
     */
    public function getStudentCourses(Request $request)
    {
        try {
            $studentId = $request->get('student_id');
            $sessionId = $request->get('session_id');
            $semester = $request->get('semester');

            if (!$studentId) {
                throw new Exception('Student ID is required', 400);
            }

            $response = $this->studentService->getStudentCourses($studentId, $sessionId, $semester);
            return new APIResource($response, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get student results
     */
    public function getStudentResults(Request $request)
    {
        try {
            $studentId = $request->get('student_id');
            $sessionId = $request->get('session_id');
            $semester = $request->get('semester');

            if (!$studentId) {
                throw new Exception('Student ID is required', 400);
            }

            $response = $this->studentService->getStudentResults($studentId, $sessionId, $semester);
            return new APIResource($response, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Search students
     */
    public function searchStudents(Request $request)
    {
        try {
            $request->validate([
                'search_type' => 'required|in:matric_number,name,email',
                'query' => 'required|string'
            ]);

            $response = $this->studentService->searchStudents($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get student academic records
     */
    public function getStudentAcademicRecords(Request $request)
    {
        try {
            $studentId = $request->route('id') ?? $request->get('student_id');
            if (!$studentId) {
                throw new Exception('Student ID is required', 400);
            }

            $response = $this->studentService->getStudentAcademicRecords($studentId);
            return new APIResource($response, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
    
}
