<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Redis;
use Modules\Staff\Services\FacultyService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;


class FacultyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $facultyService;
    private $utilities;
    public function __construct(FacultyService $facultyService, Utilities $utilities)
    {
        $this->facultyService = $facultyService;
        $this->utilities = $utilities;
    }

    public function update(Request $request){
        
        try{

            $request->validate([                             
                "id" => "required", 
                "name" => "required",                
                "abbr" => "required",                
            ]);        
                          
            $response = $this->facultyService->updateFaculty($request);        
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
            ]);        
                          
            $response = $this->facultyService->newFaculty($request);        
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
            ]);        
                          
            $response = $this->facultyService->bulkFacultyUpload($request);        
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
                          
            $response = $this->facultyService->deactivateFaculty($request);        
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
                          
            $response = $this->facultyService->activateFaculty($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getFaculties(Request $request){
        
        try{                      
            $response = $this->facultyService->faculties($request);                  
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getFacultiesWithoutPaginate(Request $request){
        
        try{           
            $key = 'departments_'.tenant('id');   
            if(Redis::get($key) && is_null($request->has('search'))){
                $response = json_decode(Redis::get($key));  
            }else{                
                $response = $this->facultyService->facultiesWithoutPaginate($request);        
                Redis::set($key,json_encode($response));      
                Redis::expire($key,259200);      
            }          
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    

    public function getTemplate(){               
        return  $this->utilities->getFile('facultyUploadTemplate.csv');
    }

}
