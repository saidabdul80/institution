<?php 

namespace App\Services;

use App\Repositories\StudentRepository;

class StudentService
{
    protected $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function getStudentById($student_id)
    {
        return $this->studentRepository->getById($student_id);
    }

    public function getStudentWithResult($request)
    {
        return $this->studentRepository->getStudentWithResult($request->student_id, $request->session_id, $request->semester_id);
    }
}