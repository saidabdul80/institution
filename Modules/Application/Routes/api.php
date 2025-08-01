<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Application\Http\Controllers\ApplicantsController;
use Modules\Application\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentController as CentralPaymentController;
use App\Http\Controllers\InvoiceController as CentralInvoiceController;
use App\Http\Resources\APIResource;

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


//Application Portal Routes #########################
Route::prefix('applicants')->group(function() {

    Route::post('/login', [ApplicantsController::class, 'login']);
    Route::get('/login', function (){
        return new APIResource("You must login to do that",true,401);        
    })->name('login');
    Route::post('/create', [ApplicantsController::class, 'create']);

    Route::group(["middleware" => ['auth:api-applicants']], function () {
        Route::get('/registration_progress/{applicant_id}', [ApplicantsController::class, 'registrationProgress']);
        Route::get('/self', [ApplicantsController::class, 'getApplicantById']);
        Route::get('/alevel/{id}', [ApplicantsController::class, 'aLevelResult']);
        Route::get('/get_documents', [ApplicantsController::class, 'getDocuments']);
        
       // Route::post('/create', [ApplicantsController::class, 'create']);
        Route::post('/update', [ApplicantsController::class, 'updateApplicant']);
        Route::post('/logout', [ApplicantsController::class, 'logout']);
        Route::post('/uploadPicture', [ApplicantsController::class, 'uploadPicture']);
        Route::post('/applicants', [ApplicantsController::class, 'getApplicants']);
        Route::post('/get_olevel_results', [ApplicantsController::class, 'oLevelResults']);
        Route::post('/olevel_results', [ApplicantsController::class, 'updateOLevelResults']);
        Route::post('/update_documents', [ApplicantsController::class, 'updateDocument']);
        Route::post('/alevel', [ApplicantsController::class, 'updateALevel']);
        Route::post('/wallet', [ApplicantsController::class, 'getWallet']);
        Route::post('/payment', [PaymentController::class, 'store']);
        Route::post('/get_payment', [PaymentController::class, 'distinct']);

        Route::group(["prefix"=>"invoices"], function () {
            Route::get('/', [PaymentController::class, 'getApplicantInvoices']);
            Route::post('/generate', [CentralInvoiceController::class, 'generateInvoice']);
            Route::post('/initiate_payment', [CentralPaymentController::class, 'redirectToGateway']);
        });

        
        Route::group(["prefix"=>"payments"], function () {
            Route::post('/details', [ApplicantsController::class, 'paymentDetails']);
            Route::get('/{invoice_id}', [PaymentController::class, 'getApplicantPayments']);
            //Route::post('/', [PaymentController::class, 'getApplicantPayments']);
            Route::post('/requery/{payment_reference?}', [CentralPaymentController::class, 'requery']);
            Route::post('/pay', [CentralPaymentController::class, 'pay']);
            Route::post('/distinct_payment_details', [PaymentController::class, 'distinct']);
            Route::post('/all_invoice_types', [PaymentController::class, 'getAllInvoiceTypes']);
            Route::post('/distinct_payment_details', [PaymentController::class, 'distinct']);
        });    
    });
});


//Route::post('/test2', [ApplicantsController::class, 'updateApplicant']);
//Route::post('/apply', [ApplicantsController::class, 'create']);
//Route::post('/get_olevel_results', [ApplicantsController::class, 'oLevelResults']);
