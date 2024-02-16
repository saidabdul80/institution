<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function show($reference)
    {
        $payment = $this->paymentService->getPaymentByReference($reference);
        return new ApiResource($payment,  false, 200);
    }

    public function initiatePayment(Request $request)
    {
        try {
            $payment = $this->paymentService->initiatePayment($request->all());
            return new ApiResource($payment, false, 200);
        } catch (ValidationException $e) {
            return new ApiResource($e->errors(), true, 400);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, $e->getCode());
        }
    }

    public function requery(Request $request)
    {
        try {
            $requery = $this->paymentService->requery($request->payment_reference);
            return new ApiResource($requery, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, $e->getCode());
        }
    }

    public function paymentWebhook(Request $request)
    {
        try {
            return $this->paymentService->paymentWebhook($request->all());
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function pay(Request $request){
        try {
            $request->validate([
                "invoice_id" => "required",
            ]);
            $response = $this->paymentService->pay($request->get('invoice_id'));
            return new ApiResource($response, false, 200 );
        }catch(ValidationException $e){
            return new ApiResource($e->errors(), true, 400 );
        } catch (Exception $e) {
            return $e;
            return new ApiResource($e->getMessage(), true, $e->getCode());
        }
    }
}
