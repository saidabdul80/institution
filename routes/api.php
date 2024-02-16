<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\CentralController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\TenantController;
use App\Jobs\CreateInvoice;
use App\Jobs\CreateInvoiceApplicant;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\HostelPortalAPI\Http\Controllers\PaymentsController;
use Modules\Staff\Http\Controllers\FacultyController;
use Modules\Staff\Http\Controllers\DepartmentController;
use Modules\Staff\Http\Controllers\CourseController;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('tenancy')->group(function () {    
    
    Route::get('school-info', [Controller::class,'getSchoolInfo']);
    Route::get('/faculties', [FacultyController::class, 'getFaculties']);
    //Route::get('/departments', [DepartmentController::class, 'getDepartments']);
    //Route::get('/courses', [CourseController::class, 'getCourses']);
    
    Route::get('/countries', [CentralController::class, 'country']);
    Route::get('/programme_types', [CentralController::class, 'programmeType']);
    Route::get('/qualifications', [CentralController::class, 'getQualifications']);
    Route::get('/subjects', [CentralController::class, 'getSubjects']);
    Route::get('/levels', [CentralController::class, 'getLevels']);
    Route::get('/exam_types', [CentralController::class, 'getExamTypes']);
    Route::get('/certifacte_types', [CentralController::class, 'getCertificateType']);
    
    Route::post('/invoice-pdf', [PDFController::class, 'downloadInvoice']);
    Route::post('/receipt-pdf', [PDFController::class, 'downloadPaymentReceipt']);
    Route::post('/slip-pdf', [PDFController::class, 'downloadSlip']);
    Route::post('/biodata-pdf', [PDFController::class, 'biodataSlip']);
    Route::post('/acknowledgement-pdf', [PDFController::class, 'acknowledgementSlip']);
    Route::post('/pdf/exam-card', [PDFController::class, 'examCard']);
    Route::post('/pdf/course-form', [PDFController::class, 'courseForm']);
    Route::post('/pdf/result-slip', [PDFController::class, 'resultSlip']);
    Route::post('/pdf/olevel-slip', [PDFController::class, 'olevelSlip']);
    
    //Route::post('/payments/pay', [PaymentsController::class, 'pay'])->middleware('idempotency');
    Route::get('payment/{reference}', [PaymentController::class, 'show'])->middleware('idempotency');
    Route::get('/invoice/{invoice_number}', [InvoiceController::class, 'getInvoice']);
    
    
    Route::get('/states/{country_id?}', [CentralController::class, 'state']);
    Route::get('/lgas/{state_id?}', [CentralController::class, 'lga']);
    Route::get('/programmes/{id?}', [CentralController::class, 'programme']);
});

Route::post('/save_school', [TenantController::class, 'createSchool']);


Route::get('/run_for_missing_invoices', function(){
    $students = Student::whereIn('level_id',[3,9])->where(['programme_type_id'=>2,'status'=>'active'])->get();    
    foreach($students as $student){
        dispatch(new CreateInvoice($student));
    }
    
    $applicants = Applicant::where(['programme_type_id'=>2,'session_id'=>8])->get();    
    foreach($applicants as $applicant){
        dispatch(new CreateInvoiceApplicant($applicant));
    }
});