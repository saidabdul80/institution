<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentGateway\GatewayFactory;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentController extends Controller
{
    protected $paymentService;

    protected $gatewayFactory;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->gatewayFactory = new GatewayFactory;
        $this->paymentService = $paymentService;
    }

    public function show($reference)
    {
        $payment = $this->paymentService->getPaymentByReference($reference);
        return new ApiResource($payment,  false, 200);
    }


    public function requery(Request $request)
    {
        try {
            $requery = $this->paymentService->requery($request->reference);
            return new ApiResource($requery, false, 200);
        } catch (Exception $e) {
            return new ApiResource($e->getMessage(), true, 400);
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
            return new ApiResource(array_values($e->errors())[0], true, 400 );
        } catch (Exception $e) {
            return $e;
            return new ApiResource($e->getMessage(), true, $e->getCode());
        }
    }

     public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required',
            'gateway' => 'required|string',
            'reference' => 'nullable|string',
        ]);
        
        $user = $request->user();
        $invoice_id = $request->invoice_id;
        
        $invoice = Invoice::where('id', $invoice_id)->first();
        $owner_type = $invoice->owner_type;
        $owner_id = $invoice->owner_id;
      

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }
        
        $gateway = $request->gateway;
        return $this->beginPaymentProcess(
            $invoice_id, 
            $gateway, 
            $owner_type, 
            $owner_id, 
            null, 
            $request->rrr, 
        );
    }


      public function beginPaymentProcess($invoice_id, $gateway, $owner_type, $owner_id, $wallet = null, $rrr = null)
    {
        

        $invoice = Invoice::where('id', $invoice_id)->first();
        
        
        if (!$invoice) {
            return response()->json(["errors" => "No valid invoice found"], 422);
        }
        

        try {
            $paid =  $invoice->status == 'paid';

            if($paid) {
                throw new \Exception('Payment has already been completed on this invoice', 422);
            }
    
            DB::beginTransaction();
            
            $internalReference = Paystack::genTranxRef();
            $gatewayService = $this->gatewayFactory->create($gateway);
            // Use total amount (amount + charges) for payment
            $totalAmount = $invoice->total_amount;

            $paymentData  = $gatewayService->preparePaymentData(
                $gateway, $totalAmount, $owner_type, $owner_id, null, $invoice, $internalReference, null, $rrr
            );
           Log::info("paymentData2 : " . json_encode($paymentData));
            $payment = Payment::create($paymentData);
            $response = $this->handleGatewayPaymentRecord(
                        $gateway,
                        $invoice,
                        $payment,
                        $paymentData,
                        $totalAmount,
                        $wallet,
                        null
                    );
            DB::commit();

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        } catch(\Throwable  $e){
            Log::error($e);
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        } catch(\TypeError  $e){    
            Log::error($e);    
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function handleWebhook(Request $request)
    {
        $gateway = $request->gateway;
        
        try {
            $gatewayService = $this->gatewayFactory->create($gateway);
            return $gatewayService->handleWebhook($request);
        } catch (\Exception $e) {
            Log::error("Webhook handling error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    

    public function handleGatewayCallback(Request $request)
    {
        try {
            $gateway = $request->gateway;
            $reference = $request->reference;
            
            $gatewayService = $this->gatewayFactory->create($gateway);
            $payment = $gatewayService->handleCallback($reference);
            if($payment->owner_type == 'applicant'){
                return redirect(config('default.portal.domain').'application/payments?status=successful');
            }
            return redirect(config('default.portal.domain').'student/payments?status=successful');
        } catch (\Exception $e) {
            dd($e->getMessage());
            if(str_contains($e->getMessage(),'applicant')){
                return redirect(config('default.portal.domain').'application/payments?status=failed');
            }
            return redirect(config('default.portal.domain').'student/payments?status=failed');
            //return response()->json(["error" => $e->getMessage()], 400);
        }
    }


    public function verifyPayment($reference){
        $payment = Payment::where('reference', $reference)->orWhere('gateway_reference', $reference)->first();
        if(!$payment){
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $payment = $payment;
        return response()->json($payment);
    }

    private function handleGatewayPaymentRecord($gateway, $invoice, $payment, $paymentData, $totalAmount, $wallet, $description = 'payment')
    {
        
        try {
            $gatewayService = $this->gatewayFactory->create($gateway);
            $response = $gatewayService->processPayment($invoice, $payment, $paymentData, $totalAmount, $wallet, $description);
            Log::info("Gateway processing response: " . json_encode($response));
            return $response;
        } catch (\Exception $e) {
            Log::error("Gateway processing error: " . $e->getMessage());
            throw $e;
        }
    }

}
