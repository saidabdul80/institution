<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\ApplicantService;
use Modules\Staff\Transformers\UtilResource;


class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $applicantService;
    public function __construct(ApplicantService $applicantService)
    {
        $this->applicantService = $applicantService;
    }
    public function updateApplicant(Request $request)
    {

        try{

            Validator::make($request->all(), [
                'id' => 'required'
            ]);

            $applicant = $this->applicantService->updateApplicant($request);
            return new APIResource($applicant, false, 200 );
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, $e->getCode() );
        }

    }

    public function exportApplicants(Request $request){        
        try{         
            return $this->applicantService->exportApplicants($request);                    
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );          
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );   
        }
        
    }
}
