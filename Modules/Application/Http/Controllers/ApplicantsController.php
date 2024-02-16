<?php

namespace Modules\Application\Http\Controllers;

use App\Http\Resources\APIResource;
use App\Jobs\CreateWallet;
use Error;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Modules\Application\Services\ApplicantsService;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicantRegister;
use App\Models\Applicant;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Utilities;
use Firebase\JWT\JWT;
class ApplicantsController extends Controller
{

    private $applicantService;
    public function __construct(ApplicantsService $applicantService)
    {
        $this->applicantService = $applicantService;
    }
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(Request $request)
    {
        
        try{
             $request->validate([
                'email' =>  'required|email|unique:applicants,email',
                'first_name' => 'required',
                'surname' => 'required',                                
                'session_id' => 'required',                
                "applied_programme_id" => 'required',                
                "mode_of_entry_id" => 'required',                                                                
            ]);

            $applicant = $this->applicantService->createApplicant($request);
            
            $accessToken = $applicant->createToken("AuthToken")->accessToken;
            try{

                Mail::to($applicant->email)->queue(new ApplicantRegister($applicant));
            }catch(\Exception $e){

            }
            
            return new APIResource(["applicant" => $applicant, "accessToken" => $accessToken], false, 200 );
        }catch(ValidationException $e){            
            return new APIResource( formatError(formatError($e->errors())), true, 400 );
        }catch(\Exception $e){
            return $e;
            return new APIResource($e->getMessage(), true, 400);
        }


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
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }


    /**
     * Login validated users
     * @param Request $request ** user login credentials
     * @return JsonResponse
     */
    public function login (Request $request) {
        try {
            //validate credentials
            $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            //query the database to check username
            $applicant = $this->applicantService->attempt($request);
            
            if (!$applicant) {
                throw new \Exception("Incorrect credentials", 404);
            }

            $payments = $this->applicantService->applicantInvoiceTypes($applicant->session_id, $applicant);
            foreach($payments as $payment){                
                if($payment['payment_short_name'] == 'registration_fee' && $payment['status'] == 'paid' && $applicant['matric_number'] == null){
                    //if($payment->payment_short_name == 'registration_fee' && $payment->status == 'paid' && $applicant->matric_number == null){
                    Utilities::makeNewStudent($applicant);
                }
            }

            $names = [strtolower($applicant->first_name), strtolower($applicant->middle_name), strtolower($applicant->surname)];
            //compare input password with hashed password from database and return error not matching
            //Auth::login($applicant);

            if (!$applicant || !(Hash::check($request->password, $applicant->password) || in_array(strtolower($request->password),$names))) {
                throw new \Exception("Incorrect credentials", 404);
            }

            $applicant->logged_in_time = now();
            $applicant->logged_in_count = $applicant->logged_in_count??0 + 1;
            $applicant->save();
            //generate access token for logged in user
            //$accessToken = $applicant->login();
            $accessToken = $applicant->createToken("AuthToken")->accessToken;


            //response structure
            return new APIResource(["applicant" => $applicant,"accessToken" => $accessToken ], false, 200);

        } catch (ValidationException $e) {

            //catch validation errors and return in response format
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){            
            //Log::error($e->getMessage());
            return $e;
            return new APIResource($e->getMessage(), true, 400 );
        }
    }


    /**
     * logout validated users
     * @return JsonResponse
     */

    public function logout() {
        //delete generated token
        Auth::guard('api:applicantsportal')->user()->tokens()->delete();
        //return response
        return new APIResource("you logged out", false, 200);
    }


    public function getApplicants(Request $request)
    {
        try{

            $request->validate([
                'paginateBy' => 'required',
                'mode' => 'required',// 1 or -1
                'search' => 'required',// {} objects

            ]);

            $response = $this->applicantService->applicants($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getApplicantById(Request $request){
        try{

            Validator::make($request->all(), [
                'id' => 'required',
            ]);
            $response = $this->applicantService->byID($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function uploadPicture(Request $request){
        try {
            Validator::make($request->all(), [
                'file' => 'required',
            ]);
            return new APIResource($this->applicantService->uploadThisPicture($request), false, 200);
        } catch (ValidationException $e) {
            return new APIResource(formatError($e->errors()), true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function oLevelResults(Request $request)
    {
        try{

            Validator::make($request->all(), [
                'id' => 'required',
                'session_id'=>'required'
            ]);
            $response = $this->applicantService->getApplicantOLevelResults($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }


    }

    public function aLevelResults(Request $request)
    {
        try{

            $id = $request->id;
            Validator::make($request->all(), [
                'id' => 'required',
            ]);
            $response = $this->applicantService->getApplicantALevelResults($id);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function aLevelResult(Request $request)
    {
        try {

            $id = $request->id;
            Validator::make($request->all(), [
                'id' => 'required',
            ]);
            $response = $this->applicantService->getApplicantALevelResult($id);

            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(formatError($e->errors()), true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function updateOLevelResults(Request $request)
    {
        try{

            $request->validate([
                'applicant_id'=>'required',
                'exam_type_id'=>'required',
                'examination_number'=>'required',
                'subjects_grades'=>'required',
                'month'=>'required',
                'year'=>'required',
                'session_id'=>'required',
            ]);

            $response = $this->applicantService->saveOLevelResults($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function updateALevel(Request $request)
    {
        try{

            $request->validate([
                'institution_attended' => 'required',
                'from' => 'required',
                'to' => 'required',
                'applicant_id' => 'required',
                'qualification_id' => 'required'
            ]);

            $response = $this->applicantService->saveALevel($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }


    }

    public function registrationProgress(Request $request)
    {
        try{



            $id = $request->applicant_id??"";
            if($id == ''){
                throw new \Exception('applicant id is required');
            }

            $response = $this->applicantService->getApplicantRegistrationProgress($id);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function paymentDetails(Request $request){

        try{
            $request->validate([
                'session_id' => 'required',
            ]);            
            $response = $this->applicantService->applicantPaymentDetails($request, $request->user());


            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError($e->errors()), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getWallet(){
        try{
            $applicant = auth('api:applicantsportal')->user();
            $response = $applicant->wallet;
            return new APIResource($response, false, 200 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

}

