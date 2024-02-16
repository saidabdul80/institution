<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Services\SessionService;
use Modules\Staff\Transformers\UtilResource;


class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $sessionService;
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function create(Request $request){
        
        try{

            $request->validate([                                           
                "name" =>"required",
            ]);        
                          
            $response = $this->sessionService->create($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function update(Request $request){
        
        try{

            $request->validate([                                           
                "id" =>"required",
                "name" =>"required",
            ]);        
                          
            $response = $this->sessionService->update($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getSessions(){
        
        try{                
                          
            $response = $this->sessionService->sessions();        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    
    public function createSession(Request $request){
          
        try{
                                     
            $request->validate([                                               
                "name"=>"required"
            ]);        
                          
            $response = $this->sessionService->createSession($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function updateSession(Request $request){
          
        try{
                                     
            $request->validate([                             
                "id" => "required",     
                "name"=>"required"
            ]);        
                          
            $response = $this->sessionService->updateSession($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function deleteSession(Request $request){
          
        try{
                                     
            $request->validate([                             
                "id" => "required",                     
            ]);        
                          
            $response = $this->sessionService->deleteSession($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getSession(Request $request){
          
        try{
                                     
            $response = DB::table("sessions")->get();        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
    
}
