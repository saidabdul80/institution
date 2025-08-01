<?php
 namespace Modules\Application\Repositories;

use App\Jobs\CreateWallet;
use App\Models\Alevel;
use App\Models\Applicant;
use App\Models\ApplicantCertificate;
use App\Models\ApplicantQualification;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Models\InvoiceType;
use App\Models\Level;
use App\Models\OlevelResult;
use App\Models\PaymentCategory;
use Illuminate\Support\Facades\App;

 use function PHPUnit\Framework\isEmpty;
 use function PHPUnit\Framework\isNull;
use function PHPUnit\Framework\throwException;

 use Illuminate\Validation\ValidationException;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Application\Database\factories\AlevelFactory;
use App\Models\Faculty;
use App\Repositories\ConfigurationRepository;
use App\Services\Utilities;
use PharIo\Manifest\ApplicationName;
use PhpParser\Node\Expr\Throw_;
use Throwable;
 class ApplicantRepository{

    private $applicant;
    private $olevelresult;
    private $alevel;
    private $applicantCertificate;
    private $invoiceType;
    private $paymentCategory;
    private $faculty;
    private $utilities;
    private $applicantQualification;
    private $configurationRepository;
    private $level;
    public $fields;
    public function __construct(Applicant $applicant, OlevelResult $olevelresult, Alevel $alevel, ApplicantCertificate $applicantCertificate, ApplicantQualification $applicantQualification,InvoiceType $invoiceType,PaymentCategory $paymentCategory, Faculty $faculty, Utilities $utilities, ConfigurationRepository $configurationRepository, Level $level)
    {
        $this->applicant = $applicant;
        $this->olevelresult = $olevelresult;
        $this->alevel = $alevel;
        $this->applicantCertificate = $applicantCertificate;
        $this->applicantQualification = $applicantQualification;
        $this->invoiceType = $invoiceType;
        $this->paymentCategory = $paymentCategory;
        $this->faculty = $faculty;
        $this->utilities = $utilities;
        $this->configurationRepository = $configurationRepository;
        $this->level = $level;

        $this->fields = [
            "first_name", "middle_name", "surname", "phone_number", "gender", "email", "application_number", "batch_id", "session_id", "lga_id", "country_id", "state_id", "applied_level_id", "level_id", "applied_programme_id", "programme_id", "programme_type_id", "mode_of_entry_id", "application_status_id", "department_id", "faculty_id", "date_of_birth", "years_of_experience", "working_class", "category", "present_address", "permanent_address", "guardian_full_name", "guardian_phone_number", "guardian_address", "guardian_email", "guardian_relationship", "sponsor_full_name", "sponsor_type", "sponsor_address", "next_of_kin_full_name", "next_of_kin_address", "next_of_kin_phone_number", "next_of_kin_relationship", "wallet_number", "prev_institution", "prev_year_of_graduation", "health_status", "health_status_description", "blood_group", "disability", "religion", "marital_status", "admission_status", "admission_serial_number", "qualified_status", "final_submission", "application_progress", "logged_in_time", "logged_in_count", "picture", "signatuare", "jamb_number", "scratch_card", "entrance_exam_score", "entrance_exam_status", "deleted_at", "deleted_by", "password", "created_at", "updated_at",
            "jamb_score"
        ];
    }

    public function searchUserBy($column_name, $value){
       return $this->applicant->searchUserBy($column_name, $value);
    }

    public function getLastNumberFromString($tablename, $column_name){
        $number_format = $this->configurationRepository->fetch($column_name.'_format');

        $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(SUBSTRING_INDEX(tablename.$column_name,'/',-1), UNSIGNED)) as lastNumber")->first();

        if(is_null($num)){
            return 1000;
        }else{
            $number = (int) $num->lastNumber;
            return $number;
        }
     }

    public function create($request, $num){

        $faculty_id = DB::table("departments")->where("id",$request->get('department_id'))->first()?->faculty_id;
        /* 
         $application_number_format = DB::table('configurations')->where(['name'=>'application_number_format','programme_type_id'=>$request->get('programme_type_id')])->first();
        */ 
        $current_application_session = (int) DB::table('configurations')->where('name','current_application_session')->first()?->value;
               
        $data  = $request->all();  
        if(!array_key_exists('level_id',$data)){
            $data['level_id'] = $this->getLevelByOrder(1)?->id ?? 1;
        }     
                

        $applicant = new Applicant();
        //$applicant->wallet_number =  $wallet_number;
        //$applicant->application_number = $this->utilities->number_formater($application_number_format,$data, $num);
        $applicant->application_number = $num;
        $applicant->first_name = $request->get('first_name');
        $applicant->middle_name = $request->get('middle_name');
        $applicant->surname = $request->get('surname');
        $applicant->email = $request->get('email');
        $applicant->session_id =  $current_application_session;
        $applicant->faculty_id =  $request->get('faculty_id');
        $applicant->phone_number = $request->get('phone_number');
        $applicant->programme_type_id = $request->get('programme_type_id');
        $applicant->gender = $request->get('gender')??"";
        $applicant->department_id = $request->get('department_id');
        $applicant->faculty_id = $faculty_id;
        $applicant->applied_programme_id = $request->get('applied_programme_id');
        $applicant->mode_of_entry_id = $request->get('mode_of_entry_id');
        $applicant->password = Hash::make($request->password);
        $applicant->country_id = $request->get('country_id');
        $applicant->state_id = $request->get('state_id');
        $applicant->lga_id = $request->get('lga_id');
        $applicant->applied_level_id = $request->get('applied_level_id') ?? null;
        $applicant->application_status_id = $request->get('application_status_id');
        $applicant->jamb_number = $request->get('jamb_number')??null;
        $applicant->scratch_card = $request->get('scratch_card')??null;
        // $applicant->admission_serial_number =str_pad($this->newSerialNumber($request->get('session_id')),5,'0',STR_PAD_LEFT);
        try{
            $applicant->save();
            $applicant->application_progress = $this->getApplicationProgress($applicant);//update applicant progress
            $applicant->save();
            //CreateWallet::dispatch($applicant, $wallet_number);
            return $applicant;

        }catch(Exception $e){
            throw new Exception($e->getMessage(), 409);
        }
    }

    private function newSerialNumber($session_id){
        $num = DB::table('applicants','t')->selectRaw("MAX( CONVERT(t.admission_serial_number,UNSIGNED)) as lastNumber")->whereRaw("session_id = '$session_id'")->first();
        if(is_null($num)){
            return 1;
        }else{
            $number = (int) $num->lastNumber +1;
            return $number;
        }
    }


    public function checkEmailExist($email,$id=null)
    {
        if(!is_null($id)){
            return $this->applicant::where('email', $email)->whereNotIn('id',[$id])->exists();
        }else{
            return $this->applicant::where('email', $email)->exists();
        }
    }

    public function find($id)
    {
     
        return $this->applicant::with(['qualifications'=>function($query){
            $query->with('qualification');
        }])->where('id',$id)->first();
    }

    public function update($data, $id=null)
    {
        if(isset($data['id'])){            
            unset($data['id']);
        }

        if(array_key_exists("admission_status", $data)){
            unset($data["admission_status"]);
        }

        if(array_key_exists("qualified_status", $data)){
            unset($data["qualified_status"]);
        }

        if(array_key_exists("entrance_exam_status", $data)){
            unset($data["entrance_exam_status"]);
        }
        $applicant = $this->applicant::find($id);
        

        if($applicant->admission_status == "admitted"){
            //reject change of programme if admitted
            if(array_key_exists("programme_id", $data)){
                unset($data["programme_id"]);
            }
            if(array_key_exists("applied_programme_id", $data)){
                unset($data["applied_programme_id"]);
            }
        }
        $filteredData = array_filter($data, function ($key) {
                return in_array($key, $this->fields);
            }, ARRAY_FILTER_USE_KEY);


        $user = $this->applicant->where('id',$id)->update($filteredData);
        
        if($user){
            $user = $this->applicant::find($id);
            $user->application_progress = $this->getApplicationProgress($this->applicant::find($id));
            $user->save();
            return $user;
        }
    }

    public function getApplicationProgress($user, $initial = true){
        $arrKeys = ['logged_in_count','password','logged_in_count','application_progress','id'];
        $count = $total= 0;
        foreach(json_decode(json_encode($user)) as $key => $entity){
            if(!in_array($key, $arrKeys)){
                if(empty($entity)){
                    $count++;
                }
                $total++;
            }
        }

        if(!$initial){

            $invoiceTypes = $this->getPaymentDetails($user->session_id, $user);
            $total = $total + count($invoiceTypes);

            $payments = $this->getApplicantPayments($user->id);
            $count += count($payments);

        }

        $value = $total - $count ;
        if($value>0){
            $percent = ($value/$total) *100;
        }else if($value === 0){
            $percent = 100;
        }else{
            $percent = 100;
        }

        return   floor($percent);

    }
    /* public function getApplicantInvoiceTypes(){
        return DB::table('invoice_types')->where(['owner_type'=>'applicant','status'])
    } */
    public function getApplicantPayments($id){
        return DB::table('payments')
        ->select('owner_id','invoice_id', DB::raw('SUM(amount) as totalPaid'))
        ->where(['status'=>'successful','owner_id'=>$id, 'owner_type'=>'applicant'])
        ->groupBy(['owner_id','invoice_id'])
        ->get();


    }
    public function generateNumber($getLast)
    {

        return str_pad($getLast, 4, '0', STR_PAD_LEFT);

    }

    public function getApplicants($relations,$searchParam,$paginateBy, $length, $mode)
    {
        if($length != -1)
        {
            if($mode == 1){

                $applicants = $this->applicant::with(['qualifications'=>function($query){
                    $query->with('qualification');
                }])->searchData($searchParam,$paginateBy, $length,$relations);

            }else{

                $applicants = $this->applicant::with(['qualifications'=>function($query){
                    $query->with('qualification');
                }])->fetchData($paginateBy, $length,$relations);

            }
        }else{
            $applicants = $this->applicant::with(['qualifications'=>function($query){
                $query->with('qualification');
            }])->searchData($searchParam,$paginateBy, $length,$relations);

        }
        return $applicants;
    }

    public function checkApplicantCredentials($username)
    {
        
        return $this->applicant::with(['olevel','alevel','qualifications'=>function($query){
            $query->with('qualification');
        }])->where('email', $username)->orWhere('application_number', $username)->latest()->first();
    }

    public function getOlevelResults($id, $session_id){
        return $this->applicant::with(['olevel'=>function($query) use($session_id){
            $query->where('session_id',$session_id);
        }])->where('id', $id)->first();
    }

    public function getAlevelResults($id){
        return $this->applicant::with(['alevel'=>function($query){
            $query->with('certificates');
        }])->where('id', $id)->first();
    }

    public function getAlevelResult($id)
    {
        return $this->alevel::where('applicant_id', $id)->first();
    }

    public function  insertOrUpdateOLevelResults($request){

        $subject_grades = $request->get('subjects_grades');
        if(gettype($subject_grades) ==="array"){
            $subject_grades = json_encode($subject_grades);
        }
        // $applicant_id = $request->get('applicant_id');
        $session_id = $request->get('session_id');
        $olevel_id = $request->get('id');
            return DB::table('olevel_results')->updateOrInsert([
                "applicant_id"=> $request->user()?->id,           
                "session_id"=> $session_id,
                "exam_type_id"=> $request->get('exam_type_id'),
            ],[
                "applicant_id"=> $request->user()?->id,
                "exam_type_id"=> $request->get('exam_type_id'),
                "examination_number"=> $request->get('examination_number'),
                "subjects_grades"=> $subject_grades,
                "month"=> $request->get('month'),
                "year"=> $request->get('year'),
                "session_id"=> $session_id,
                "scratch_card"=>$request->get('scratch_card')??"",
                "serial_number"=>$request->get('serial_number')??"",
                "pin"=>$request->get('pin')??""
            ]);
        // throw new Exception("Applicant not found. Probably applicant not in current session",404);
    }

    public function insertOrUpdateDocument($id, $name, $url){
        return DB::table('documents')->updateOrInsert([
            "applicant_id"=> $id,           
            "name"=> $name,
        ],[
            "url"=> $url,
        ]);
    }   

    
    public function getDocuments($request){
        return DB::table('documents')->where(["owner_id"=> $request->user()?->id,'owner_type'=>"applicant"])->get();
    }   

    public function checkDocument($applicant_id, $name){
        return DB::table('documents')->where(["applicant_id"=> $applicant_id, "name"=>$name])->first();
    }   


    public function insertALevel( $alevel_data, $certificate_data ){
        try {
            DB::beginTransaction();
             $save = $this->saveToAlevel($alevel_data);
            DB::commit();
            return $save;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 500);
      }
    }

    public function  updateALevel($id, $alevel_data, $certificate_data){
        try {
            DB::beginTransaction();
                $save = $this->saveToAlevel($alevel_data, $id);
                DB::commit();
                return $save;
            } catch (\Exception $e) {
                DB::rollBack();
            throw new Exception($e->getMessage(), 500);
           }
    }

    private function saveToAlevel($other_data, $id="", $certificate_id = null, $applicant_qualification_id = null){
        if($id !== ""){

            return $this->alevel::where('id', $id)->update([
                "applicant_id"=>$other_data['applicant_id'],
                "institution_attended"=>$other_data['institution_attended'],
                "from"=>$other_data['from'],
                "to"=>$other_data['to'],
                "qualification_id"=> $other_data['qualification_id'],
                "class_of_certificate" => $other_data['class_of_certificate'],
                "certificate_id"=> $other_data['certificate_id'],
                "programme_studied" => $other_data['programme_studied'],
                "cgpa" => $other_data['cgpa'],
                "session_id"=>$other_data['session_id']
            ]);

        }else{

            return $this->alevel::insert([
                "applicant_id"=>$other_data['applicant_id'],
                "institution_attended"=>$other_data['institution_attended'],
                "from"=>$other_data['from'],
                "to"=>$other_data['to'],
                "qualification_id"=>$other_data['qualification_id'],
                "class_of_certificate" => $other_data['class_of_certificate'],
                "certificate_id" => $other_data['certificate_id'],
                "programme_studied" => $other_data['programme_studied'],
                "cgpa" => $other_data['cgpa'],
                "session_id"=>$other_data['session_id'],
            ]);

        }
    }

    private function saveToCertificate($applicant_id, $other_data){

            $applicantCertificate = $this->applicantCertificate::where([
                "applicant_id"  => $applicant_id,
                "certificate_type_id" => $other_data['certificate_type_id'],
            ])->first();
            if(is_null($applicantCertificate)){

                DB::table('applicants_certificates')->insert([
                    "applicant_id"  => $applicant_id,
                    "name" => $other_data['certificate_name'],
                    "url" => $other_data['url'],
                    "filename" => $other_data['filename'],
                    "certificate_type_id" => $other_data['certificate_type_id'],
                ]);
            }else{
                $this->applicantCertificate::where(["applicant_id"  => $applicant_id,"certificate_type_id" => $other_data['certificate_type_id']])
                                ->update([
                                    "name" => $other_data['certificate_name'],
                                    "url" =>$other_data['url'],
                                    "filename" =>$other_data['filename'],
                                ]);
            }

        $response = $this->applicantCertificate::where(["applicant_id"  => $applicant_id])->first();
        return $response->id;

    }

    private function saveToQualification($applicant_id, $qualification_id){

        $this->applicantQualification::updateOrInsert(
            ["applicant_id"  => $applicant_id, "qualification_id" =>$qualification_id],["qualification_id" =>$qualification_id,"applicant_id"  => $applicant_id,]
        );

        $response = $this->applicantQualification::where(["applicant_id"  => $applicant_id, "qualification_id" =>$qualification_id])->first();
        return $response->id;

    }

    public function filterInvoiceType($invoiceTypes, $property, $value){                
        $groupedInvoiceTypes = $this->group_by($invoiceTypes,"payment_short_name");
        $filteredInvoiceTypes = [];
        foreach($groupedInvoiceTypes as $key => $payment){
            $is_null = true;
            if(sizeof($payment) >1){     
                collect($payment)->each(function($item) use($property,$value,&$is_null){
                    if(!is_null($item[$property])){
                        $is_null = false;
                    }       
                });
                if($is_null){                                        
                    array_push($filteredInvoiceTypes,  ...$payment);
                }else{
                    array_push($filteredInvoiceTypes, ...collect($payment)->where($property,$value)->toArray());
                }                           
            }else{                  
                $filteredInvoiceTypes[] = $payment[0];
            }
        }
        return $filteredInvoiceTypes;
    }
    private function group_by($array, $key) {
        $return = [];
        
        foreach($array as $k => $val) {
            $return[$val[$key]][] = $val;         
        }        
        return $return;
    }

    public function  getPaymentDetails($session_id, $applicant = null){
        // $application_fee_id = $this->getPaymentId("application_fee");
        // $acceptance_fee_id = $this->getPaymentId("acceptance_fee");
        // $registration_fee_id = $this->getPaymentId("school_fee");   
        $semester_id = (int) DB::table('configurations')->where('name','current_semester')->first()?->value;          
        $applicant = auth('api:applicantsportal')->user() ?? $applicant;
       // $faculty = $this->faculty::find($applicant->department_id);       
        $query = [
            "gender" => $applicant->gender,
            "owner_type" => 'applicant',
            "programme_id" => $applicant->programme_id ?? $applicant->applied_programme_id,
            "programme_type_id" => $applicant->programme_type_id,
            "department_id" => $applicant->department_id,
            "faculty_id" => $applicant->faculty_id,
            "entry_mode_id" => $applicant->mode_of_entry_id,
            "state_id" => $applicant->state_id,
            "lga_id" => $applicant->lga_id,
            "level_id" => $applicant->level_id,
            "country_id" => $applicant->country_id,
            "session_id" => $applicant->session_id,
            "semester_id" =>$semester_id
        ];

        // if($application_fee_id == '')
        // {
        //     throw new \Exception('Application fee payment category does not exist, please contact system administrator');
        // }
        // if($acceptance_fee_id == '') {
        //     throw new \Exception('Acceptance fee payment category does not exist, please contact system administrator');
        // }
        // if($registration_fee_id == ''){
        //     throw new \Exception('Registration fee payment category does not exist, please contact system administrator');
        // }
        
        //Log::info($applicant);
        $invoiceTypes = $this->invoiceType::match($query)->where('status', 'Active')->latest()->get()->toArray();
        
        $invoiceTypes = $this->filterInvoiceType($invoiceTypes,"country_id",$applicant->country_id);
        $invoiceTypes = $this->filterInvoiceType($invoiceTypes,"state_id",$applicant->state_id);        
        $invoiceTypes = $this->filterInvoiceType($invoiceTypes,"lga_id",$applicant->lga_id);     
        
        //dd($invoiceTypes);
        if(empty($invoiceTypes)){
            throw new Exception("Sorry, no payment setup for you yet", 404);
        }
        
        $applicantInvoice = DB::table('invoices')
                                ->whereIn('invoice_type_id',array_column($invoiceTypes,'id'))
                                ->where(['owner_id'=> $applicant['id'], 'session_id'=>$session_id, "owner_type"=>"applicant"])
                                ->get();         
                                                
        foreach($invoiceTypes as $key => &$invoiceType){                                    
            if(count($applicantInvoice->where("status","paid")->where("invoice_type_id",$invoiceType['id']))>0){
                $invoiceType['status'] = 'paid';
            }else{
                $invoiceType['status'] = 'unpaid';                
            }
        }

        $response = $invoiceTypes;
        if(!is_null($response)){
            return $response;
        }

    }

    private function getPaymentId($short_name){
        return $this->paymentCategory::where('short_name',$short_name)->first()->id;
    }

    public function updateApplicantProgress($id,$progress){
        $applicant = $this->applicant::find($id);
        $applicant->application_progress = $progress;
        return $applicant->save();;
    }

    public function getLevelByOrder($order){
        return $this->level::where('order',$order)->first();
    }
 }
