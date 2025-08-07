<?php
namespace Modules\Application\Services;

use Exception;
use GuzzleHttp\Psr7\Request;
use Modules\Application\Repositories\ApplicantRepository;
use Illuminate\Validation\ValidationException;
use Modules\Application\Transformers\UtilResource;
use App\Repositories\ConfigurationRepository;
use App\Services\Utilities;
use Psy\Exception\ThrowUpException;
use App\Repositories\InvoiceTypeRepository as CentralInvoiceTypeRepository;
use Illuminate\Support\Facades\Storage;
use App\Models\Applicant;

class ApplicantsService{


    private $applicantRepository;
    private $utilities;
    private $centralInvoiceTypeRepository;
    public function __construct( ApplicantRepository $applicantRepository, CentralInvoiceTypeRepository $centralInvoiceTypeRepository, Utilities $utilities)
    {
        $this->applicantRepository = $applicantRepository;
        $this->utilities = $utilities;
        $this->centralInvoiceTypeRepository = $centralInvoiceTypeRepository;
    }

    public function createApplicant($request){

        // $checkUser =  $this->applicantRepository->checkEmailExist($request->get('email'));
        $data  = $request->all();
        if(!array_key_exists('level_id',$data)){
            $data['level_id'] = $this->applicantRepository->getLevelByOrder(1)?->id ?? 1;
        }

        $num = $this->utilities->getNextNumberFromString('applicants','application_number', $data);

       try{
           return $this->applicantRepository->create($request,$num);
        }
        catch(Exception $e){
            throw new Exception($e);
        }

    }

    public function applicants($request){

        $length = $request->get('length')??1;
        $paginateBy = $request->get('paginateBy');
        //$search = $request->get('search')['value'];
        $mode = $request->get('mode')??-1; // 1 => search mode

        $searchObj = $request->get('search')??"";
        if($searchObj != ""){

            $email = $searchObj['email']??"";
            $gender = $searchObj['gender']??"";
            $state = $searchObj['state_id']??"";
            $country = $searchObj['country_id']??"";
            $department_id = $searchObj['department_id']??"";
            $applied_programme_curriculum_id = $searchObj['applied_programme_curriculum_id']??"";
            $mode_of_entry_id = $searchObj['mode_of_entry_id']??"";
            $health_status = $searchObj['health_status']??"";
            $payment_open = $searchObj['payment_open']??"";
            $application_status = $searchObj['application_status']??"";
            $session_id = $searchObj['session_id']??"";
            $searchParam = [
                "email"=>$email,
                "gender"=>$gender,
                "state_id"=>$state,
                "country_id"=>$country,
                "session_id"=>$session_id,
                "department_id"=>$department_id,
                "applied_programme_curriculum_id"=>$applied_programme_curriculum_id,
                "mode_of_entry_id"=>$mode_of_entry_id,
                "health_status"=>$health_status,
                "payment_open"=>$payment_open,
                "application_status"=>$application_status
            ];

        }
        $relations = ['alevel','olevel'];
       return $this->applicantRepository->getApplicants($relations,$searchParam,$paginateBy,$length,$mode);
    }

    public function byID($request){

        $applicant = $this->applicantRepository->find($request->id);
        if($applicant){
            return $applicant;
        }
        throw new Exception("Applicant not Found",404);
    }

    public function updateApplicant($request){

        $email = $request->get('email');
        $id = $request->user()?->id;
        
        //$checkUser = $this->applicantRepository->checkEmailExist($email, $id);
             
        /* if($checkUser){
            throw new Exception("This email is already in use",409);
        } */
        
        return $this->applicantRepository->update($request->all(), $id);

    }

    public function uploadThisPicture($request){
        $file = $request->file('file');
        $applicant_id = auth('api:applicantsportal')->id();
        $disk = env('FILESYSTEM_DISK', 'public');

        if (!$file) {
            throw new \Exception('No file uploaded.');
        }

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Only JPEG, PNG files are allowed.');
        }

        if ($file->getSize() > 2048000) { // 2MB limit
            throw new \Exception('File size too large. Maximum size is 2MB.');
        }

        // Store the file on the specified disk and get the path
        $filePath = Storage::disk($disk)->putFile('profile-pictures', $file);
        $url = Storage::disk($disk)->url($filePath);

        // Delete old profile picture if exists
        $currentUser = auth('api:applicantsportal')->user();
        if ($currentUser->picture) {
            $oldPath = str_replace(Storage::disk($disk)->url(''), '', $currentUser->picture);
            Storage::disk($disk)->delete($oldPath);
        }

        // Update applicant record with new picture URL
        if($this->applicantRepository->update(["picture"=>$url],$applicant_id)){
            return auth('api:applicantsportal')->user();
        };
        throw new Exception("Error making upload, please try again", 500);
    }


    public function attempt($request)
    {
        // Support both email and JAMB number login
        $username = $request->username;

        // First try to find by email
        $applicant = $this->applicantRepository->checkApplicantCredentials($username);

        // If not found by email, try JAMB number
        if (!$applicant) {
            $applicant = Applicant::where('jamb_number', $username)->first();
        }

        return $applicant;
    }


    public function getApplicantOLevelResults($request)
    {
        $response =  $this->applicantRepository->getOlevelResults($request->user()->id, $request->get('session_id'));     
        return $response->olevel;
    }

    public function getApplicantALevelResults($id)
    {
        $response =  $this->applicantRepository->getAlevelResults($id);
        return $response->alevel;
    }

    public function getApplicantALevelResult($id)
    {
        return $this->applicantRepository->getAlevelResult($id);
    }

    public function saveOLevelResults($request)
    {

        $response =  $this->applicantRepository->insertOrUpdateOLevelResults($request);
        return $response;
    }

    public function updateDocument($request)
    {
        $file = $request->file('file');
        $name = $request->input('document_type');
        $disk = config('filesystems.default'); // Use default disk (S3, local, etc.)

        if (!$file) {
            throw new \Exception('No file uploaded.');
        }

        // Store file and get path (e.g., 'documents/filename.jpg')
        $filePath = Storage::disk($disk)->putFile('documents', $file);

        // Generate full URL (S3: https://bucket.s3.amazonaws.com/..., local: /storage/...)
        $url = Storage::disk($disk)->url($filePath);

        // Delete old file if it exists
        $existingFile = $this->applicantRepository->checkDocument($request->user()->id, $name);
        if ($existingFile?->name) {
            Storage::disk($disk)->delete($existingFile->url);
        }

        // Insert/update document record
        $response = $this->applicantRepository->insertOrUpdateDocument(
            $request->user()->id,
            \str_replace('_', ' ', $name),
            $url,
            $request->get('document_type')
        );

        return $response;
    }
    public function getDocuments($request){
        $response =  $this->applicantRepository->getDocuments($request);
        return $response;
    }

    public function saveALevel($request)
    {
        $data_to_alevel_tbl = $certificate_data =array();
        $data_to_alevel_tbl['applicant_id'] =$request->user()->id;
        $data_to_alevel_tbl['institution_attended'] =$request->get('institution_attended');
        $data_to_alevel_tbl['from'] = date('Y-m-d H:i:s',strtotime($request->get('from')));
        $data_to_alevel_tbl['to'] =date('Y-m-d H:i:s',strtotime($request->get('to')));
        $data_to_alevel_tbl['qualification_id'] =$request->get('qualification_id');
        $data_to_alevel_tbl['cgpa'] = $request->get('cgpa');
        $data_to_alevel_tbl['programme_studied'] = $request->get('programme_studied');
        $data_to_alevel_tbl['class_of_certificate'] = $request->get('class_of_certificate');
        $data_to_alevel_tbl['certificate_id'] = $request->get('certificate_id') ?? null;
        $data_to_alevel_tbl['session_id'] =$request->get('session_id');
        $certificate_data['id'] = $request->get('certificate_type_id')??"";
        $certificate_data['certificate_type_id'] =$request->get('certificate_type_id')??"";
        $certificate_data['certificate_id'] =$request->get('certificate_id')??"";
        $certificate_data['certificate_name'] =$request->get('certificate_name')??"";
        $certificate_data['url'] =$request->get('url')??""; //send to cloud, but pass empty for now
        $certificate_data['filename'] =$request->get('filename');
        $alevel_id = $request->get('id')??"";
        if($alevel_id === ""){
            //insert
            $response =  $this->applicantRepository->insertALevel( $data_to_alevel_tbl, $certificate_data);
        }else{
            //update
            $response =  $this->applicantRepository->updateALevel($alevel_id, $data_to_alevel_tbl, $certificate_data);
        }

        return $response;
    }

    public function getApplicantRegistrationProgress($id)
    {
        $applicant = $this->applicantRepository->find($id);
        if(!$applicant){
            throw new Exception("Applicant Not Found",404);
        }

        return $applicant->application_progress??0;
    }

    public function applicantPaymentDetails($request, $applicant)
    {
        $sessionId = $request->get('session_id');
        $response = $this->centralInvoiceTypeRepository->getPaymentDetails($sessionId, $applicant);
    
        if (!$response) {
            throw new Exception("Sorry, no payment setup for you yet", 404);
        }
    
        $details = collect($response);
    
        $applicationFee = $details->firstWhere('payment_short_name', 'application_fee');
        $acceptanceFee = $details->firstWhere('payment_short_name', 'acceptance_fee');
        $registrationFee = $details->firstWhere('payment_short_name', 'registration_fee');
        
        if(!$applicationFee){
            throw new Exception('Application fee is not yet available');
        }

        if ($applicationFee && $applicationFee['status'] == 'unpaid') {
            return [$applicationFee];
        }
            
        if (ConfigurationRepository::check('enable_acceptance_fee', 'true')){
            if(!$acceptanceFee){
                throw new Exception('Acceptance fee is not yet available');
            }

            if($acceptanceFee['status'] == 'unpaid') {
                if($applicant->admission_status == 'admitted'){
                    return [$acceptanceFee];
                }else{
                    return [];
                }
            }
        } 
            
        return $response;
    }

    public function applicantInvoiceTypes($session_id,$applicant)
    {
        $response = $this->centralInvoiceTypeRepository->getPaymentDetails($session_id,$applicant);
        if (!$response) {
            throw new Exception("Sorry, no payment setup for you yet", 404);
        }

        return $response;
    }

    public function applicantDashboard($session_id)
    {
        return $this->applicantRepository->applicantDashboard($session_id);
    }

}
?>
