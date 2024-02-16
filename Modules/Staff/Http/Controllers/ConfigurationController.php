<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Staff\Services\ConfigurationService;
use Exception;
use Illuminate\Validation\ValidationException;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;

class ConfigurationController extends Controller
{
    private $configurationService;
    private $utilities;
    public function __construct(ConfigurationService $configurationService, Utilities $utilities)
    {
        $this->configurationService = $configurationService;
        $this->utilities = $utilities;
    }

    
    public function save(Request $request){
        
        try{

            $request->validate([                                                           
                "name" =>"required", //{}
                "value"=>"required"
            ]);        
                          
            $response = $this->configurationService->save($request->all());        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }

    public function getAllConfigs(){
        
        try{                
                          
            $response = $this->configurationService->configurations();        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    } 
    
    public function getConfig(Request $request){
        
        try{       
            
            $response = $this->configurationService->configuration($request->name);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    } 
    
    public function givePermission(Request $request){
        try{            
            $request->validate([                                                           
                "staff_id" =>"required", 
                "permissions" =>"required", //[]
            ]);       
                          
            $response = $this->configurationService->givePermission($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function revokePermission(Request $request){
        try{        
            
            $request->validate([                                                           
                "staff_id" =>"required", 
                "permissions" =>"required", //[]
            ]);       
                          
            $response = $this->configurationService->revokePermission($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }   

    public function allPermissions(Request $request){
        try{                
                          
            $response = $this->configurationService->getAllPermissions();        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }       
    
    public function getStaffPermissions(Request $request){
        try{        
            
            if(!$request->id){
                return new APIResource("id field is required.", true, 400 );              
            }                                                                  
            $response = $this->configurationService->getStaffPermissions($request->id);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }


    public function createRole(Request $request){
         try{        
            
            $request->validate([                             
                "role_name" => "required", //                                                                                 
                "permission_ids" => "required", //[]                                                                                 
            ]);                                                                
            $response = $this->configurationService->createRole($request->get('role_name'), $request->get('permission_ids'));        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }            
    }

    public function deleteRole(Request $request){
         try{        
            
            if(!$request->id){
                return new APIResource("id field is required.", true, 400 );              
            }                            

            $response = $this->configurationService->deleteRole($request->id);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }            
    }

    public function updateRole(Request $request){
         try{        

            $request->validate([                             
                "role_id" => "required", //                                                                                 
                "role_name" => "required",                                                                                               
            ]);                                     

            $response = $this->configurationService->updateRole($request->get('role_id'),$request->get('role_name'),$request->get('permission_ids')??null);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }            
    }

    public function addPermission(Request $request){
         try{        
            
            $request->validate([                             
                "role_id" => "required", //                                                                 
                "permissions" => "required", //                          
            ]);                            

            $response = $this->configurationService->addPermission($request->get('role_id'),$request->get('permissions'));        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }            
    }

    public function removePermission(Request $request){
         try{        
                        
            $request->validate([                             
                "role_id" => "required", //                                                              
                "permissions" => "required", //                          
            ]);                                                        

            $response = $this->configurationService->removePermission($request->get('role_id'),$request->get('permissions'));        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }            
    }

    public function getRolePermissions(Request $request){
         try{        
            
            if(!$request->id){
                return new APIResource("role id field is required.", true, 400 );              
            }                                                                  
            $response = $this->configurationService->getRolePermissions($request->id);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }            
    }

    public function allRoles(Request $request){
         try{        

            $response = $this->configurationService->roles();        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }        
    }

    public function rolesOfOffices(Request $request){
        try{        

           $response = $this->configurationService->rolesOfOffices();        
           return new APIResource($response, false, 200 );

       }catch(Exception $e){
           return new APIResource($e->getMessage(), true, 400 );   
       }        
   }

    
    public function removeRole(Request $request){
        try{        
                        
            $request->validate([                             
                "staff_id" => "required", //                                                              
                "role_name" => "required", //                          
            ]);                
            
            $response = $this->configurationService->removeRoleFromStaff($request->get('role_name'),$request->get('staff_id'));        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }   
    }

    public function assignRole(Request $request){
        try{        
                        
            $request->validate([                             
                "staff_id" => "required", //                                                              
                "role_name" => "required", //                          
            ]);                
            
            $response = $this->configurationService->assignRoleToStaff($request->get('role_name'),$request->get('staff_id'));        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }   
    }

    public function  getStaffRoles(Request $request){
        try{        
            
            if(!$request->id){
                return new APIResource("id field is required.", true, 400 );              
            }                                                                  
            $response = $this->configurationService->getStaffRoles($request->id);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }
    

}
                                         

