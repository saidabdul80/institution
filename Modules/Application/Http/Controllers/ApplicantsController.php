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
                'password' => 'required|confirmed',                                         
                'applied_programme_curriculum_id' => 'required',                
                'mode_of_entry_id' => 'nullable',                                                                
            ]);
            

            $applicant = $this->applicantService->createApplicant($request);
            
            $accessToken = $applicant->createToken("AuthToken")->accessToken;
            try{

                Mail::to($applicant->email)->queue(new ApplicantRegister($applicant));
            }catch(\Exception $e){

            }
            
            return new APIResource(["applicant" => $applicant, "accessToken" => $accessToken], false, 200 );
        }catch(ValidationException $e){            
            return new APIResource( formatError(formatError(array_values($e->errors())[0])), true, 400 );
        }catch(\Exception $e){
            Log::error($e);
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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
                throw new \Exception("Incorrect credentials", 400);
            }

            $applicant->logged_in_time = now();
            $applicant->logged_in_count = $applicant->logged_in_count??0 + 1;
            $applicant->save();

            //generate access token for logged in user
            $accessToken = $applicant->createToken("AuthToken")->accessToken;

            // Check if application fee is paid for imported applicants
            $requiresPayment = $applicant->is_imported && !$applicant->application_fee_paid;

            //response structure
            return new APIResource([
                "applicant" => $applicant,
                "accessToken" => $accessToken,
                "requires_payment" => $requiresPayment
            ], false, 200);

        } catch (ValidationException $e) {

            //catch validation errors and return in response format
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
        }catch(Exception $e){            
            //Log::error($e->getMessage());
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getApplicantById(Request $request){
        try{

            // Validator::make($request->all(), [
            //     'id' => 'required',
            // ]);
            // $response = $this->applicantService->byID($request);

            return new APIResource($request->user(), false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function oLevelResults(Request $request)
    {
        try{

            Validator::make($request->all(), [                
                'session_id'=>'required'
            ]);
            $response = $this->applicantService->getApplicantOLevelResults($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function updateOLevelResults(Request $request)
    {
        try{
            
            $request->validate([             
                'exam_type_id'=>'required',
                'examination_number'=>'required',
                'subjects_grades'=>'required',
                'month'=>'required',
                'year'=>'required',
                'session_id'=>'required',
            ]);

            $response = $this->applicantService->saveOLevelResults($request);
            return new APIResource("Saved successfuly", false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getDocuments(Request $request){
        try{
          
            $response = $this->applicantService->getDocuments($request);

            return new APIResource($response, false, 200 );
        }catch(Exception $e){
            
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateDocument(Request $request)
    {
        try{
     
          $validated = $request->validate([
                    'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB max
                    'document_type' => 'required|string',
                    // Add other validation rules as needed
                ]);


            $response = $this->applicantService->updateDocument($request);

            return new APIResource("Saved Successfuly", false, 200 );
        }catch(ValidationException $e){
            Log::error($e);
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
        }catch(Exception $e){
            Log::error($e);
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
                'qualification_id' => 'required'
            ]);

            $response = $this->applicantService->saveALevel($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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
            return new APIResource(formatError(array_values($e->errors())[0]), true, 400 );
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

    /**
     * Final submission of application
     */
    public function finalSubmit(Request $request)
    {
        try {
            $applicant = auth('api-applicants')->user();
            // Check if already final submitted
            if ($applicant->is_final_submitted) {
                return new APIResource('Application has already been final submitted', true, 400);
            }

            $request->validate([
                'notes' => 'nullable|string|max:500'
            ]);

            // Perform final submission
            $applicant->finalSubmit($request->get('notes'));

            // Generate acknowledgment slip
            $documentService = new \App\Services\DocumentGenerationService();
            $acknowledgmentSlip = $documentService->generateAcknowledgmentSlip($applicant);

            return new APIResource([
                'message' => 'Application has been final submitted successfully',
                'final_submitted_at' => $applicant->final_submitted_at,
                'is_final_submitted' => $applicant->is_final_submitted,
                'acknowledgment_slip' => $acknowledgmentSlip
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error in final submission: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Download acknowledgment slip
     */
    public function downloadAcknowledgmentSlip(Request $request)
    {
        try {
            $applicant = auth('api-applicants')->user();

            if (!$applicant->is_final_submitted) {
                return new APIResource('Application has not been final submitted yet', true, 403);
            }

            // Generate acknowledgment slip
            $documentService = new \App\Services\DocumentGenerationService();
            $acknowledgmentSlip = $documentService->generateAcknowledgmentSlip($applicant);

            if (!$acknowledgmentSlip) {
                return new APIResource('Unable to generate acknowledgment slip', true, 500);
            }

            return new APIResource($acknowledgmentSlip, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Download verification slip (after acceptance fee payment)
     */
    public function downloadVerificationSlip(Request $request)
    {
        try {
            $applicant = auth('api-applicants')->user();

            // Check if applicant is admitted
            if ($applicant->admission_status !== 'admitted') {
                return new APIResource('You have not been admitted yet', true, 403);
            }

            // Check if acceptance fee has been paid
            if (!$this->hasAcceptanceFeePaid($applicant)) {
                return new APIResource('Acceptance fee has not been paid yet', true, 403);
            }

            // Generate verification slip
            $documentService = new \App\Services\DocumentGenerationService();

            // Get payment data for the template
            $acceptanceFeePayment = $this->getAcceptanceFeePayment($applicant);
            $paymentData = null;

            if ($acceptanceFeePayment) {
                $paymentData = [
                    'payment_date' => $acceptanceFeePayment->paid_at ? $acceptanceFeePayment->paid_at->format('F j, Y') : now()->format('F j, Y'),
                    'payment_reference' => $acceptanceFeePayment->payment_reference,
                    'amount' => number_format($acceptanceFeePayment->amount, 2)
                ];
            }

            $verificationSlip = $documentService->generateVerificationSlip($applicant, $paymentData);

            if (!$verificationSlip) {
                return new APIResource('Unable to generate verification slip', true, 500);
            }

            return new APIResource($verificationSlip, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Check if applicant has paid acceptance fee
     */
    private function hasAcceptanceFeePaid($applicant): bool
    {
        return $applicant->invoices()
            ->whereHas('invoiceType.paymentCategory', function($query) {
                $query->where('short_name', 'acceptance_fee');
            })
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Get acceptance fee payment details
     */
    private function getAcceptanceFeePayment($applicant)
    {
        $acceptanceFeeInvoice = $applicant->invoices()
            ->whereHas('invoiceType.paymentCategory', function($query) {
                $query->where('short_name', 'acceptance_fee');
            })
            ->where('status', 'paid')
            ->first();

        if ($acceptanceFeeInvoice) {
            return $acceptanceFeeInvoice->payments()
                ->where('status', 'successful')
                ->latest()
                ->first();
        }

        return null;
    }

    /**
     * Get admission letter for admitted applicant
     */
    public function getAdmissionLetter(Request $request)
    {
        try {
            $applicant = auth('api-applicants')->user();

            // Check if applicant is admitted and published
            if ($applicant->admission_status !== 'admitted') {
                return new APIResource('You have not been admitted yet', true, 403);
            }

            if (!$applicant->isPublished()) {
                return new APIResource('Your admission has not been published yet', true, 403);
            }

            // Check if acceptance fee has been paid
            $acceptanceFeePaid = $applicant->invoices()
                ->whereHas('invoiceType.paymentCategory', function($q) {
                    $q->where('short_name', 'acceptance_fee');
                })
                ->where('status', 'paid')
                ->exists();

            if (!$acceptanceFeePaid) {
                return new APIResource('Acceptance fee must be paid before accessing admission letter', true, 403);
            }

            // Check if documents have been verified
            if ($applicant->verification_status !== 'verified') {
                return new APIResource('Your documents are still under verification. Please wait for verification to complete.', true, 403);
            }

            // Check if admission letter has been officially issued
            if (!$applicant->admission_letter_issued) {
                return new APIResource('Your admission letter has not been issued yet. Please wait for the admissions office to complete the process.', true, 403);
            }

            // Get admission letter template from configuration
            $template = \App\Services\Util::getConfigValue('admission_letter_template');

            if (empty($template)) {
                return new APIResource('Admission letter template not configured', true, 500);
            }

            // Prepare template data
            $templateData = $this->prepareAdmissionLetterData($applicant);

            // Replace placeholders in template
            $admissionLetter = $this->replacePlaceholders($template, $templateData);

            return new APIResource([
                'admission_letter_html' => $admissionLetter,
                'applicant_name' => $applicant->first_name . ' ' . $applicant->surname,
                'application_number' => $applicant->application_number,
                'programme_name' => $applicant->programme->name ?? 'N/A',
                'admission_date' => $applicant->published_at ? $applicant->published_at->format('F j, Y') : 'N/A'
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error getting admission letter: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Download admission letter as PDF
     */
    public function downloadAdmissionLetter(Request $request)
    {
        try {
            $applicant = auth('api-applicants')->user();

            // Check if applicant is admitted and published
            if ($applicant->admission_status !== 'admitted') {
                return new APIResource('You have not been admitted yet', true, 403);
            }

            if (!$applicant->isPublished()) {
                return new APIResource('Your admission has not been published yet', true, 403);
            }

            // Check if acceptance fee has been paid
            $acceptanceFeePaid = $applicant->invoices()
                ->whereHas('invoiceType.paymentCategory', function($q) {
                    $q->where('short_name', 'acceptance_fee');
                })
                ->where('status', 'paid')
                ->exists();

            if (!$acceptanceFeePaid) {
                return new APIResource('Acceptance fee must be paid before accessing admission letter', true, 403);
            }

            // Check if documents have been verified
            if ($applicant->verification_status !== 'verified') {
                return new APIResource('Your documents are still under verification. Please wait for verification to complete.', true, 403);
            }

            // Check if admission letter has been officially issued
            if (!$applicant->admission_letter_issued) {
                return new APIResource('Your admission letter has not been issued yet. Please wait for the admissions office to complete the process.', true, 403);
            }

            // Get admission letter template
            $template = \App\Services\Util::getConfigValue('admission_letter_template');

            if (empty($template)) {
                return new APIResource('Admission letter template not configured', true, 500);
            }

            // Prepare template data
            $templateData = $this->prepareAdmissionLetterData($applicant);

            // Replace placeholders in template
            $admissionLetter = $this->replacePlaceholders($template, $templateData);

            // Generate PDF using DomPDF
            $pdf = \PDF::loadHTML($admissionLetter);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'admission_letter_' . $applicant->application_number . '.pdf';

            return $pdf->download($filename);

        } catch (Exception $e) {
            Log::error('Error downloading admission letter: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Prepare data for admission letter template
     */
    private function prepareAdmissionLetterData($applicant)
    {
        return [
            // School information
            'school_name' => \App\Services\Util::getConfigValue('school_name') ?? 'Institution Name',
            'school_address' => \App\Services\Util::getConfigValue('school_address') ?? '',
            'school_city' => \App\Services\Util::getConfigValue('school_city') ?? '',
            'school_state' => \App\Services\Util::getConfigValue('school_state') ?? '',
            'school_email' => \App\Services\Util::getConfigValue('school_email') ?? '',
            'school_phone' => \App\Services\Util::getConfigValue('school_phone') ?? '',
            'school_logo' => \App\Services\Util::getConfigValue('school_logo') ?? '',

            // Applicant information
            'applicant_title' => $applicant->title ?? 'Mr/Ms',
            'applicant_first_name' => $applicant->first_name,
            'applicant_middle_name' => $applicant->middle_name ?? '',
            'applicant_surname' => $applicant->surname,
            'applicant_address' => $applicant->address ?? '',
            'applicant_city' => $applicant->lga->name ?? '',
            'applicant_state' => $applicant->state->name ?? '',
            'application_number' => $applicant->application_number,

            // Academic information
            'programme_name' => $applicant->programme->name ?? 'N/A',
            'level_name' => $applicant->level->title ?? 'N/A',
            'faculty_name' => $applicant->programme->faculty->name ?? 'N/A',
            'department_name' => $applicant->programme->department->name ?? 'N/A',
            'mode_of_study' => $applicant->modeOfEntry->name ?? 'Full Time',
            'admission_batch' => $applicant->batch->name ?? 'N/A',
            'academic_session' => $applicant->session->name ?? 'N/A',

            // Dates
            'current_date' => now()->format('F j, Y'),
            'admission_date' => $applicant->published_at ? $applicant->published_at->format('F j, Y') : 'N/A',
        ];
    }

   /**
     * Replace placeholders in template with actual data
     */
    private function replacePlaceholders($template, $data)
    {
        // Replace simple placeholders like {{name}}, {{email}}, etc.
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        // Handle conditional blocks like {{#if someKey}}...{{/if}}
        $template = preg_replace_callback('/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($data) {
            $condition = $matches[1];
            $content = $matches[2];
            return !empty($data[$condition]) ? $content : '';
        }, $template);

        return $template;
    }

}

