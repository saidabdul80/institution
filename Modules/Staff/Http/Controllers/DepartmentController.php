<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Redis;
use Modules\Staff\Services\DepartmentService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;


class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $departmentService;
    private $utilities;
    public function __construct(DepartmentService $departmentService, Utilities $utilities)
    {
        $this->departmentService = $departmentService;
        $this->utilities = $utilities;
    }

    public function update(Request $request){
        
        try{

            $request->validate([                             
                "id" => "required", 
                "name" => "required",                
                "abbr" => "required",
                "faculty_id"=>"required"                
            ]);        
                          
            $response = $this->departmentService->updateDepartment($request);        
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
                "name" => "required",                
                "abbr" => "required",      
                "faculty_id"=>"required"                          
            ]);        
                          
            $response = $this->departmentService->newDepartment($request);        
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
                "faculty_id"=>"required"              
            ]);        
                          
            $response = $this->departmentService->bulkDepartmentUpload($request);        
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
                          
            $response = $this->departmentService->deactivateDepartment($request);        
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
                          
            $response = $this->departmentService->activateDepartment($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getDepartments(Request $request){
        
        try{
            
            $response = $this->departmentService->departments($request);        
            return new APIResource($response, false, 200 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    public function getDepartmentsWithoutPaginate(Request $request){
        
        try{

            $key = 'departments_'.tenant('id');   
            if(Redis::get($key) && is_null($request->has('search'))){
                $response = json_decode(Redis::get($key));  
            }else{
                $response = $this->departmentService->departmentsWithoutPaginate($request);        
                Redis::set($key,json_encode($response));      
                Redis::expire($key,259200);      
            }              
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    
    public function getTemplate(){               
        return  $this->utilities->getFile('departmentUploadTemplate.csv');
    }
}
