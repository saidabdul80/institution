<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Application\Http\Controllers\ApplicantsController;
use Modules\Application\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentController as CentralPaymentController;
use App\Http\Controllers\InvoiceController as CentralInvoiceController;

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

Route::prefix('applicants')->middleware('tenancy')->group(function() {

    
    Route::get('/login', function() {
        return ["message" => "You must be logged in to do that!"];
    })->name('applicant.login');


    Route::group(["middleware" => ['auth:api:applicant']], function () {

        Route::post('/logout', [ApplicantsController::class, 'logout']);

            Route::post('/update', [ApplicantsController::class, 'updateApplicant']);
            Route::post('/uploadPicture', [ApplicantsController::class, 'uploadPicture']);
            Route::post('/applicants', [ApplicantsController::class, 'getApplicants']);

            Route::post('/get_olevel_results', [ApplicantsController::class, 'oLevelResults']);
            Route::post('/olevel_results', [ApplicantsController::class, 'updateOLevelResults']);
            Route::get('/alevel/{id}', [ApplicantsController::class, 'aLevelResult']);
            Route::post('/alevel', [ApplicantsController::class, 'updateALevel']);
            Route::get('/registration_progress/{applicant_id}', [ApplicantsController::class, 'registrationProgress']);
            Route::post('/payment_details', [ApplicantsController::class, 'paymentDetails']);
            Route::get('/{id}', [ApplicantsController::class, 'getApplicantById']);
            Route::post('/wallet', [ApplicantsController::class, 'getWallet']);

            Route::post('/generate_applicant_invoice', [CentralInvoiceController::class, 'generateInvoice']);
            Route::post('/payment', [PaymentController::class, 'store']);
            Route::post('/get_payment', [PaymentController::class, 'distinct']);
            Route::post('/invoices', [PaymentController::class, 'getApplicantInvoices']);
            Route::post('/payments', [PaymentController::class, 'getApplicantPayments']);
            Route::post('/initiate_payment', [CentralPaymentController::class, 'initiatePayment']);
            Route::post('/requery/{payment_reference?}', [CentralPaymentController::class, 'requery']);        

    Route::group(["prefix"=>"payments"], function () {
        Route::post('/pay', [CentralPaymentController::class, 'pay']);
        Route::post('/distinct_payment_details', [PaymentController::class, 'distinct']);
        Route::post('/all_invoice_types', [PaymentController::class, 'getAllInvoiceTypes']);
        Route::post('/distinct_payment_details', [PaymentController::class, 'distinct']);
    });

    });
    Route::post('/login', 'ApplicantsController@login');
    Route::post('/create', [ApplicantsController::class, 'create']);

});


//Route::post('/test2', [ApplicantsController::class, 'updateApplicant']);
//Route::post('/apply', [ApplicantsController::class, 'create']);
//Route::post('/get_olevel_results', [ApplicantsController::class, 'oLevelResults']);
