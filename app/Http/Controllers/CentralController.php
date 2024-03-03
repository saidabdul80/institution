<?php

namespace App\Http\Controllers;

use App\Events\InvoicePaid;
use App\Events\PaymentMade;
use App\Http\Resources\APIResource;
use App\Jobs\PromoteStudent;
use App\Listeners\PromoteToStudent;
use App\Models\Applicant;
use App\Models\Country;
use App\Models\Level;
use App\Models\LGA;
use App\Models\Programme;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ApplicationPortalAPI\Transformers\UtilResource;
use Illuminate\Validation\ValidationException;
use App\Models\ProgrammeType;
use App\Models\ExamType;
use App\Models\CertificateType;
use App\Models\Configuration;
use App\Models\CourseCategory;
use App\Models\Department;
use App\Models\EntryMode;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Payment;
use App\Models\PaymentCategory;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Services\Utilities;
//use Illuminate\Support\Facades\Redis;

class CentralController extends Controller
{
    //
    private $country;
    private $state;
    private $lga;
    private $programme;
    private $programmeTypes;
    function __construct(Country $country, State $state, LGA $lga, Programme $programme)
    {
        $this->country  = $country;
        $this->state    = $state;
        $this->lga  = $lga;
        $this->programme    = $programme;
    }

    public function getSchoolInfo(){
        
        try {
            //code...
            $logo = tenant('logo');
            $school_name = tenant('school_name');
            $school_short_name = tenant('school_short');
            
            $response = [
                "course_categories"=>CourseCategory::all(),
                "payment_types"=> PaymentCategory::all(),
                "countries"=>Country::all(),
                "entry_modes"=>EntryMode::all(),
                "faculties"=>Faculty::all(),
                "departments"=>Department::all(),
                "sessions"=>Session::all(),
                "semesters"=>Semester::all(),
                "levels"=>Level::all(),
                "programmes"=>Programme::all(),
                "programme_types"=>ProgrammeType::all(),
                "logo" =>$logo,
                "school_name" =>$school_name,
                "school_short_name" =>$school_short_name,
                "configurations"=> Configuration::all()
            ];
    
            return new APIResource($response, false,200);
        } catch (\Exception $e) {
            return new APIResource($e, true,400);
        }
    }

    public function country()
    {

        try {

            $response = $this->country::all(); // Fallback to another method or storage mechanism
            return new APIResource($response, false, 200);

          /*   $key = 'countries';
            if (Redis::ping()) {
                // Redis is available, proceed with your Redis operations
                if (Redis::get($key)) {
                    $response = json_decode(Redis::get($key));
                } else {
                    $response = $this->country::all();
                    Redis::set($key, json_encode($response));
                    Redis::expire($key, 259200);
                }
            } else {
                $response = $this->country::all(); // Fallback to another method or storage mechanism
            } */
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function state(Request $request)
    {
        try {
            $country_id = $request->country_id ?? '';
            if ($country_id == '') {
                throw new \Exception('country_id is required');
            }
            $response = $this->state::with('lga')->where('country_id', $country_id)->get();
            return new APIResource($response, false, 200);
/* 
            $key = 'country_' . $country_id;
            if (Redis::get($key)) {
                $response = json_decode(Redis::get($key));
            } else {
                $response = $this->state::with('lga')->where('country_id', $country_id)->get();
                Redis::set($key, json_encode($response));
                Redis::expire($key, 259200);
            }
            return new APIResource($response, false, 200); */
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function lga(Request $request)
    {
        try {
            $state_id = $request->state_id ?? '';
            if ($state_id == '') {
                throw new \Exception('state_id is required');
            }
            
            $response = $this->lga::where('state_id', $state_id)->get();
            return new APIResource($response, false, 200);

            /* $key = 'lga_' . $state_id;
            if (Redis::get($key)) {
                $response = json_decode(Redis::get($key));
            } else {
                $response = $this->lga::where('state_id', $state_id)->get();
                Redis::set($key, json_encode($response));
                Redis::expire($key, 259200);
            }
            return new APIResource($response, false, 200); */
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function programme(Request $request)
    {
        try {
            $request_department_id = $request->get('department_id') ?? ""; //same as $id
            $department_id = $request->id ?? $request_department_id;
            $programme_type_id = $request->get('programme_type_id') ?? "";
            $entry_mode_id = $request->get('entry_mode_id') ?? "";

            $data = [
                "department_id" => $department_id,
                "programme_type_id" => $programme_type_id,
                "entry_mode_id" => $entry_mode_id
            ];

            foreach ($data as $datum => $value) {
                if (empty($value)) {
                    unset($data[$datum]);
                }
            }

            $response = $this->programme::search($request->search ?? null)->where($data)->latest()->get();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function programmeType(Request $request)
    {
        try {
            $paginate = $request->paginateBy ?? 100;
            $response = DB::table('programme_types')->latest()->paginate($paginate);
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function programmeTypeWithoutPaginate(Request $request)
    {
        try {
            $response = DB::table('programme_types')->latest()->get();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getQualifications()
    {
        try {
            $response = DB::table('qualifications')->get();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function resetApplicantProgress()
    {
        try {
            $response = DB::table('applicants')->update(['application_progress', 30.0]);
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getLevels()
    {
        try {
            $response = Level::all();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getExamTypes()
    {
        try {
            $response = ExamType::all();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getCertificateType()
    {
        try {
            $response = CertificateType::all();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function getPaymentCategory()
    {
        try {
            $response = DB::table('payment_categories')->get();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getSessions()
    {
        try {
            $response = Session::all();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getModeOfEntries()
    {
        try {
            $response = EntryMode::all();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    function getInvoice(Request $request)
    {
        try {
            $invoice = Invoice::where('invoice_number', $request->invoice_number)->with('payment')->first();
            return new APIResource($invoice, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }


    public function getAllTenants(Request $request)
    {
        try {
            return new APIResource(Tenant::all(), false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function getTenantById(Request $request)
    {
        try {
            $tenant_id = $request->tenant_id ?? '';
            if ($tenant_id == '') {
                throw new \Exception('tenant id is required');
            }
            $response = Tenant::find($tenant_id);
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }



    public function getApplicantVerificationStatus(Request $request)
    {
        try {

            $request->validate([
                "application_number" => "required"
            ]);

            $response = Applicant::where('application_number', $request->get('application_number'))->first();
            if (!$response) {
                throw new \Exception('Application Number is Not Found', 404);
            }

            return new APIResource(["admission_status" => $response->admission_status, "qualification_status" => $response->qualified_status], false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function updateLevel(Request $request)
    {
        try {

            $request->validate([
                "id" => "required",
                "title" => "required"
            ]);

            $response = Level::where('id', $request->id)->update(['title' => $request->title]);

            return new APIResource('Updated Successfully', false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function getSubjects()
    {
        try {
            $response = Subject::all();
            return new APIResource($response, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function test()
    {
        //$students = Student::whereIn('level_id',[3,9])->where(['programme_type_id'=>2,'status'=>'active'])->get();            

    }
}
