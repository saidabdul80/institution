<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\MenuService;
use Modules\Staff\Transformers\UtilResource;


class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $menuService;
    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }
    
    public function updateSummerPortalMenu(Request $request){
        try{

            $request->validate([                             
                "id" => "required",                
                "status" => "required"                                
            ]);        
                          
            $response = $this->menuService->updateSummerPortalMenu($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function allSummerPortalMenus(Request $request){
        try{            
                          
            $response = $this->menuService->allSummerPortalMenus($request);        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function updateSummerPortalSubMenu(Request $request){
        try{

            $request->validate([                             
                "id" => "required",                
                "status" => "required"                
            ]);        
                          
            $response = $this->menuService->updateSummerPortalSubMenu($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function allSummerPortalSubMenus(Request $request){
        try{            
                          
            $response = $this->menuService->allSummerPortalSubMenus($request);        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function updateStudentPortalMenu(Request $request){
        try{

            $request->validate([                             
                "id" => "required",                
                "status" => "required"                
            ]);        
                          
            $response = $this->menuService->updateStudentPortalMenu($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function allStudentPortalMenus(Request $request){
        try{            
                          
            $response = $this->menuService->allStudentPortalMenus($request);        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }    

    public function updateStudentPortalSubMenu(Request $request){
        try{

            $request->validate([                             
                "id" => "required",                
                "status" => "required"                
            ]);        
                          
            $response = $this->menuService->updateStudentPortalSubMenu($request);        
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function allStudentPortalSubMenus(Request $request){
        try{            
                          
            $response = $this->menuService->allStudentPortalSubMenus($request);        
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }



    public function updateApplicantsPortalMenu(Request $request){
        try{
    
            $request->validate([                             
                "id" => "required",                
                "status" => "required"                
            ]);        
                          
            $response = $this->menuService->updateApplicantsPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }
    
    public function allApplicantsPortalMenus(Request $request){
        try{            
                          
            $response = $this->menuService->allApplicantsPortalMenus($request);        
            return new APIResource($response, false, 200 );
    
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }    
    
    public function updateApplicantsPortalSubMenu(Request $request){
        try{
    
            $request->validate([                             
                "id" => "required",                
                "status" => "required"                
            ]);        
                          
            $response = $this->menuService->updateApplicantsPortalSubMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }
    
    public function allApplicantsPortalSubMenus(Request $request){
        try{            
                          
            $response = $this->menuService->allApplicantsPortalSubMenus($request);        
            return new APIResource($response, false, 200 );
    
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    
    
    
    
    
    

    public function createStudentPortalMenu(Request $request){        
        try{
    
            $request->validate([   
                "title"=>"required",                          
                "icon"=>"required",
                "path"=>"required",                
            ]);        
                          
            $response = $this->menuService->createStudentPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function createStudentPortalSubMenu(Request $request){        
        try{
    
            $request->validate([   
                "title"=>"required",                          
                "icon"=>"required",
                "path"=>"required",                
            ]);        
                          
            $response = $this->menuService->createStudentPortalSubMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function createApplicantPortalMenu(Request $request){        
        try{
    
            $request->validate([   
                "title"=>"required",                          
                "icon"=>"required",
                "path"=>"required",                
            ]);        
                          
            $response = $this->menuService->createApplicantPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }
    
    public function createApplicantPortalSubMenu(Request $request){        
        try{
    
            $request->validate([   
                "title"=>"required",                          
                "icon"=>"required",
                "path"=>"required",                
            ]);        
                          
            $response = $this->menuService->createStudentPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function createSummerPortalMenu(Request $request){        
        try{
    
            $request->validate([   
                "title"=>"required",                          
                "icon"=>"required",
                "path"=>"required",                
            ]);        
                          
            $response = $this->menuService->createSummerPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function createSummerPortalSubMenu(Request $request){        
        try{
    
            $request->validate([   
                "title"=>"required",                          
                "icon"=>"required",
                "path"=>"required",                
            ]);        
                          
            $response = $this->menuService->createSummerPortalSubMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }




    public function deleteApplicantPortalMenu(Request $request){                
        try{
    
            $request->validate([   
                "id"=>"required",                                          
            ]);        
                          
            $response = $this->menuService->deleteApplicantPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function deleteApplicantPortalSubMenu(Request $request){                
        try{
    
            $request->validate([   
                "id"=>"required",                                          
            ]);        
                          
            $response = $this->menuService->deleteApplicantPortalSubMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function deleteStudentPortalMenu(Request $request){                
        try{
    
            $request->validate([   
                "id"=>"required",                                          
            ]);        
                          
            $response = $this->menuService->deleteStudentPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function deleteStudentPortalSubMenu(Request $request){                
        try{
    
            $request->validate([   
                "id"=>"required",                                          
            ]);        
                          
            $response = $this->menuService->deleteStudentPortalSubMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function deleteSummerPortalMenu(Request $request){                
        try{
    
            $request->validate([   
                "id"=>"required",                                          
            ]);        
                          
            $response = $this->menuService->deleteSummerPortalMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

    public function deleteSummerPortalSubMenu(Request $request){                
        try{
    
            $request->validate([   
                "id"=>"required",                                          
            ]);        
                          
            $response = $this->menuService->deleteSummerPortalSubMenu($request);        
            return new APIResource($response, false, 200 );
    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
    }

}

