<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Services\AdmissionService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;


class AdmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $admissionService;
    private $utilities;
    public function __construct(AdmissionService $admissionService, Utilities $utilities)
    {
        $this->admissionService = $admissionService;
        $this->utilities=  $utilities;
    }

    public function applicantAdmission(Request $request){

        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                //"maintain_programme_id" =>"required",
                "session_id"   =>"required",                
            ]);

            $response = $this->admissionService->acceptApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function bulkApplicantAdmission(Request $request){
        try{

            $request->validate([
                "file" => "required",
            ]);

            $response = $this->admissionService->bulkAcceptApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }
    public function unAdmitApplicant(Request $request){
        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                "session_id"   =>"required",
            ]);

            $response = $this->admissionService->rejectApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function rejectApplicant(Request $request){

    }

    public function allApplicants(Request $request){
        try{

            $request->validate([
                'paginateBy' => 'required',
                'status' => 'required',//paid, unpaid
                'session_id'=>'required',
                'payment_name'=>'required'

            ]);

            $response = $this->admissionService->paidApplicants($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function activateStudent(Request $request){

        try{

            $request->validate([
                "matric_number" => "required", //[]
                "session_id" => "required", //this is used to check if student paid current school fees
            ]);

            $response = $this->admissionService->activateStudent($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateAdmissionStatus(Request $request){

        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                "status" => "required", //
            ]);
            $response = $this->admissionService->updateAdmissionStatus($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateQualifiedStatus(Request $request){

        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                "status" => "required", //
            ]);
            $response = $this->admissionService->updateQualifiedStatus($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }



    public function getApplicant(Request $request){

        try{

            $request->validate([
                "session_id" => "required", //
                //paginateBy
            ]);

            $response = $this->admissionService->getApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getStudents(Request $request){

        try{

            $request->validate([
                "session_id" => "required", //
                //paginateBy
                //"search" =>"required" //{applicant_state:...,}
            ]);

            $response = $this->admissionService->getStudents($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getBatches(Request $request){

        try{

            $request->validate([
                "session_id" => "required", //
            ]);

            $response = $this->admissionService->getBatches($request->get('session_id'));
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }


    public function changeApplicantProgramme(Request $request){

        try{
            $request->validate([
                'applicant_id'   =>'required',
                'faculty_id' => 'required',
                'department_id' => 'required',
                'programme_id' => 'required'
            ]);
            $response = $this->admissionService->changeAdmittedProgramme($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function createBatch(Request $request){

        try{

            $request->validate([
                "name"=>"required"
            ]);

            $response = $this->admissionService->createBatch($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function updateBatch(Request $request){

        try{

            $request->validate([
                "id" => "required",
                "name"=>"required"
            ]);

            $response = $this->admissionService->updateBatch($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function deleteBatch(Request $request){

        try{

            $request->validate([
                "id" => "required",
            ]);

            $response = $this->admissionService->deleteBatch($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getAllBatches(Request $request){
        try{

            $response = $this->admissionService->fetchAdmissionBatches();
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource($e->errors(), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getTemplate(){
        return  $this->utilities->getFile('admissionUploadTemplate.csv');
    }


}
