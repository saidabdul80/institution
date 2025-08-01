<?php
namespace Modules\Staff\Repositories;

use App\Models\AdmissionBatch;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Database\Seeders\Students;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Applicant;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Models\InvoiceType;

use App\Repositories\InvoiceRepository;
use App\Services\Utilities;
use Exception;

class AdmissionRepository{

    private $student;
    private $applicant;
    private $payment;
    private $invoice;
    private $paymentCategory;
    private $invoiceType;
    private $admissionBatch;
    private $utilities;
    protected $invoiceRepository;
    public function __construct(Student $student,Applicant $applicant,Payment $payment,PaymentCategory $paymentCategory, Invoice $invoice, InvoiceType $invoiceType, AdmissionBatch $admissionBatch, Utilities $utilities, InvoiceRepository $invoiceRepository)
    {
        $this->student = $student;
        $this->applicant = $applicant;
        $this->payment = $payment;
        $this->invoice = $invoice;
        $this->invoiceType = $invoiceType;
        $this->paymentCategory = $paymentCategory;
        $this->admissionBatch = $admissionBatch;
        $this->utilities = $utilities;
        $this->invoiceRepository = $invoiceRepository;
         config()->set('database.connections.mysql.strict', false);
    }

    public function exists($id, $code){
        return $this->student::where('code', $code)->where('id','!=',$id)->first();

    }

    public function applicants($keyword,$paginateBy,$session_id, $filters)
    {
        
         /* if(is_null($filters)){
            return $this->applicant::with('alevel','olevel')->search($keyword)->where('session_id',$session_id)->latest()->paginate($paginateBy);
        }  */
        //return $this->applicant::filter($filters??[])->with('alevel','olevel')->search($keyword)->where('applicants.session_id',$session_id)->toSql();
        return $this->applicant::filter($filters??[])->with('alevel','olevel','invoices')->search($keyword)
                    ->where('session_id',$session_id)->latest()->paginate($paginateBy);
    }

    public function getStudents($keyword,$paginateBy, $session_id, $payment_name,$filters=[])
    {
        return $this->student::filter($filters)->with('alevel','olevel')->search($keyword)->latest()->paginate($paginateBy);
    }

    public function paidApplicants($session_id,$searchParam,$paginateBy,$payment_name, $status,$type='applicant' ){
        $applicant_ids = $this->getPaidApplicantsOrStudentsIDs($session_id,$payment_name,$type,$status, $searchParam );
        if(sizeof($applicant_ids)>0){
            return $this->applicant::with('alevel','olevel')->whereIn('id',$applicant_ids)->latest()->paginate($paginateBy);
        }else{
            throw new \Exception('No Applicant found', 404);
        }

    }

    public function getPaidApplicantsOrStudentsIDs($session_id,$payment_name, $type='applicant', $status='paid', $searchParam = []){
        try{
            $payment_category =  $this->paymentCategory::where('short_name', $payment_name)->first();
            $payment_category_id = $payment_category->id;
        }catch(\Exception $e){
            throw new \Exception('not payment type found', 404);
        }
        try{
            $searchParam['payment_category_id'] = $payment_category_id;
            $searchParam['owner_type'] = $type;
            $searchParam['session_id'] = $session_id;
            //fetching with get(), in case of late payment invoice type
            $invoiceTypes = $this->invoiceType::match($searchParam)->orderBy('id', 'desc')->get();
        }catch(\Exception $e){
            throw new \Exception('Invoice Type not found for '.$payment_category->name.'. Maybe you should check your filter', 404);
        }

        if(!empty($invoiceTypes))
        {
            $invoiceTypes_ids = array_column($invoiceTypes->toArray(), 'id');
            unset($searchParam['payment_category_id']);
            unset($searchParam['owner_type']);
            $searchParam['session_id'] = $session_id;
            $searchParam['status'] = $status;
            $invoices = $this->invoice->where($searchParam)->whereIn('invoice_type_id',$invoiceTypes_ids)->get();
            return array_column($invoices->toArray(), 'owner_id');
        }
        return [];
    }

    public function getSessionName($session_id){
        $session = DB::table('sessions')->where('id', $session_id)->first();
        return substr(explode('/',$session->name)[1],2);
    }

    public function getDepartmentName($id){
        $dept = DB::table('departments')->where('id', $id)->first();
        return $dept->abbr;
    }

    public function getFacultyName($id){
        $fac = DB::table('faculties')->where('id', $id)->first();
        return $fac->abbr;
    }

    public function getLastMatricNumber($matricName){
        $matric =  DB::table('students')
        ->selectRaw("MAX( CONVERT(SUBSTRING_INDEX(matric_number,'/',-1), int)) as lastNumber")
        ->whereRaw("matric_number like '$matricName%'")->first();
        if($matric->lastNumber !=''){
            return $matric->lastNumber+1;
        }else{
            return 1000;
        }
    }

    public function admitApplicant($applicant_ids, $level_id = null, $programme_id = null){
        $batch =  DB::table('configurations')->where('name','current_admission_batch')->first()->value;

        if(is_null($batch)){
            throw new \Exception('current batch not available in configuration');
        }

        $unpaidApplicantIds = [];        
        $applicants  = Applicant::whereIn('id',$applicant_ids)->get()->toArray();         

        $eligible_applicants = [];     
        foreach($applicants as $key => &$applicant){            
            if(!empty($programme_id)){
                $programme_id_x = $programme_id;
            }else{
                $programme_id_x = $applicant['applied_programme_id'] ;
            }

            if(!empty($level_id)){
                $level_id_x = $level_id;
            }else{
                $level_id_x = $applicant['applied_level_id'];
            }
            
            if($applicant['application_fee'] == 'Paid'){     
                $applicant['programme_id'] = $programme_id_x;                                           
                $applicant['admission_status'] = 'admitted';                                           
                $applicant['level_id'] = $level_id_x;                                           
                $applicant['qualified_status'] = 'qualified';    
                $applicant['batch_id'] = $batch;             
                $applicant['updated_at'] = now();             
                $applicants[$key] = $this->utilities->removeAllAccessors('applicants', $applicant);                
            }else{
                $unpaidApplicantIds[] = $applicant['application_number'];
            }

        }        
        
       /*  if(count($applicant_ids_to_be_admitted)){
            Applicant::whereIn('id',$applicant_ids_to_be_admitted)->update([                
                "admission_status" => 'admitted',
                "qualified_status" => 'qualified',
                "batch_id" => $batch,
                "session_id" => $session_id
            ]);
        } */
    
        DB::table('applicants')->upsert($applicants,['id'], ['programme_id','admission_status','level_id','qualified_status','batch_id']);

        if(count($unpaidApplicantIds)>0){
            return 'Admitted successfully, except for upaid applicants ('.implode(',',$unpaidApplicantIds).')';
        }
        return 'Admitted successfully';

    }

    public function admitBulkApplicant ($application_numbers,$programme_id=null, $level_id=null){               
        $session_id =  DB::table('configurations')->where('name','current_application_session')->first()?->value;
        $applicant_ids = DB::table('applicants')->whereIn('application_number',$application_numbers)->pluck('id');
        return $this->admitApplicant($applicant_ids,$session_id,'',$level_id,$programme_id);/* 
        $paid_applicant_ids = $this->getPaidApplicantsOrStudentsIDs($session_id,$payment_name);
        $filtered_id = array_filter($applicant_ids, function($id) use($paid_applicant_ids){
            if(in_array($id, $paid_applicant_ids)){
                return $id;
            }
        });      //filter out unpaid applicants

        $applicants = DB::table('applicants')->whereIn('id',$filtered_id)->where('admission_status','!=','admitted')->latest()->get();

        if(sizeof($applicants)>0){

            $applicants = $applicants->toArray();
            foreach($applicants as &$applicant){
                if($maintain_programme_id){
                    $applicant->programme_id = $applicant->applied_programme_id ;
                }else{
                    $applicant->programme_id = $programme_id;
                }

                $applicant->entry_session_id = $applicant->session_id;
                $applicant->application_id = $applicant->id;
                $applicant->batch = $current_batch;
                $applicant = (Array) $applicant;
            }

            return DB::transaction(function () use($applicants,$filtered_id){
                // $this->student::insert($applicants);
                $this->applicant::whereIn('id',$filtered_id)->update(['admission_status'=>'admitted', 'qualified_status'=>'qualified']);
                return $applicants;
            });
        }else{
            throw new \Exception('Cannot Admit, Applicant is yet to make payment or Applicant not found', 404);
        } */
    }

    public function rejectThisApplicants($ids, $session_id){
        //$this->student::whereIn('applicant_id', $ids)->delete();//remove from student table
        $this->applicant::whereIn('applicant_id', $ids)->update(['admission_status'=>'rejected','qualified_status'=>'not qualified']);

    }
    public function activateStudent($matric_number, $session_id){
        $student  = $this->student::where('matric_number', $matric_number)->first();
        $id = "";
        if($student){
            $id = $student->id;
        }

        $searchParam['p.owner_id'] = $id;
        $student_id = $this->getPaidApplicantsOrStudentsIDs($session_id,$searchParam,'school_fee', 'student');
        if($student_id == ''){
            $this->student::withTrashed()
            ->where('id', $student_id )
            ->restore();
        }else{
            throw new \Exception('Student has not paid School fee', 404);
        }


    }

    public function updateApplicantAdmissionStatus($applicant_ids, $status){
        return $this->applicant::whereIn('id', $applicant_ids)->update(['admission_status'=>$status]);
    }

    public function updateApplicantQualifiedStatus($applicant_ids,$status){
        return $this->applicant::whereIn('id', $applicant_ids)->update(['qualified_status'=>$status]);
    }

    public function admissionBatches($session_id){
        return Student::distinct()->where('session_id', $session_id)->get()->pluck('batch');
    }


    public function changeProgramme($applicant_ids, $programme_id,$faculty_id, $department_id){
        $areStudents = [];
        foreach($applicant_ids as $id){
            $applicant = $this->applicant::find($id);
            $check = $this->applicantExistAsStudent($id);
            if(!empty($check)){
                //skip applicants
                $areStudents[] =  [
                   "application_number" => $applicant->application_number,
                   "info" => "Applicant programme cannot be changed"
                ];
                continue;
            }

           /*  if(!$this->checkQualify($applicant, $programme_id)['is_qualify']){
                $areStudents[] =  [
                    "application_number" => $applicant->application_number,
                    "info" => "Applicant is not qualify for this programme"
                 ];
                //not qualify
                continue;
            } */

            $applicant->programme_id = $programme_id;
            $applicant->faculty_id = $faculty_id;
            $applicant->department_id = $department_id;
            $applicant->save();
        }
        return $areStudents;
    }

    public function changeAdmittedProgramme($applicant_id, $programme_id, $department_id, $faculty_id, $programme_type_id, $level_id){
        $applicant = $this->applicant::find($applicant_id);
        $check = $this->applicantExistAsStudent($applicant_id);
        if (!empty($check)) {
            throw new \Exception("Applicant programme cannot be changed", 400);
        }

        /* if (!$this->checkQualify($applicant, $programme_id)['is_qualify']) {
            throw new \Exception("Applicant is not qualify for this programme",400);
        } */
        $applicant->programme_id = $programme_id;
        $applicant->faculty_id = $faculty_id;
        $applicant->department_id = $department_id;
        $applicant->programme_type_id = $programme_type_id;
        $applicant->level_id = $level_id;
        $applicant->save();
        return "Applicant programme changed successfully";
    }

    private function checkQualify($applicant,$programme_id) {
        $olevelResuts = DB::table('olevel_results')->where('applicant_id',$applicant->id)->pluck('subjects_grades');
        $subjects = explode(',', DB::table('programmes')->where('id', $programme_id)->first()->required_subjects);
        $grades = explode(',', DB::table('programmes')->where('id', $programme_id)->first()->accepted_grades);
        $pass = 0;
        $checkSubject = [];
        $checkSubject2 = [];

        if(empty($subjects)){
            return ["is_qualify"=>true, "info" => 'no required subjects set'];
        }

        foreach($olevelResuts as $key => $olevelResut){
            //first and second result
            $oResult = (Array) json_decode($olevelResut);
            foreach($subjects as $subject){
                //the required subjects
                if(isset($oResult[$subject])){
                    //applicant has the required subjects
                    if(in_array($oResult[$subject],$grades)){
                        //applicant pass the required subjects
                        $key==0? $checkSubject[$subject] = 1: $checkSubject2[$subject] = 1;
                    }else{
                        $key==0? $checkSubject[$subject] = 0: $checkSubject2[$subject] = 0;
                    }
                }else{
                    $key==0? $checkSubject[$subject] = 0: $checkSubject2[$subject] = 0;
                }
            }
        }

        $first  = array_sum(array_values($checkSubject));
        $second  = array_sum(array_values($checkSubject2));
        if($first == count($subjects)){
            return ["is_qualify"=>true, "info" => 'One Result'];
        }else if($first + $second >= count($subjects)){
            return ["is_qualify"=>true, "info" => 'Two Result'];
        }
        return ["is_qualify"=>false, "info" => ''];
    }
    private function applicantExistAsStudent($id){
        return $this->student::where('application_id', $id)->first();
    }

    private function getTableById($tablename,$id){
        return DB::table($tablename)->where('id', $id)->first();
    }

    private function newInvoice($applicant, $new_programme_id, $session_id, $semester_id, $type){

        $payment_category_id = $this->paymentCategory::where('short_name','registration_fee')->first()->id;
        $query = [
            "gender" => $applicant->gender,
            "level_id" => $applicant->applied_level_id,
            "programme_id" => $new_programme_id,
            "department_id" => $applicant->department_id,
            "faculty_id" => $applicant->faculty_id,
            "entry_mode_id" => $applicant->mode_of_entry_id,
            "state_id" => $applicant->state_id,
            "lga_id" => $applicant->lga_id,
            "country_id" => $applicant->country_id,
            "session_id" => $session_id,
            "semester_id"=> $semester_id,
            "owner_type" =>"applicant",
            'payment_category_id'=>$payment_category_id
        ];
        $invoice_type =  $this->invoiceType::match($query)->first();

        $invoiceDetails = [
            "owner_id" => $applicant->id,
            "owner_type" => 'applicant',
            "session_id" => $session_id,
            "invoice_number"=>generateInvoiceNumber(),
            "amount"=> $invoice_type->amount,
            //"payment_category_slug" => 'registration_fee',
            "payment_category_id" => $payment_category_id,
            "invoice_type_id" => $invoice_type->id
        ];


        $invoices = $this->invoice::where(['owner_id'=> $applicant->id, 'owner_type'=>'applicant'])->get()->toArray();
        $registration_invoice = array_filter($invoices,function($item){
            return $item['payment_category'] == 'registration_fee';
        });

        if(sizeof($registration_invoice)>0){
            //$applicantInvoice = $registration_invoice[0];
            /* if($applicantInvoice['invoice_type_id'] != $invoice_type->id){
                //if invoice not the same
                //soft delete old
                //$this->invoice::where('id', $applicantInvoice['id'])->delete();
                //create new invoice
            } */
            $this->invoiceRepository->createInvoice($invoiceDetails);
            $this->student::where(['application_id'=>$applicant->id])->delete();
        }else{
            $this->invoice::create($invoiceDetails);
        }

        return true;
    }

    private function getStudent($id){
        return $this->student::withTrashed()->find($id);
    }
    public function deleteBatch($model,$id){
        return $model::find($id)->delete();
    }

    public function allBatches(){
        return $this->admissionBatch::all();
    }
}
