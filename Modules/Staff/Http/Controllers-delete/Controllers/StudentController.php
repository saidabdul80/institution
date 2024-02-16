<?php

namespace Modules\Staff\Http\Controllers;
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
            return new APIResource($e->errors(), true, 400 );
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
            return new APIResource($e->errors(), true, 400 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }
    
    public function exportStudents(Request $request){        
        try{         
            return $this->studentService->exportStudents($request);                    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    
}
