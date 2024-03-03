<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\ProgrammeTypeService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;


class ProgrammeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $programmeTypeService;
    private $utilities;
    public function __construct(ProgrammeTypeService $programmeTypeService, Utilities $utilities)
    {
        $this->programmeTypeService = $programmeTypeService;
        $this->utilities = $utilities;
    }

    public function create(Request $request){
        
        try{

            $request->validate([                                           
                "name" =>"required",
                "short_name"=>"required"
            ]);        
                          
            $response = $this->programmeTypeService->create($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function update(Request $request){
        
        try{

            $request->validate([                                           
                "name" =>"required",
                "short_name"=>"required",
                "id"=>"required"
            ]);        
                          
            $response = $this->programmeTypeService->update($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function delete(Request $request){
        
        try{

            $request->validate([                                           
                "id" =>"required",
            ]);        
                          
            $response = $this->programmeTypeService->delete($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    
    public function getProgrammeTypes(Request $request){
        
        try{                
                          
            $response = $this->programmeTypeService->all($request);        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getTemplate(){               
        return  $this->utilities->getFile('programmeTypeUploadTemplate.csv');
    }
    
}
