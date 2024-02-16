<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Redis;
use Modules\Staff\Services\ProgrammeService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;


class ProgrammeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $programService;
    private $utilities;
    public function __construct(ProgrammeService $programService, Utilities $utilities)
    {
        $this->programService = $programService;
        $this->utilities = $utilities;
    }

    public function update(Request $request){
        
        try{

            $request->validate([                             
                "id" => "required",                 
                "faculty_id" =>"required",
                "department_id" =>"required",
                "name" =>"required",
                "code" =>"required",
                "maximum_credit_unit" =>"required",
                "minimum_credit_unit" =>"required",
                "duration" =>"required",   
                "max_duration"=>"required",
                "graduation_level_id"=>"required"                        
            ]);        
                          
            $response = $this->programService->updateProgramme($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function create(Request $request){
        
        try{

            $request->validate([          
                "faculty_id" =>"required",
                "department_id" =>"required",
                "name" =>"required",
                "code" =>"required",
                "maximum_credit_unit" =>"required",
                "minimum_credit_unit" =>"required",
                "duration" =>"required",                
            ]);        
                          
            
            $response = $this->programService->newProgramme($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function bulkUpload(Request $request){
        
        try{

            $request->validate([                             
                "file" => "required",                
                "department_id"=> "required",
                "faculty_id"=> "required",
            ]);        
                          
            $response = $this->programService->bulkProgrammeUpload($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function deactivate(Request $request){
        
        try{

            $request->validate([                             
                "id" => "required",                
            ]);        
                          
            $response = $this->programService->deactivateProgramme($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function activate(Request $request){
        
        try{

            $request->validate([                             
                "id" => "required",                
            ]);        
                          
            $response = $this->programService->activateProgramme($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getProgrammes(Request $request){
        try{
            $request->validate([                                             
                //"department"              
                //"entry_mode"
                //"programme_type"
            ]);                        
            $response = $this->programService->programmes($request);  
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function assignCourse(Request $request){
          
        try{

            $request->validate([                             
                "programme_id" => "required",                
                "course_ids" => "required",  //[1,2,3,4]          
                "level_id"=>"required",
                "semester_id"=>"required",
                "staff_id"=>"required",                                
            ]);        
                          
            $response = $this->programService->assignCoursesToProgramme($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    

    public function updateProgrammeCourse(Request $request){
          
        try{

            $request->validate([                             
                "id" => "required",                
                "programme_id" => "required",                
                "course_id" => "required",    //[1,2,3,4]          
                "level_id"=>"required",
                "semester_id"=>"required",
                "staff_id"=>"required"
            ]);        
                          
            $response = $this->programService->updateProgrammeCourse($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    

    public function unAssignCourses(Request $request){
          
        try{
                                     
            $request->validate([                             
                "ids" => "required",                
            ]);        
                          
            $response = $this->programService->unAssignCoursesFromProgramme($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getProgrammeCourses(Request $request){
          
        try{              
            $response = $this->programService->programmeCourses($request);                
            return new APIResource($response, false, 200 );
      
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function programmeCoursesWithoutPaginate(Request $request){
          
        try{
              
            $response = $this->programService->programmeCoursesWithoutPaginate($request);                
            return new APIResource($response, false, 200 );
      
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getProgrammeById(Request $request){
          
        try{
              
            $response = $this->programService->getProgrammeById($request);                
            return new APIResource($response, false, 200 );    
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }    

    public function getTemplate(){               
        return  $this->utilities->getFile('programmeUploadTemplate.csv');
    }

}
