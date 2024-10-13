<?php

// use Illuminate\Http\Request;
// use Modules\Student\Http\Controllers\StudentController;
// use Modules\Student\Http\Controllers\PaymentController;
// use Modules\Staff\Http\Controllers\InvoiceTypeController;
// use Illuminate\Support\Facades\Route;
// use Modules\ApplicationPortalAPI\Http\Controllers\PaymentController as ControllersPaymentController; //from app
// use App\Http\Controllers\PaymentController as CentralPaymentController;
// /*
// |--------------------------------------------------------------------------
// | API Routes
// |--------------------------------------------------------------------------
// |
// | Here is where you can register API routes for your application. These
// | routes are loaded by the RouteServiceProvider within a group which
// | is assigned the "api" middleware group. Enjoy building your API!
// |
// */

// Route::prefix('studentportal')->group(function() {
//     Route::post('/login', 'StudentController@login');
//     Route::get('/login', function() {
//         return ["message" => "You must be logged in to do that!"];
//     })->name('students.login');
//     Route::group(["middleware" => ['auth:api:studentsportal']], function () {

//         Route::post('/logout', [StudentController::class, 'logout']);

//         Route::group(["prefix"=>"student"], function () {
//             Route::post('/students', [StudentController::class, 'getStudents']);
//             Route::post('/update', [StudentController::class, 'updateStudent']);
//             Route::post('/uploadPicture', [StudentController::class, 'uploadPicture']);
//             Route::post('/upload_signature', [StudentController::class, 'uploadSignature']);
//             Route::post('/register_courses', [StudentController::class, 'registerCourses']);
//             Route::post('/unregister_courses', [StudentController::class, 'unregisterCourses']);
//             Route::post('/generate_invoice', [PaymentController::class, 'generateInvoice']);
//             Route::post('/payment', [PaymentController::class, 'store']);
//             Route::post('/get_payments', [PaymentController::class, 'getPayments']);
//             Route::post('/requery/{payment_reference?}', [CentralPaymentController::class, 'requery'])->middleware('idempotency');
//             Route::get('/', [StudentController::class, 'getStudentById']);
//             Route::post('/by_payment_category', [InvoiceTypeController::class, 'getInvoiceTypeByCategory']);
//             //Route::get('/invoice_types', [InvoiceTypeController::class, 'getInvoiceTypes']);
//             Route::post('/payment_status', [PaymentController::class, 'getPaymentStatus']);
//             Route::post('/invoice_types/all', [ControllersPaymentController::class, 'getAllInvoiceTypes']);
//             Route::post('/registered_courses', [StudentController::class, 'registeredCourses']);
//             Route::post('/result', [StudentController::class, 'getResult']);
//             Route::post('/courses_result', [StudentController::class, 'getCoursesResult']);
//             Route::get('/get_student_invoices/{session_id}', [PaymentController::class, 'getStudentInvoice']);
//             Route::get('/programme_courses/{session_id}', [StudentController::class, 'getProgrammeCoursesByProgrammeId']);
//             //Route::post('/generate_rrr', [StudentController::class, 'generateRRR']);
//             Route::post('/payment_details', [PaymentController::class, 'paymentDetails']);
//             Route::post('/all_invoice_types', [PaymentController::class, 'getAllInvoiceTypes']);
//             Route::post('/initiate_payment', [CentralPaymentController::class, 'initiatePayment']);
//             Route::get('/wallet', [StudentController::class, 'getWallet']);
//             Route::post('/pay', [CentralPaymentController::class, 'pay'])->middleware('idempotency');
//         });
//     });
// });