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
}
?>