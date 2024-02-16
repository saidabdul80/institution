<?php

namespace Modules\Application\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Application\Services\PaymentsService;
use Modules\Application\Transformers\UtilResource;

class PaymentController extends Controller
{

    private $paymentsService;
    public function __construct(PaymentsService $paymentsService)
    {
        $this->paymentsService = $paymentsService;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try{

            $request->validate([
                "amount" =>'required',
                "payment_reference" =>'required',
                "payment_channel" =>'required',
                "gateway_response" =>'required',
                "transaction_id" =>'required',
                "jtr" =>'required',
                "status" =>'required',
                "invoice_id" =>'required',
                "applicant_id" =>'required',
                "session_id" =>'required',
                "payment_mode" =>'required'
           ]);


           $response = $this->paymentsService->processPaymentStoreDetails($request);
           return new UtilResource($response, false, 200 );
        }catch(ValidationException $e){
            return new UtilResource($e->errors(), true, 400 );
        }

    }

    public function distinct(Request $request)
    {
        try{
            $request->validate([
                "applicant_id" => "required",
                "session_id" => "required",
           ]);

            $response = $this->paymentsService->distinctPayments($request);
            return new UtilResource($response, false, 200 );
        }catch(ValidationException $e){
            return new UtilResource($e->errors(), true, 400 );
        }
    }

    public function getApplicantInvoice(Request $request){
        try{

            $request->validate([
                'applicant_id' => 'required',
                'session_id' => 'required',
                'payment_category_short_name'=>'required',
            ]);
            
            $response = $this->paymentsService->applicantInvoice($request);

            return new UtilResource($response, false, 200 );
        }catch(ValidationException $e){
            return new UtilResource($e->errors(), true, 400 );
        }catch(\Exception $e){
            return new UtilResource($e->getMessage(), true, 400 );
        }
    }


    public function getAllInvoiceTypes(Request $request){
        try{

            $request->validate([
                'owner_type' => 'required',
                'session_id' => 'required'
            ]);

            $response = $this->paymentsService->getAllInvoiceTypes($request);

            return new UtilResource($response, false, 200 );
        }catch(ValidationException $e){
            return new UtilResource($e->errors(), true, 400 );
        }catch(\Exception $e){
            return new UtilResource($e->getMessage(), true, 400 );
        }
    }

    public function getApplicantInvoices(Request $request)
    {
        try {

            $request->validate([
                'owner_id' => 'required',
                'session_id' => 'required',
            ]);

            $response = $this->paymentsService->getApplicantInvoices($request);

            return new UtilResource($response, false, 200);
        } catch (ValidationException $e) {
            return new UtilResource($e->errors(), true, 400);
        } catch (\Exception $e) {
            return new UtilResource($e->getMessage(), true, 400);
        }
    }

    public function getApplicantPayments(Request $request)
    {
        try {

            $request->validate([
                'owner_id' => 'required',
                'session_id' => 'required',
            ]);

            $response = $this->paymentsService->getApplicantPayments($request);

            return new UtilResource($response, false, 200);
        } catch (ValidationException $e) {
            return new UtilResource($e->errors(), true, 400);
        } catch (\Exception $e) {
            return new UtilResource($e->getMessage(), true, 400);
        }
    }

    public function proceedToPay(Request $request)
    {
        try {
            return new UtilResource($this->paymentsService->proceedToPay($request->all()), false, 200);
        } catch (ValidationException $e) {
            return new UtilResource($e->errors(), true, 400);
        } catch (\Exception $e) {
            return new UtilResource($e->getMessage(), true, 400);
        }
    }

    public function requery(Request $request)
    {
        try {
            return new UtilResource($this->paymentsService->requery($request->rrr), false, 200);
        } catch (ValidationException $e) {
            return new UtilResource($e->errors(), true, 400);
        } catch (\Exception $e) {
            return new UtilResource($e->getMessage(), true, 400);
        }
    }

    public function paymentWebhook(Request $request)
    {
        try {
            return new UtilResource($this->paymentsService->paymentWebhook($request->all()), false, 200);
        } catch (ValidationException $e) {
            return new UtilResource($e->errors(), true, 400);
        } catch (\Exception $e) {
            return new UtilResource($e->getMessage(), true, 400);
        }
    }
}
