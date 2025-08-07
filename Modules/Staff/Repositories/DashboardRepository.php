<?php

namespace Modules\Staff\Repositories;


use Illuminate\Support\Facades\Http;
use Database\Seeders\Courses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Applicant;
use App\Models\EntryMode;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\ProgrammeType;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Wallet;
use App\Models\WalletSettlement;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository
{

    private $student;
    private $staff;
    private $applicant;
    private $invoiceType;
    private $programmeType;
    private $invoice;
    private $programme;
    private $level;
    public function __construct(Student $student, Staff $staff, Applicant $applicant, InvoiceType $invoiceType, ProgrammeType $programmeType, Invoice $invoice, Programme $programme,Level $level)
    {
        $this->student = $student;
        $this->staff = $staff;
        $this->applicant = $applicant;
        $this->invoiceType = $invoiceType;
        $this->programmeType = $programmeType;
        $this->invoice = $invoice;
        config()->set('database.connections.mysql.strict', false);
        $this->programme = $programme;
        $this->level = $level;
    }

    public function getAdmittedStateCount($session_id)
    {
        $stateOfOrigin_state_id = DB::table('configurations')->where('name', 'school_state_of_origin')->first();
        if ($stateOfOrigin_state_id) {
            return DB::table('applicants')->where(['state_id' => $stateOfOrigin_state_id->value, 'session_id' => $session_id])->count();
        }
        return 0;
    }

    public function getQualificationStatusCount($session_id,$status)
    {
        return DB::table('applicants')->where(['qualified_status' => $status, 'session_id' => $session_id])->count();
    }

    public function getTotalApplicants($session_id)
    {
        return DB::table('applicants')->where('session_id', $session_id)->count();
    }

    public function getTotalStudents($session_id)
    {
        return DB::table('applicants')->where(['admission_status' => 'admitted', 'session_id' => $session_id])->count();
    }

    public function getTotalCourse()
    {
        return DB::table('courses')->count();
    }

    public function getTotalDepartment()
    {
        return DB::table('departments')->count();
    }

    public function getEnumValues($tablename, $column)
    {
        // Create an instance of the model to be able to get the table name

        // Pulls column string from DB
        $enumStr = DB::select(DB::raw("SHOW COLUMNS FROM $tablename  WHERE Field = '$column' "))[0]->Type;

        //Parse string
        preg_match_all("/'([^']+)'/", $enumStr, $matches);

        //Return matches
        return isset($matches[1]) ? $matches[1] : [];
    }

    public function getApplicantReligionCount($session_id)
    {
        $enum  = $this->getEnumValues('applicants', 'religion');

        $groups = DB::table('applicants')->where(['admission_status' => 'admitted', 'session_id' => $session_id])->where('religion', '!=', null)->get()->groupBy('religion');
        $religions = [];

        foreach ($enum as $value) {
            $religions[strtolower($value)] = count($groups[$value] ?? []);
        }
        return $religions;
    }

    public function getTotalActiveStaff()
    {
        return $this->staff->count();
    }

    public function totalNonActiveStaff()
    {
        return $this->staff->onlyTrashed()->count();
    }

    public function getTotalNonActiveStudent()
    {
        return $this->student->whereNotNull('deleted_at')->count();
    }

    public function getTotalActiveStudent()
    {
        return $this->staff->count();
    }

    public function getTotalNonActiveApplicant()
    {
        return $this->applicant->onlyTrashed()->count();
    }

    public function getTotalActiveApplicant()
    {
        return $this->applicant->count();
    }

    private function changeArrayKey($array)
    {
        foreach ($array as $key => $value) {
            if ($key == "") {
                $array["unknown_category"] = sizeof($array[$key]);
                unset($array[$key]);
            }
        }
        return $array;
    }

    public function getApplicantReport($session_id)
    {

        $data = $this->applicant::where('session_id', $session_id)->get();
        $levels = DB::table('levels')->get();
        $religions = ['Islam', 'Christian', 'Other'];
        $entry_modes = DB::table('entry_modes')->pluck('code');
        $genders = ['Male', 'Female'];

        $dataGrouped = [
            'gender' => [],
            'level' => [],
            'religion' => [],
            'entry_mode' => [],
        ];

        $level  = $this->changeArrayKey($data->groupBy('level')->toArray());
        $religion = $this->changeArrayKey($data->groupBy('religion')->toArray());
        $entry_mode = $this->changeArrayKey($data->groupBy('entry_mode')->toArray());
        $gender = $this->changeArrayKey($data->groupBy('gender')->toArray());

        foreach ($levels as $key => $value) {
            if (sizeof($level) > 0) {
                if (!array_key_exists($value->title, (array) $level)) {
                    $level[$value->title] = 0;
                } else {
                    $level[$value->title] = sizeof($level[$value->title]);
                }
            } else {
                $level[$value->title] = 0;
            }
        }

        /*  $religion["unknown_category"] = sizeof($religion[""]);
        unset($religion[""]); */
        foreach ($religions as $value) {
            if (!array_key_exists($value, (array) $religion)) {
                $religion[$value] = 0;
            } else {
                $religion[$value] = sizeof($religion[$value]);
            }
        }


        /*   $entry_mode["unknown_category"] = sizeof($entry_mode[""]);
        unset($entry_mode[""]); */
        foreach ($entry_modes as $value) {
            if (!array_key_exists($value, (array) $entry_mode)) {
                $entry_mode[$value] = 0;
            } else {
                $entry_mode[$value] = sizeof($entry_mode[$value]);
            }
        }



        foreach ($genders as $value) {
            if (!array_key_exists($value, (array) $gender)) {
                $gender[$value] = 0;
            } else {
                $gender[$value] = sizeof($gender[$value]);
            }
        }
        /*  $gender["unknown_category"] = sizeof($gender[""]);
        unset($gender[""]); */




        $dataGrouped = [
            'gender' => $gender,
            'level' => $level,
            'religion' => $religion,
            'entry_mode' => $entry_mode,
        ];
        return $dataGrouped;
    }

    public function getStudentReport($session_id)
    {
        return DB::table('students', 'a')
            ->join('levels', 'levels.id', 'a.applied_level_id')
            ->join('entry_modes', 'entry_modes.id', 'a.mode_of_entry_id')
            ->groupBy('gender', 'religion', 'levels.title', 'entry_modes.title')
            ->where('a.entry_session_id', $session_id)->get();
    }

    public function getFinanceReport($session_id)
    {

        $payment_categories = DB::table('payment_categories')->get();
        $invoice_types = array();
        foreach ($payment_categories as $payment_category) {
            $invoice_type = DB::select(DB::raw("select id, name from invoice_types where (session_id=$session_id or session_id is null) and payment_category_id = $payment_category->id order by id desc Limit 1"));
            if ($invoice_type) {
                $invoice_type = (array) $invoice_type[0];
                $invoice_type['payment_category'] = $payment_category->name;
                $invoice_types[] = $invoice_type;
            }
        }

        $payments = array();
        //return $invoice_types;
        foreach ($invoice_types as $invoice_type) {
            $invoice_type_id = $invoice_type['id'];
            $payment = DB::select(DB::raw("select sum(amount) as total from invoices WHERE status='paid' AND invoice_type_id = $invoice_type_id AND session_id = $session_id"));

            $payments[] = [
                "total" => $payment[0]->total ?? 0,
                "name" => $invoice_type['name'],
                "payment_category" => $invoice_type['payment_category']
            ];
        }
        return $payments;
    }

    private function getPaymentCategoryId($payment_name)
    {
        $data = DB::table('payment_categories')->where('short_name', $payment_name)->first();
        if (!empty($data)) {
            return $data->id;
        }
        throw new \Exception('Payment name not found', 404);
    }

    private function getInvoiceTypeId($payment_category_id, $type)
    {
        $data = DB::table('invoice_types')->where(['payment_category_id' => $payment_category_id, 'type' => $type])->first();
        if ($data) {
            return $data->id;
        }
        return "";
    }

    private function getInvoiceId($invoice_type_id)
    {
        $data = DB::table('invoices')->where('invoice_type_id', $invoice_type_id)->first();
        if ($data) {
            return $data->id;
        }
        return "";
    }

    public function countInvoiceByInvoiceTypes($invoice_type_ids,$by_session='yes', $session_id=null, $from = null, $to=null){
        if($by_session=='yes'){
           return  DB::select(DB::raw("select count(id) as total from invoices WHERE status='paid' AND invoice_type_id IN ($invoice_type_ids) AND session_id = $session_id AND session_id = $session_id"));
        }
        return DB::select(DB::raw("select count(id) as total from invoices WHERE status='paid' AND invoice_type_id IN  ($invoice_type_ids) AND SUBSTRING_INDEX(  paid_at ,' ',1)>= '$from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$to'"));
    }

    public function totalPaidInvoiceByInvoiceTypes($invoice_type_ids,$by_session='yes', $session_id = null,$from = null, $to=null){
        if($by_session=='yes'){
           return  DB::select(DB::raw("select sum(amount) as total from invoices WHERE status='paid' AND invoice_type_id IN ($invoice_type_ids) AND session_id = $session_id"));
        }
        return DB::select(DB::raw("select sum(amount) as total from invoices WHERE status='paid' AND invoice_type_id IN ($invoice_type_ids) AND SUBSTRING_INDEX(  paid_at ,' ',1)>= '$from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$to'"));
    }

    public function paymentReport($session_id, $byDate_from, $byDate_to, $use_session)
    {
       $payment_categories = DB::table('payment_categories')->get();
       /*
         foreach ($paymentCategories as &$paymentCategory) {
            $invoice_types_records = DB::table('invoice_types')->where('payment_category_id', $paymentCategory->id)->where(function ($query) use ($session_id) {
                $query->where('session_id', $session_id)->orWhere('session_id', null);
            })->get();
            $invoice_types = $invoice_types_records->groupBy('owner_type')->map(function($item){
                return $item->pluck('id');
            })->toArray();
            $applicant_invoice_type_ids ='';
            $student_invoice_type_ids ='';
            $student_count = 0;
            $student_total_paid = 0;
            $applicant_count = 0;
            $applicant_total_paid = 0;
            if(array_key_exists('applicant', $invoice_types)){
                $applicant_invoice_type_ids = implode(',', $invoice_types['applicant']);
                //$invoice_types['applicant'] = [];
            }
            if(array_key_exists('student', $invoice_types)){
                //$invoice_types['student'] = [];
                $student_invoice_type_ids = implode(',', $invoice_types['student']);
            }

            if ($use_session) {
                if($student_invoice_type_ids != ''){
                    $student_count = $this->countInvoiceByInvoiceTypes($student_invoice_type_ids,'yes',$session_id)[0]?->total;
                    $student_total_paid = $this->totalPaidInvoiceByInvoiceTypes($student_invoice_type_ids,'yes', $session_id)[0]?->total;
                }
                if($applicant_invoice_type_ids != ''){
                    $applicant_count = $this->countInvoiceByInvoiceTypes($applicant_invoice_type_ids,'yes', $session_id)[0]?->total;
                    $applicant_total_paid = $this->totalPaidInvoiceByInvoiceTypes($applicant_invoice_type_ids,'yes' ,$session_id)[0]?->total;
                }

            } else {
                if($student_invoice_type_ids != ''){
                    $student_count = $this->countInvoiceByInvoiceTypes($student_invoice_type_ids,'no',$session_id,$byDate_from, $byDate_to)[0]?->total;
                    $student_total_paid = $this->totalPaidInvoiceByInvoiceTypes($student_invoice_type_ids,'no', $session_id,$byDate_from, $byDate_to)[0]?->total;
                }
                if($applicant_invoice_type_ids != ''){
                    $applicant_count = $this->countInvoiceByInvoiceTypes($applicant_invoice_type_ids,'no', $session_id,$byDate_from, $byDate_to)[0]?->total;
                    $applicant_total_paid = $this->totalPaidInvoiceByInvoiceTypes($applicant_invoice_type_ids,'no' ,$session_id,$byDate_from, $byDate_to)[0]?->total;
                }
            }

            $paymentCategory->invoice_types = $invoice_types_records;
            $paymentCategory->student_count = $student_count;
            $paymentCategory->student_total_paid = $student_total_paid;
            $paymentCategory->applicant_count = $applicant_count;
            $paymentCategory->applicant_total_paid = $applicant_total_paid;
        } */
        if($use_session){
            $paymentCategories = DB::table('payment_categories as p')
            ->select('p.short_name', DB::raw("
            SUM(CASE WHEN t.owner_type = 'applicant' THEN 1 ELSE 0 END) AS applicant_count,
            SUM(CASE WHEN t.owner_type = 'applicant' THEN i.amount ELSE 0 END) AS applicant_total_paid,
            SUM(CASE WHEN t.owner_type = 'student' THEN 1 ELSE 0 END) AS student_count,
            SUM(CASE WHEN t.owner_type = 'student' THEN i.amount ELSE 0 END) AS student_total_paid
            "))
            ->leftJoin('invoice_types as t', 'p.id', '=', 't.payment_category_id')
            ->leftJoin('invoices as i', 't.id', '=', 'i.invoice_type_id')
            //->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where(['t.session_id'=> $session_id,'i.status'=>'paid'])
            ->groupBy('p.short_name')
            ->get();
        }else{
            $from = Carbon::parse($byDate_from)->format('Y-m-d');
            $to = Carbon::parse($byDate_to)->format('Y-m-d');
            $paymentCategories = DB::table('payment_categories as p')
            ->select('p.short_name', DB::raw("
            SUM(CASE WHEN t.owner_type = 'applicant' THEN 1 ELSE 0 END) AS applicant_count,
            SUM(CASE WHEN t.owner_type = 'applicant' THEN i.amount ELSE 0 END) AS applicant_total_paid,
            SUM(CASE WHEN t.owner_type = 'student' THEN 1 ELSE 0 END) AS student_count,
            SUM(CASE WHEN t.owner_type = 'student' THEN i.amount ELSE 0 END) AS student_total_paid
            "))
            ->leftJoin('invoice_types as t', 'p.id', '=', 't.payment_category_id')
            ->leftJoin('invoices as i', 't.id', '=', 'i.invoice_type_id')
            ->where('i.status','paid')
            ->whereBetween('i.paid_at',[$from, $to])
            ->groupBy('p.short_name')
            ->get();
        }
        $payment_categories->map(function($item) use($paymentCategories){
            $x = $paymentCategories->where('short_name',$item->short_name)->first();
            if($x){
                $item->applicant_count = $x->applicant_count;
                $item->applicant_total_paid = $x->applicant_total_paid;
                $item->student_count = $x->student_count;
                $item->student_total_paid = $x->student_total_paid;
            }else{
                $item->applicant_count = 0;
                $item->applicant_total_paid = 0;
                $item->student_count = 0;
                $item->student_total_paid = 0;
            }
        });
        return $payment_categories;
    }

    public function totalPaidCount($session_id, $byDate_from, $byDate_to, $use_session)
    {
        $paymentCategories = DB::table('payment_categories')->get();
        foreach ($paymentCategories as &$paymentCategory) {
            $invoice_types = DB::table('invoice_types')->where('payment_category_id', $paymentCategory->id)->where(function ($query) use ($session_id) {
                $query->where('session_id', $session_id)->orWhere('session_id', null);
            })->get();

            if (count($invoice_types) > 0) {

                $invoice_type_ids = "(";
                foreach ($invoice_types as $invoice_type) {
                    $invoice_type_ids .= $invoice_type->id . ',';
                }
                $invoice_type_ids = rtrim($invoice_type_ids, ',');
                $invoice_type_ids .= ")";
                if ($use_session) {
                } else {
                    $totalPaid = DB::select(DB::raw("select count(id) as total from invoices WHERE status='paid' AND invoice_type_id IN $invoice_type_ids AND session_id = $session_id"));
                    $totalPaid = DB::select(DB::raw("select count(id) as total from invoices WHERE status='paid' AND invoice_type_id IN $invoice_type_ids AND SUBSTRING_INDEX(  paid_at ,' ',1)>= '$byDate_from' AND SUBSTRING_INDEX(  paid_at ,' ',1) <= '$byDate_to'"));
                }
                $paymentCategory->total_paid = $totalPaid[0]->total ?? 0;
                $paymentCategory->invoice_types = $invoice_types;
            } else {
                $paymentCategory->total_paid = 0;
                $paymentCategory->invoice_types = [];
            }
        }

        return $paymentCategories;
    }

    public function hostelReport($session_id, $byDate_from, $byDate_to, $use_session)
    {

        if ($use_session) {
            $allocated = DB::select(DB::raw("select count(id) as total from allocations WHERE status='active' AND session_id = $session_id"));
        } else {
            $allocated = DB::select(DB::raw("select count(id) as total from allocations WHERE status='active' AND SUBSTRING_INDEX(  created_at ,' ',1)>= '$byDate_from' AND SUBSTRING_INDEX(  created_at ,' ',1) <= '$byDate_to'"));
        }
        $allocations = DB::select(DB::raw("select sum(bedspace) as total from rooms WHERE status='active'"));

        $left = $allocations[0]->total - $allocated[0]->total;
        if ($allocations[0]->total > 0) {
            $per_allocated = ($allocated[0]->total / $allocations[0]->total) * 100;
            $per_left = ($left / $allocations[0]->total) * 100;
        } else {
            $per_allocated = 0;
            $per_left = 0;
        }

        return [
            'allocated' => $allocated[0]->total ?? 0,
            'unallocated' => $left,
            'total_space' => $allocations[0]->total ?? 0,
            'percentage_allocated' => $per_allocated,
            'percentage_unallocated' => $per_left
        ];
    }

    public function applicantReport($session_id, $byDate_from = '', $byDate_to = '', $use_session = '')
    {
        $applicant =  $this->applicant->where('session_id', $session_id)->get();

        $male = $applicant->where('gender', 'male')->count();
        $female = $applicant->where('gender', 'female')->count();
        $other = $applicant->where('gender', 'other')->count();
        $completed_application = $applicant->where(["final_submission" => 1])->count();
        $stateOfOrigin_state_id = DB::table('configurations')->where('name', 'school_state_of_origin')->first();
        if (!empty($stateOfOrigin_state_id)) {
            $total_indigene = $applicant->where('state_id', $stateOfOrigin_state_id->value)->count();
        }

        return [
            'total' => $applicant->count(),
            'gender' => [
                'male' => $male,
                'female' => $female,
                'other' => $other
            ],
            'total_indigene' => $total_indigene,
            'total_non_indigene' => $applicant->count() - $total_indigene,
            'total_completed_application' => $completed_application,
            'total_non_completed_application' => $applicant->count() - $completed_application,
        ];
    }

    public function studentReport($session_id, $byDate_from = '', $byDate_to = '', $use_session = '')
    {
        // $student =  $this->student->join('student_enrollments', 'student_enrollments.owner_id', 'students.id')->where(["student_enrollments.owner_type" => "student", "student_enrollments.session_id" => $session_id])->get();
        $student =  $this->student->where('status','active')->get('gender');
        $male = $student->where('gender', 'male')->count();
        $female = $student->where('gender', 'female')->count();
        $other = $student->where('gender', 'other')->count();
        $stateOfOrigin_state_id = DB::table('configurations')->where('name', 'school_state_of_origin')->first();
        if (!empty($stateOfOrigin_state_id)) {
            $total_indigene = $student->where('state_id', $stateOfOrigin_state_id->value)->count();
        }
        return [
            'total' => $student->count(),
            'gender' => [
                'male' => $male,
                'female' => $female,
                'other' => $other
            ],
            'total_indigene' => $total_indigene,
            'total_non_indigene' => $student->count() - $total_indigene,
        ];
    }

    public function admissionReport($session_id, $byDate_from, $byDate_to, $use_session)
    {
        $admitted = $this->applicant->where(['session_id' => $session_id, 'admission_status' => 'admitted'])->count();
        $not_admitted = $this->applicant->where(['session_id' => $session_id, 'admission_status' => 'not admitted'])->count();
        return [
            'admitted' => $admitted[0]->total ?? 0,
            'not_admitted' => $not_admitted[0]->total ?? 0,
        ];
    }


    public function programmeTypeFeeReport($payment_short_name, $session_id)
    {
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        $response = DB::table('invoices as i')
            ->join('invoice_types as t', 't.id','=','i.invoice_type_id')
            ->join('programme_types as p','p.id','=','programme_type_id')
            ->selectRaw('p.short_name, p.name, SUM(i.amount) as total_paid')
            ->where(["i.session_id" => $session_id, "payment_category_id" => $payment_category_id,'i.status'=>'paid'])
            ->groupBy('p.short_name','p.name')->get();
        return $response;
    }

    public function invoicesByPaymentName($payment_name,$filters, $session_id, $paginateBy = null)
    {
        $paginate = $paginateBy ?? 100;
        $filters = $filters??[];
        $payment_category_id = $this->getPaymentCategoryId($payment_name);
        $ids = $this->invoiceType::where(["payment_category_id" => $payment_category_id, "session_id" => $session_id])->pluck('id');
        return $this->invoice::filter($filters)->whereIn('invoice_type_id', $ids)->where('session_id', $session_id)->latest()->paginate($paginate);
    }

    public function sessionsFeeReport($session_ids, $payment_name)
    {
        $payment_category_id = $this->getPaymentCategoryId($payment_name);
        $invoices_type_ids = implode(',', $this->invoiceType::where(["payment_category_id" => $payment_category_id])->whereIn('session_id', $session_ids)->pluck('id'));
        return DB::select(DB::raw("SELECT s.name, sum(i.amount) total_paid FROM invoices AS i JOIN sessions AS s ON s.id = i.session_id WHERE status ='paid' AND invoice_type_id IN ($invoices_type_ids) GROUP BY s.name "));
    }

    public function programmeTypesReport($session_id)
    {
        $programme_types = $this->programmeType::all();
        $applicants = DB::table('applicants')->where('session_id', $session_id)->get();
        foreach ($programme_types as $key => &$programme_type) {
            $programme_type->total_qualified = $applicants->where('programme_type_id', $programme_type->id)->where('qualified_status', 'qualified')->count();
            $programme_type->total_not_qualified = $applicants->where('programme_type_id', $programme_type->id)->where('qualified_status', 'not qualified')->count();;
        }
        return $programme_types;
    }

    public function recentLogins($model_name, $take = null)
    {

        $take = $take ?? 5;
        if ($model_name == 'applicant') {
            return $this->applicant::orderBy('logged_in_time', 'desc')->take($take)->get();
        } else if ($model_name == 'student') {
            return $this->student::orderBy('logged_in_time', 'desc')->take($take)->get();
        } else if ($model_name == 'staff') {
            return $this->staff::orderBy('logged_in_time', 'desc')->take($take)->get();
        } else {
            throw new Exception('Invalid model name');
        }
    }

    public function admissionReportAdmittedByProgrammeType($session_id)
    {
        $programmeTypes= $this->programmeType::all();
        foreach($programmeTypes as $key => &$programmeType){
            $pid = $programmeType->id;
            $batch_array = DB::select(DB::raw("SELECT b.name batch, count(a.id) total FROM applicants as a JOIN `admission_batches` b ON b.id = a.batch_id WHERE a.programme_type_id=$pid AND a.session_id=$session_id  AND a.admission_status = 'admitted' GROUP BY b.name"));
            $r = DB::select(DB::raw("SELECT sum(if(a.admission_status = 'admitted',1,0)) total_admitted, sum(if(a.admission_status = 'not admitted',1,0)) total_not_admitted FROM applicants as a WHERE  a.programme_type_id = $pid AND a.session_id=$session_id"));
            $programmeType->total_admitted = $r[0]->total_admitted;
            $programmeType->total_not_admitted = $r[0]->total_not_admitted;
            $programmeType->batches = $batch_array;
        }
        return $programmeTypes;
    }

    public function admissionReportAdmittedByProgramme($session_id)
    {
        $programmes= DB::table('programmes')->get();
        foreach($programmes as $key => &$programme){
            $pid = $programme->id;
            $batch_array = DB::select(DB::raw("SELECT b.name batch, count(a.id) total FROM applicants as a JOIN `admission_batches` b ON b.id = a.batch_id WHERE a.programme_id=$pid AND a.session_id=$session_id  AND a.admission_status = 'admitted' GROUP BY b.name"));
            $r = DB::select(DB::raw("SELECT sum(if(a.admission_status = 'admitted',1,0)) total_admitted, sum(if(a.admission_status = 'not admitted',1,0)) total_not_admitted FROM applicants as a WHERE  a.programme_id = $pid AND a.session_id=$session_id"));
            $programme->total_admitted = $r[0]->total_admitted;
            $programme->total_not_admitted = $r[0]->total_not_admitted;
            $programme->batches = $batch_array;
        }
        return $programmes;
    }

    public function qualificationReportQualifiedByProgrammeType($session_id)
    {
        return DB::select(DB::raw("SELECT p.name,p.code, sum(if(a.qualified_status = 'qualified',1,0)) total_qualified, sum(if(a.qualified_status = 'not qualified',1,0)) total_not_qualified FROM applicants as a JOIN programme_types as p ON p.id = a.programme_type_id WHERE a.session_id=$session_id GROUP BY p.name"));
    }

    public function qualificationReportQualifiedByProgramme($session_id)
    {
        return DB::select(DB::raw("SELECT p.name, sum(if(a.qualified_status = 'qualified',1,0)) total_qualified, sum(if(a.qualified_status = 'not qualified',1,0)) total_not_qualified FROM applicants as a JOIN programmes as p ON p.id = a.programme_id WHERE a.session_id=$session_id GROUP BY p.name"));
    }

    public function totalPaidAndUnpaidByProgrammeType($payment_short_name, $session_id)
    {
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        $programme_types = $this->programmeType::all();
        $applicant_invoices_x = DB::table('invoices')->where(["session_id"=>$session_id, 'owner_type'=> 'student'])->get();
        $student_invoices_x = DB::table('invoices')->where(["session_id"=>$session_id, 'owner_type'=> 'applicant'])->get();

        foreach ($programme_types as $key => &$programme_type) {

            $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id, "programme_type_id" => $programme_type->id]);

            $invoices_type_ids = $invoices_types->pluck('id');
            if (count($invoices_type_ids) == 0) {
                $programme_type->total_students = 0;
                $programme_type->total_paid_student = 0;
                $programme_type->total_unpaid_student = 0;
                $programme_type->total_applicants = 0;
                $programme_type->total_paid_applicant = 0;
                $programme_type->total_unpaid_applicant = 0;
            } else {

                $applicant_invoices = $applicant_invoices_x->whereIn("invoice_type_id", $invoices_type_ids);
                $student_invoices = $student_invoices_x->whereIn("invoice_type_id", $invoices_type_ids);

                $total_student = $this->student::where(['programme_type_id' => $programme_type->id, 'status' => 'Active'])->count();
                $total_applicant = $this->applicant::where(['programme_type_id' => $programme_type->id, "session_id" => $session_id])->count();

                $programme_type->total_students = $total_student;
                $programme_type->total_paid_student = $student_invoices->where('status', 'paid')->where('owner_type', 'student')->count();
                $programme_type->total_unpaid_student = $total_student - $programme_type->total_paid_student;
                $programme_type->total_applicants = $total_applicant;
                $programme_type->total_paid_applicant = $applicant_invoices->where('status','paid')->where('owner_type', 'applicant')->count();
                $programme_type->total_unpaid_applicant = $total_applicant - $programme_type->total_paid_applicant;
            }
        }

        /* $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id])->whereNull("programme_type_id");
        $invoices_type_ids = $invoices_types->pluck('id');

        $total_applicant = $this->applicant::where(["session_id" => $session_id])->count();
        $total_student = $this->student::where(['status' => 'Active'])->count();


        $total_paid_student = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where(['owner_type' => 'student', 'status' => 'paid'])->count();
        $total_paid_applicant = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where(['owner_type' => 'applicant', 'status' => 'paid'])->count();

        $programme_types[] = [
            "name" => "Invoice Type without Programme Type Specification",
            "total_students" => $total_student,
            "total_paid_student" => $total_paid_student,
            "total_unpaid_student" => $total_student - $total_paid_student,
            "total_applicants" => $total_applicant,
            "total_paid_applicant" => $total_paid_applicant,
            "total_unpaid_applicant" => $total_applicant - $total_paid_applicant,
        ]; */

        return $programme_types;
    }

    public function totalPaidAndUnpaidByProgramme($payment_short_name, $session_id)
    {
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        $programmes = DB::table('programmes')->select('id','name','code')->get();

        foreach ($programmes as $key => &$programme) {

            $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id, "programme_id" => $programme->id]);

            $invoices_type_ids = $invoices_types->pluck('id');
            if (count($invoices_type_ids) == 0) {
                $programme->total_students = 0;
                $programme->total_paid_student = 0;
                $programme->total_unpaid_student = 0;
                $programme->total_applicants = 0;
                $programme->total_paid_applicant = 0;
                $programme->total_unpaid_applicant = 0;
            } else {

                $applicant_invoices = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where('owner_type', 'student');
                $student_invoices = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where('owner_type', 'applicant');

                $total_student = $this->student::where(['programme_id' => $programme->id, 'status' => 'Active'])->count();
                $total_applicant = $this->applicant::where(['programme_id' => $programme->id, "session_id" => $session_id])->count();

                $programme->total_students = $total_student;
                $programme->total_paid_student = $student_invoices->where(['status' => 'paid', 'owner_type' => 'student'])->count();
                $programme->total_unpaid_student = $total_student - $programme->total_paid_student;
                $programme->total_applicants = $total_applicant;
                $programme->total_paid_applicant = $applicant_invoices->where(['status' => 'paid', 'owner_type' => 'applicant'])->count();
                $programme->total_unpaid_applicant = $total_applicant - $programme->total_paid_applicant;
            }
        }

        $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id])->whereNull("programme_id");
        $invoices_type_ids = $invoices_types->pluck('id');

        $total_applicant = $this->applicant::where(["session_id" => $session_id])->count();
        $total_student = $this->student::where(['status' => 'Active'])->count();


        $total_paid_student = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where(['owner_type' => 'student', 'status' => 'paid'])->count();
        $total_paid_applicant = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where(['owner_type' => 'applicant', 'status' => 'paid'])->count();

        $programmes[] = [
            "name" => "Invoice Type without Programme Type Specification",
            "total_students" => $total_student,
            "total_paid_student" => $total_paid_student,
            "total_unpaid_student" => $total_student - $total_paid_student,
            "total_applicants" => $total_applicant,
            "total_paid_applicant" => $total_paid_applicant,
            "total_unpaid_applicant" => $total_applicant - $total_paid_applicant,
        ];

        return $programmes;
    }

    public function totalPaidAndUnpaid($payment_short_name, $session_id)
    {
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);


        $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id]);
        $invoices_type_ids = $invoices_types->pluck('id');

        $total_applicant = $this->applicant::where(["session_id" => $session_id])->count();
        $total_student = $this->student::where(['status' => 'Active'])->count();


        $total_paid_student = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where(['owner_type' => 'student', 'status' => 'paid'])->count();
        $total_paid_applicant = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where(['owner_type' => 'applicant', 'status' => 'paid'])->count();

       return [
            "name" => $payment_short_name,
            "total_students" => $total_student,
            "total_paid_student" => $total_paid_student,
            "total_unpaid_student" => $total_student - $total_paid_student,
            "total_applicants" => $total_applicant,
            "total_paid_applicant" => $total_paid_applicant,
            "total_unpaid_applicant" => $total_applicant - $total_paid_applicant,
        ];

    }

    public function studentByPhysicalChallenge(){
        $physical_challenged_male = $this->student::where(['disability'=>'None','gender'=>'male'])->count();
        $physical_challenged_female = $this->student::where(['disability'=>'None','gender'=>'female'])->count();
        $physical_challenged_other = $this->student::where(['disability'=>'None','gender'=>'other'])->count();

        $not_physical_challenged_male = $this->student::where('disability','!=','None')->where('gender','male')->count();
        $not_physical_challenged_female = $this->student::where('disability','!=','None')->where('gender','female')->count();
        $not_physical_challenged_other = $this->student::where('disability','!=','None')->where('gender','other')->count();
        return [
            [
                'name'=>'Physical Challenged',
                 "male"=>$physical_challenged_male,
                 "female"=>$physical_challenged_female,
                 "other"=>$physical_challenged_other
            ],
            [
                'name'=>'Not Physical Challenged',
                 "male"=>$not_physical_challenged_male,
                 "female"=>$not_physical_challenged_female,
                 "other"=>$not_physical_challenged_other
            ],
        ];
    }

    public function admittedByEntryMode($session_id,$programme_type_id=null){
        $entry_modes = DB::table('entry_modes')->get();

        if(!empty($programme_type_id)){
            $applicants = $this->applicant::where(['session_id'=>$session_id, 'admission_status'=>'admitted','programme_type_id'=>$programme_type_id])->get();
        }else{
            $applicants = $this->applicant::where(['session_id'=>$session_id,'admission_status'=>'admitted'])->get();
        }

        foreach($entry_modes as &$entry_mode){
            $entry_mode->total = $applicants->where('mode_of_entry_id',$entry_mode->id)->count();
        }
        return $entry_modes;
    }

    public function activeAndNonActiveStudent(){
        $statuses = [
            ["name"=>'active'],
            ["name"=>'expel'],
            ["name"=>'rusticated'],
            ["name"=>'voluntary withdraw'],
            ["name"=>'academic withdrawal'],
            ["name"=>'death'],
            ["name"=>'deferment'],
            ["name"=>'suspension'],
            ["name"=>'graduated']
        ];

        foreach($statuses  as &$status){
            $status['total'] = $this->student::where('status',$status['name'])->count();
        }
        return $statuses;
    }

    public function studentRegisteredAndUnregisteredCount($session_id,$programme_type_id){

        $registered_course_students = DB::select(DB::raw("select IF(x.total IS NULL,0, x.total) total_registered ,l.order as level_order,l.id as level_id, l.title as level from (SELECT COUNT(s.id) total, l.title FROM students AS s JOIN student_courses_grades AS c ON c.student_id= s.id LEFT  JOIN levels as l ON l.id = c.level_id WHERE c.session_id=$session_id AND s.status = 'active' AND s.programme_type_id= $programme_type_id GROUP BY l.title) x RIGHT JOIN levels l on l.title = x.title"));
        collect($registered_course_students)->map(function($item)use($session_id, $programme_type_id){
            $level_id = $item->level_id;
            if($item->level_order == 1){
                $total_expected_registered = DB::select(DB::raw("SELECT count(s.id) as total FROM applicants s JOIN student_enrollments e ON s.id = e.owner_id WHERE e.session_id=$session_id AND e.level_id_from = $level_id AND s.programme_type_id = $programme_type_id "));
            }else{
                $total_expected_registered = DB::select(DB::raw("SELECT count(s.id) as total FROM students s JOIN student_enrollments e ON s.id = e.owner_id WHERE e.session_id=$session_id AND e.level_id_from = $level_id AND s.programme_type_id = $programme_type_id "));
            }
            return $item->total_unregistered = $total_expected_registered[0]->total - $item->total_registered;
        });

        //$number_of_unregistered_course_students = $this->student::joinwhere(['status'=>'active','programme_type_id'=>$programme_type_id])->groupBy('level_id')->get()->map(function());
        return $registered_course_students;
    }

    public function schoolFeeTotalPaidAndUnpaid($session_id){
        $payment_category_id = $this->getPaymentCategoryId('registration_fee');
        $invoice_types = $this->invoiceType::where(['session_id'=>$session_id, 'payment_category_id'=>$payment_category_id,'owner_type'=>'student'])->get();
        $expected_amount = 0;
        $invoice_type_ids = [];

        foreach ($invoice_types as $key => $invoice_type) {
            $invoice_type_ids[] = $invoice_type->id;
            $invoice_type_arr = [
                "gender"=>$invoice_type->gender,
                "level_id"=>$invoice_type->level_id,
                "programme_id"=>$invoice_type->programme_id,
                "programme_type_id"=>$invoice_type->programme_type_id,
                "department_id"=>$invoice_type->department_id,
                "faculty_id"=>$invoice_type->faculty_id,
                "entry_mode_id"=>$invoice_type->entry_mode_id,
                "state_id"=>$invoice_type->state_id,
                "lga_id"=>$invoice_type->lga_id,
                "country_id"=>$invoice_type->country_id
            ];

            $invoice_type_filtered = array_filter($invoice_type_arr,function($value,$key){
                return !is_null($value);
            },ARRAY_FILTER_USE_BOTH);

            $student_count = $this->student::match($invoice_type_filtered)->where('status','active')->count();
            $expected_amount += ($student_count * $invoice_type->amount);
        }


        $total_paid = $this->invoice::whereIn('invoice_type_id', $invoice_type_ids)->where(['session_id'=>$session_id,'status'=>'paid'])->sum('amount');
        return [
            'total_amount_paid' => $total_paid,
            'expected_amount' => $expected_amount,
            'total_amount_unpaid'=>$expected_amount -$total_paid
        ];

    }

    public function allFeeReport($session_id)
    {
        $payment_categories = DB::table('payment_categories')->get();
        foreach($payment_categories as &$payment_category){
            $invoice_type_ids = $this->invoiceType::where(["payment_category_id" => $payment_category->id,'session_id'=> $session_id])->pluck('id');
            $payment_category->total_paid = $this->invoice::where(['session_id'=> $session_id, "status"=>"paid"])->whereIn('invoice_type_id',$invoice_type_ids)->sum('amount');
            $session = DB::table('sessions')->where('id',$session_id)->first();
            $payment_category->session = $session?->name;
        }
        return $payment_categories;
    }

    public function applicantByPhysicalChallenge(){
        $physical_challenged_male = $this->applicant::where(['disability'=>'None','gender'=>'male'])->count();
        $physical_challenged_female = $this->applicant::where(['disability'=>'None','gender'=>'female'])->count();
        $physical_challenged_other = $this->applicant::where(['disability'=>'None','gender'=>'other'])->count();

        $not_physical_challenged_male = $this->applicant::where('disability','!=','None')->where('gender','male')->count();
        $not_physical_challenged_female = $this->applicant::where('disability','!=','None')->where('gender','female')->count();
        $not_physical_challenged_other = $this->applicant::where('disability','!=','None')->where('gender','other')->count();
        return [
            [
                'name'=>'Physical Challenged',
                 "male"=>$physical_challenged_male,
                 "female"=>$physical_challenged_female,
                 "other"=>$physical_challenged_other
            ],
            [
                'name'=>'Not Physical Challenged',
                 "male"=>$not_physical_challenged_male,
                 "female"=>$not_physical_challenged_female,
                 "other"=>$not_physical_challenged_other
            ],
        ];
    }

    public function studentsBySponsorship($session_id =null){
        $students = DB::table('students')->selectRaw('count(students.id) as total,sponsor_types.name as sponsor_type ')->join('sponsor_types','students.sponsor_type','=','sponsor_types.id' )->where(['status'=>'Active'])->groupBY('sponsor_types.name')->get();
        $applicants = DB::table('applicants')->selectRaw('count(applicants.id) as total,sponsor_types.name as sponsor_type ')->join('sponsor_types','applicants.sponsor_type','=','sponsor_types.id' )->where('session_id',$session_id)->groupBY('sponsor_types.name')->get();
        return [
            "student" => $students,
            "applicant"=> $applicants
        ];
    }

    public function totalStudentsByLevels($session_id)
    {
        $programme_types = $this->programmeType::get()->toArray();
        $levels = $this->level::get()->toArray();
        foreach ($programme_types as &$programme_type) {
            $programme_type_id = $programme_type['id'];
            foreach($levels as $key => &$level){
                $level_id = $level['id'];
                if($level['order'] == 1){
                    $data = DB::select(DB::raw("SELECT count(s.id) as total FROM applicants s JOIN student_enrollments e ON (s.id = e.owner_id AND e.owner_type ='applicant') WHERE e.session_id=$session_id AND e.level_id_to = $level_id AND s.programme_type_id = $programme_type_id "));
                }else{
                    $data = DB::select(DB::raw("SELECT count(s.id) as total FROM students s JOIN student_enrollments e ON (s.id = e.owner_id AND e.owner_type ='student') WHERE e.session_id=$session_id AND e.level_id_to = $level_id AND s.programme_type_id = $programme_type_id "));
                }
                $level['total'] = $data[0]->total;
            };
            $programme_type['levels'] = $levels;
        }
        return $programme_types;
    }

    public function totalStudentsByProgrammes($session_id)
    {
        $programme_types = $this->programmeType::get()->toArray();
        foreach ($programme_types as &$programme_type) {
            $programme_type_id = $programme_type['id'];
            $programmes = DB::select(DB::raw("SELECT p.name,p.code, count(s.id) total FROM programmes p  JOIN students s ON p.id = s.programme_id WHERE s.programme_type_id=$programme_type_id AND s.status = 'active' GROUP BY   p.name, p.code "));
            $programme_type['programmes'] = $programmes;
        }
        return $programme_types;
    }

    /* public function totalPaidAndUnpaidAmountByProgrammeType($payment_short_name, $session_id){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        $programme_types = $this->programmeType::all();
        $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id])->get();
        //dd($invoices_types->toArray());
        $applicant_invoices_x = DB::table('invoices')->where(['status' => 'paid', "session_id"=>$session_id, 'owner_type'=> 'applicant'])->get();
        $student_invoices_x = DB::table('invoices')->where(['status' => 'paid', "session_id"=>$session_id, 'owner_type'=> 'student'])->get();
        $total_student_x = DB::table('student_enrollments', 'e')->join('students','students.id','=','e.owner_id')->where(["session_id"=>$session_id, 'owner_type'=> 'student'])->get();

        foreach ($programme_types as $key => &$programme_type) {
            $programme_type_id = $programme_type['id'];

            $applicant_invoices_type_ids = $invoices_types->where("programme_type_id",$programme_type_id)->where("owner_type","applicant")->pluck('id')->toArray();
            $student_invoices_type_ids = $invoices_types->where("programme_type_id",$programme_type_id)->where("owner_type","student")->pluck('id')->toArray();

            if (count($applicant_invoices_type_ids) == 0) {
                $programme_type['total_expected_amount_applicants'] = 0;
                $programme_type['total_amount_paid_applicants'] = 0;
                $programme_type['total_amount_unpaid_applicants'] = 0;
            }else{
                $applicant_invoices_type_amount = $invoices_types->where("programme_type_id",$programme_type_id)->where("owner_type","applicant")->sum('amount');
                $applicant_invoices = $applicant_invoices_x->whereIn("invoice_type_id", $applicant_invoices_type_ids);
                $total_applicant = $this->applicant::where(['programme_type_id' => $programme_type->id, "session_id" => $session_id])->count();
                $expected_amount_applicants = $total_applicant * $applicant_invoices_type_amount;
                $programme_type['total_expected_amount_applicants'] = $expected_amount_applicants;
                $programme_type['total_amount_paid_applicants'] = $applicant_invoices->sum('amount');
                $programme_type['total_amount_unpaid_applicants'] = $expected_amount_applicants - $programme_type->total_paid_applicant;
            }

            if (count($student_invoices_type_ids) == 0) {
                $programme_type['total_expected_amount_students'] = 0;
                $programme_type['total_amount_paid_students'] = 0;
                $programme_type['total_amount_unpaid_students'] = 0;
            } else {
                $student_invoices_type_amount = $invoices_types->where("programme_type_id",$programme_type_id)->where("owner_type","student")->sum('amount');
                $student_invoices = $student_invoices_x->whereIn("invoice_type_id", $student_invoices_type_ids);
                $total_student = $total_student_x->where('programme_type_id' ,$programme_type->id)->count();
                $expected_amount_students = $total_student * $student_invoices_type_amount;
                $programme_type['total_expected_amount_students'] = $expected_amount_students;
                $programme_type['total_amount_paid_students'] = $student_invoices->sum('amount');
                $programme_type['total_amount_unpaid_students'] = $expected_amount_students - $programme_type->total_paid_student;

            }
        }

        return $programme_types;
    }
    */
    public function totalPaidAndUnpaidAmountByProgrammeType($payment_short_name, $session_id,$owner_type){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        if($owner_type == "student"){

            $response = DB::table("invoices","i")
            ->join('students as s','s.id','i.owner_id')
            ->join("student_enrollments as se","se.owner_id","=","s.id")
            ->join("programme_types as p","p.id",'=',"s.programme_type_id")
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme_type, p.short_name as short_name ")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'student'])
            ->groupBy("p.name","p.short_name")
            ->get();
        }else{
            $response = DB::table("invoices","i")
            ->join('applicants as s','s.id','i.owner_id',)
            ->join("programme_types as p","p.id",'=',"s.programme_type_id")
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme_type, p.short_name as short_name")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'applicant'])
            ->groupBy("p.name","p.short_name")
            ->get();
        }
      return $response;
    }

    /*public function totalPaidAndUnpaidAmountBylevelProgrammeType($payment_short_name,$session_id){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        $programme_types = $this->programmeType::get()->toArray();
        $levels = $this->level::get()->toArray();
        $applicant_invoices_x = DB::table('invoices')->where(['status' => 'paid', "session_id"=>$session_id, 'owner_type'=> 'applicant'])->get();
        $student_invoices_x = DB::table('invoices')->where(['status' => 'paid', "session_id"=>$session_id, 'owner_type'=> 'student'])->get();
        $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id])->get();
        $student_data = collect(DB::select(DB::raw("SELECT s.programme_type_id,e.level_id_to level_id FROM students s JOIN student_enrollments e ON s.id = e.owner_id WHERE owner_type='student' AND e.session_id=$session_id")));
        foreach ($programme_types as &$programme_type) {
            $programme_type_id = $programme_type['id'];
            foreach($levels as $key => &$level){
                $level_id = $level['id'];
                $total_amount_paid = 0;

                if($level['order'] == 1){
                    $data = DB::select(DB::raw("SELECT count(s.id) as total FROM applicants s JOIN student_enrollments e ON s.id = e.owner_id WHERE owner_type='applicant' AND e.session_id=$session_id AND e.level_id_to = $level_id AND s.programme_type_id = $programme_type_id "));
                    $invoices_type_ids = $invoices_types->where("programme_type_id",$programme_type_id)->where("owner_type","applicant")->pluck('id')->toArray();
                    $invoices_type_amount = $invoices_types->where( "programme_type_id",$programme_type_id)->where("owner_type","applicant")->where('level_id',$level_id)->sum('amount');
                    $total_amount_paid = $applicant_invoices_x->whereIn("invoice_type_id", $invoices_type_ids)->sum('amount');
                    $total_count =$data[0]->total;
                    $expected_amount = $total_count * $invoices_type_amount;
                    $level['total_expected_amount'] = $expected_amount ;
                    $level['total_amount_paid'] = $total_amount_paid;
                    $level['total_amount_unpaid'] = $expected_amount - $total_amount_paid;
                }else{
                    $invoices_type_ids = $invoices_types->where("programme_type_id",$programme_type_id)->where("owner_type","student")->where('level_id',$level_id)->pluck('id')->toArray();

                    $total_amount_paid = $student_invoices_x->whereIn("invoice_type_id", $invoices_type_ids)->sum('amount');
                    $invoices_type_amount = $invoices_types->where( "programme_type_id",$programme_type_id)->where("owner_type","student")->where('level_id',$level_id)->sum('amount');

                    $total_count = $student_data->where('programme_type_id', $programme_type_id)->where("level_id",$level_id)->count();

                    $expected_amount = $total_count * $invoices_type_amount;
                    $level['total_expected_amount'] =$expected_amount ;
                    $level['total_amount_paid'] = $total_amount_paid;
                    $level['total_amount_unpaid'] =$expected_amount - $total_amount_paid;
                }

            };
            $programme_type['levels'] = $levels;
        }
        return $programme_types;
    } */

    public function totalPaidAndUnpaidAmountBylevelProgrammeType($payment_short_name,$session_id,$owner_type){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        if($owner_type == "student"){

            $response = DB::table("invoices","i")
            ->join('students as s','s.id','i.owner_id')
            ->join("student_enrollments as se","se.owner_id","=","s.id")
            ->join("levels as l","l.id","=","se.level_id_to")
            ->join("programme_types as p","p.id",'=',"s.programme_type_id")
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme_type, p.short_name as short_name,l.title as level ")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'student'])
            ->groupBy("p.name","l.title","p.short_name")
            ->get()->groupBy("level");
        }else{
            $response = DB::table("invoices","i")
            ->join('applicants as s','s.id','i.owner_id',)
            ->join("levels as l","l.id","=","s.level_id")
            ->join("programme_types as p","p.id",'=',"s.programme_type_id")
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme_type, p.short_name as short_name,l.title as level ")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'applicant'])
            ->groupBy("p.name","l.title","p.short_name")
            ->get()->groupBy("level");
        }
      return $response;
    }

    public function totalPaidAndUnpaidAmountBylevelProgramme($payment_short_name,$session_id,$owner_type){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        if($owner_type == "student"){

            $response = DB::table("invoices","i")
            ->join('students as s','s.id','i.owner_id')
            ->join("student_enrollments as se","se.owner_id","=","s.id")
            ->join("levels as l","l.id","=","se.level_id_to")
            ->join("programmes as p","p.id",'=',"s.programme_id")
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme, p.code as code,l.title as level ")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'student'])
            ->groupBy("p.name","l.title","p.code")
            ->get()->groupBy("level");
        }else{
            $programme_id = "s.applied_programme_curriculum_id";
            if($payment_short_name == "acceptance_fee"){
                $programme_id = "s.programme_id";
            }
            $response = DB::table("invoices","i")
            ->join('applicants as s','s.id','i.owner_id',)
            ->join("levels as l","l.id","=","s.level_id")
            ->join("programmes as p","p.id",'=',$programme_id)
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme, p.code as code,l.title as level ")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'applicant'])
            ->groupBy("p.name","l.title","p.code")
            ->get()->groupBy("level");
        }
      return $response;
    }

    /*public function totalPaidAndUnpaidAmountBylevelProgramme($payment_short_name,$session_id){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);

        $programmes = $this->programme::select('id','name','code')->get()->toArray();
        $levels = $this->level::get()->toArray();

        $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id])->get();

        foreach ($programmes as $key => &$programme) {

            $programme_id = $programme['id'];
            $invoices_types_query_response = collect(DB::select(DB::raw("SELECT l.id level_id, i.id invoice_type_id, i.amount FROM invoice_types i JOIN levels l ON l.id = i.level_id WHERE programme_id = $programme_id AND session_id = $session_id")));
            $invoices_type_ids = $invoices_types_query_response->groupBy('level_id')->map(function($item){
                                    return $item->pluck('invoice_type_id')->toArray();
                                 })->toArray();
            $invoices_type_amounts = $invoices_types_query_response->groupBy('level_id')->map(function($item){
                return $item->sum('amount');
            })->toArray();

            foreach($levels as $key => &$level){
                $level_id = $level['id'];

                $total_amount_paid = 0;
                if($level['order'] == 1){
                    $data = DB::select(DB::raw("SELECT count(s.id) as total FROM applicants s JOIN student_enrollments e ON s.id = e.owner_id WHERE owner_type='applicant' AND e.session_id=$session_id AND e.level_id_from IS NULL AND s.programme_id = $programme_id "));

                    if(array_key_exists($level_id,$invoices_type_ids)){
                        $total_amount_paid = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids[$level_id])->where(['owner_type' => 'applicant', 'status' => 'paid'])->sum('amount');
                    }
                }else{
                    $data = DB::select(DB::raw("SELECT count(s.id) as total FROM students s JOIN student_enrollments e ON s.id = e.owner_id WHERE owner_type='student' AND e.session_id=$session_id AND e.level_id_to = $level_id AND s.programme_id = $programme_id "));
                    if(array_key_exists($level_id,$invoices_type_ids)){
                        $total_amount_paid = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids[$level_id])->where(['owner_type' => 'student', 'status' => 'paid'])->sum('amount');
                    }
                }
                $total_count =$data[0]->total;
                $expected_amount = 0;
                if(array_key_exists($level_id,$invoices_type_amounts)){
                    $expected_amount = $total_count * $invoices_type_amounts[$level_id];
                }
                $level['total_amount_paid'] = $total_amount_paid;
                $level['total_amount_unpaid'] = $expected_amount - $total_amount_paid;
            };
            $programme['levels'] = $levels;
        }
        return $programmes;
    }*/

    /*public function totalPaidAndUnpaidAmountByProgramme($payment_short_name, $session_id){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        $programmes = $this->programme::select('id','name','code')->get()->toArray();

        $invoices_types = $this->invoiceType::where(["session_id" => $session_id, "payment_category_id" => $payment_category_id])->get();
        foreach ($programmes as $key => &$programme) {
            $programme_id = $programme['id'];
            $invoices_type_ids = $invoices_types->where("programme_id" ,$programme_id)->pluck('id')->toArray();
            $invoices_type_amount = $invoices_types->where("programme_id" ,$programme_id)->sum('amount');
            if (count($invoices_type_ids) == 0) {
                $programme['total_amount_students'] = 0;
                $programme['total_amount_paid_student'] = 0;
                $programme['total_amount_unpaid_student'] = 0;
                $programme['total_amount_applicants'] = 0;
                $programme['total_amount_paid_applicant'] = 0;
                $programme['total_amount_unpaid_applicant'] = 0;
            } else {

                $applicant_invoices = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where('owner_type', 'student');
                $student_invoices = DB::table('invoices')->whereIn("invoice_type_id", $invoices_type_ids)->where('owner_type', 'applicant');

                $total_student = $this->student::where(['programme_id' => $programme_id, 'status' => 'Active'])->count();
                $total_applicant = $this->applicant::where(['programme_id' => $programme_id, "session_id" => $session_id])->count();
                $expected_amount_students = $total_student * $invoices_type_amount;
                $expected_amount_applicants = $total_applicant * $invoices_type_amount;

                $programme['total_amount_students'] = $expected_amount_students;
                $programme['total_amount_paid_student'] = $student_invoices->where(['status' => 'paid', 'owner_type' => 'student'])->sum('amount');
                $programme['total_amount_unpaid_student'] = $expected_amount_students - $programme->total_amount_paid_student;
                $programme['total_amount_applicants'] = $expected_amount_applicants;
                $programme['total_amount_paid_applicant'] = $applicant_invoices->where(['status' => 'paid', 'owner_type' => 'applicant'])->sum('amount');
                $programme['total_amount_unpaid_applicant'] = $expected_amount_applicants - $programme->total_amount_paid_applicant;
            }
        }

        return $programmes;
    }*/

    public function totalPaidAndUnpaidAmountByProgramme($payment_short_name, $session_id,$owner_type){
        $payment_category_id = $this->getPaymentCategoryId($payment_short_name);
        if($owner_type == "student"){
            $response = DB::table("invoices","i")
            ->join('students as s','s.id','i.owner_id')
            ->join("student_enrollments as se","se.owner_id","=","s.id")
            ->join("programmes as p","p.id",'=',"s.programme_id")
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme, p.code")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'student'])
            ->groupBy("p.name","p.code")
            ->get();
        }else{
            $programme_id = "s.applied_programme_curriculum_id";
            if($payment_short_name == "acceptance_fee"){
                $programme_id = "s.programme_id";
            }
            $response = DB::table("invoices","i")
            ->join('applicants as s','s.id','i.owner_id',)
            ->join("programmes as p","p.id",'=',$programme_id)
            ->join("invoice_types as t","i.invoice_type_id","t.id")
            ->selectRaw(" SUM(i.amount) as amount, p.name as programme, p.code")
            ->where(["t.payment_category_id"=>$payment_category_id,"i.session_id"=>$session_id,"i.status"=>"paid","t.owner_type"=>'applicant'])
            ->groupBy("p.name","p.code")
            ->get();
        }
      return $response;
    }

    public function walletReport(){
        $tenant_id = tenant('id');
        $wallet_collection = Invoice::whereNull('confirmed_by')->where(['payment_channel'=>'wallet','status'=>'paid'])->sum('amount');
        $manual_collection = Invoice::whereNotNull('confirmed_by')->where(['payment_channel'=>'wallet','status'=>'paid'])->sum('amount');
        $tenant = Wallet::where(['owner_type'=>'App\\Models\\Tenant','owner_id'=>$tenant_id])->first();
        $unusedBalance = Wallet::where(['tenant_id'=>$tenant_id])->where(function($query){
            return $query->where('owner_type','App\\Models\\Applicant')->orWhere('owner_type','App\\Models\\Student');
        })->sum('balance');
        $settledAmount = WalletSettlement::where(['tenant_id'=>$tenant_id,'status'=>'successful'])->sum('amount');

        return [
            "wallet_collection"=> $wallet_collection,
            "manual_collection"=> $manual_collection,
            "unused_wallet_balance" =>$unusedBalance,
            "settled_amount"=>$settledAmount,
            "next_settled_amount"=> $wallet_collection - $settledAmount
        ];

    }

    public function walletFundingLog($paginateBy, $search){

        $paginateBy = $paginateBy??30;

        if($search != null){
            return Payment::latest()->paginate($paginateBy);
        }
        return Payment::where("payment_reference","Like",$search)->orWhere("jtr","Like", $search)->latest()->paginate($paginateBy);
    }

    public function walletSettlementLog($paginateBy){
        $paginateBy = $paginateBy??30;
        $tenant_id = tenant('id');
        $settlements = WalletSettlement::where(['tenant_id'=>$tenant_id])->latest()->paginate($paginateBy);
        return $settlements;
    }

    public function walletFunding($type, $from, $to){

        $from = Carbon::parse($from)->format('Y-m-d');
        $to = Carbon::parse($to)->format('Y-m-d');

        $payments =  DB::table('payments')->whereBetween('created_at',[$from, $to])->where('status','successful')
                        ->selectRaw('sum(amount) as total_amount, date(created_at) as date')->groupBy('date')
                        ->get();

        $settlements =  WalletSettlement::whereBetween('settlement_date',[$from, $to])->where(['status'=>'successful','tenant_id'=>tenant('id')])
                        ->groupBy('settlement_date')->selectRaw('sum(amount) as total_amount, settlement_date as date')
                        ->get();
        return [
            "fundings"=>$payments,
            "total_fund_amount" => $payments->sum('total_amount'),
            "total_fund_counts" =>$payments->count(),
            "settlements"=>$settlements,
            "total_settlement_amount" =>$settlements->sum('amount'),
            "total_settlement_counts" => $settlements->count(),
        ];

    }

    public function getApplicantsBySessionId($session_id){
        return $this->applicant::where("session_id", $session_id)->get()->makeHidden(['matric_number','qualify' ,'level', 'programme_name', 'programme_type','entry_mode', 'active_state', 'state','country','faculty','department','lga', 'qualification', 'full_name','admitted_programme_name']);
    }
}
