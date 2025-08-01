<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\BeneficiariesRepository;
use App\Events\PaymentMade;
use App\Events\InvoicePaid;
use App\Events\WalletFunded;
use App\Http\Controllers\TenantController;
use App\Models\Tenant;
use App\Repositories\AllocationRepository;
use Flutterwave\Config\PackageConfig;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $paymentRepository, $invoiceRepository, $allocationRepository, $beneficiariesRepository;

    public function __construct(PaymentRepository $paymentRepository, InvoiceRepository $invoiceRepository)
    {
        $this->paymentRepository = $paymentRepository;
        $this->invoiceRepository = $invoiceRepository;        
    }

    public function initiatePayment($paymentDetails, $callback_url = null)
    {
        if (isset($paymentDetails['payment_category'])) {
            return $this->initiateInvoicePayment($paymentDetails, $callback_url);
        } else {
            return $this->initiateLoadWallet($paymentDetails, $callback_url);
        }
    }

    public function initiatePay($request, $callback_url = null)
    {
        $paymentDetails = $request->all();
        $paymentDetails['applicant_id'] = $request->user()->id;        
        $paymentDetails['owner_id'] = $request->user()->id;        
        $paymentDetails['email'] = $request->user()->email; 
        $paymentDetails['phone_number'] = $request->user()->phone_number; 
        $paymentDetails['full_name'] = $request->user()->full_name; 
        $paymentDetails['owner_type'] = get_class($request->user()); 
        $paymentDetails['invoice_id'] = $request->invoice_id;
        return $this->initiateInvoicePayment($paymentDetails, $callback_url);
        // if (isset($paymentDetails['payment_category'])) {
        // } else {
        //     return $this->initiateLoadWallet($paymentDetails, $callback_url);
        // }
    }

    public function initiateInvoicePayment($paymentDetails, $callback_url = null)
    {
        try {
            $check_paid = $this->paymentRepository->isPaymentComplete($paymentDetails["invoice_number"]);
            if (!$check_paid) {
                $invoice = $this->invoiceRepository->getInvoiceByNumber($paymentDetails["invoice_number"]);
                if(!$invoice){
                    throw new \Exception('Invoice number is not valid, Create new invoice');
                }

                //percentage resolution;                
                $paymentDetails['amount'] = $invoice->amount + $invoice->charges;
                $paymentDetails['ashlab_charges'] = $invoice->charges;
                $paymentDetails['invoice_id'] = $invoice->id;
                $check_init = $this->paymentRepository->isPaymentExist($paymentDetails["invoice_number"]);
                $paymentDetails['invoice_name'] = $invoice['invoice_name'];
                if (!$check_init) {
                    $pay_mode = $paymentDetails['payment_mode'];
                    $paymentDetails["ourTrxRef"] = $this->generateTrxId();
                    
                    switch ($pay_mode) {
                        case 'remita':
                            $url = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.remita.liveURL') : config('ashlab.payment_gateways.remita.testURL');
                            $rrr = $this->generateStandardRRR($paymentDetails, $url, $callback_url ?? null)["RRR"];
                            $paymentDetails['payment_reference'] = $rrr;
                            break;
                        case 'cbs':
                            $invoice = $this->cbsCreateInvoice($paymentDetails, 'https://nigerigr.com/api/v1/invoice/create');
                            
                            break;
                        case 'flutterwave':                            
                            $response = $this->getInitializeUrlFlutterWave($paymentDetails);
                            $authorization_url = $response->link;
                            $key = explode('pay/',$authorization_url);                        
                            $paymentDetails['payment_reference'] = $key[1];
                            break;
                        case 'paystack':                    
                            $response = $this->getInitializeUrlPayStack($paymentDetails);
                            $authorization_url = $response->data->authorization_url;
                            $paymentDetails['payment_reference'] = $response->data->reference;
                            break;                            
                        default:                                
                            break;
                    }                    
                    $this->paymentRepository->createPayment($paymentDetails);
                    return $authorization_url ?? null;
                } else {
                    return $this->paymentRepository->getPaymentByInvoiceNumber($paymentDetails["id"]);
                }
            } else {
                throw new Exception("This payment has been completed", 303);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 300);
        }
    }

    public function getInitializeUrlFlutterWave($paymentDetails){
        $webhook = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.flutterwave.redirect_url') : config('ashlab.payment_gateways.flutterwave.redirect_url');
        $flutterwave_initialize_url = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.flutterwave.liveUrl') : config('ashlab.payment_gateways.flutterwave.testUrl');
        $flwseck = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.flutterwave.live_secret_key') : config('ashlab.payment_gateways.flutterwave.test_secret_key');            

        $data = [
            "tx_ref"=> $paymentDetails['ourTrxRef'],
            "amount"=> $paymentDetails['amount'],
            "currency"=> "NGN",
            "redirect_url"=> $webhook,
            "meta"=> [
                "consumer_id" => tenant('id'),
                "ashlab_charges" =>$paymentDetails['ashlab_charges'],
//              "consumer_mac" => "92a3-912ba-1192a"
            ],
            "customer"=> [
                'email' => $paymentDetails['email'],
                "phonenumber"=> $paymentDetails['phone_number'],
                "name"=> $paymentDetails['full_name']
            ],
            "customizations"=>[
                "title"=> tenant('school_name').' '.$paymentDetails['invoice_name'],
                "logo"=> tenant('logo')
            ]
            ];        
                
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $flwseck,
            "Cache-Control" => "no-cache",
        ])->post($flutterwave_initialize_url, $data)->body();
        $response = json_decode($response);                    
        if($response->status){
            return $response->data;
            /* $authorization_url = $response->data->authorization_url;
            $transactionDetails['reference'] = $response->data->reference; */
        }else{
            throw new \Exception($response->message);                        
        }
    }

    public function getInitializeUrlPayStack($paymentDetails){
        $data = [
            'email' => $paymentDetails['email'],
            'amount' => $paymentDetails['amount'] * 100,                                    
            'channels' => ["card", "bank_transfer"],                        
        ];

        $paystack_initialize_url = config('ashlab.paystack.initialize');
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . env('PAYSTACK_SECRET_KEY'),
            "Cache-Control" => "no-cache",
        ])->post($paystack_initialize_url, $data)->body();
        $response = json_decode($response);                    
        if($response->status){
            return $response->data;
            /* $authorization_url = $response->data->authorization_url;
            $transactionDetails['reference'] = $response->data->reference; */
        }else{
            throw new \Exception($response->message);                        
        }
    }
    public function initiateLoadWallet($paymentDetails, $callback_url = null)
    {
        try {

            $pay_mode = $paymentDetails['payment_mode'];
            $paymentDetails["ourTrxRef"] = generateTrxId();
            switch ($pay_mode) {
                case 'remita':
                    $url = config('ashlab.payment_gateways.remita.liveURL');
                    $rrr = $this->generateStandardRRR($paymentDetails, $url, $callback_url ?? null)["RRR"];
                    $paymentDetails['payment_reference'] = $rrr;
                    break;
                case 'cbs':
                    $invoice = $this->cbsCreateInvoice($paymentDetails, 'https://nigerigr.com/api/v1/invoice/create');
                    dd($invoice);
                    break;
                case 'flutterwave':
                    Redis::set($paymentDetails['ourTrxRef'], tenant('id'));
                    Redis::expire($paymentDetails['ourTrxRef'], 1440);
                    Redis::set('domain_' . tenant('id'), $paymentDetails['host']);
                    break;
                default:
                    # code...
                    break;
            }
            return $this->paymentRepository->createPayment($paymentDetails);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 300);
        }
    }

    public function pay($invoice_id)
    {
        $invoice = $this->invoiceRepository->getInvoiceById($invoice_id);
        if ($invoice->status == 'paid') {
            return 'Invoice already paid';
        }        
        if ($invoice->owner->wallet->balance >= ($invoice->amount + $invoice->charges) && $invoice->status == 'unpaid') {
            event(new InvoicePaid($invoice));
            return 'Payment Successful';
        }else{
            throw new Exception('Insufficient Balance, please fund your wallet', 300);
        }
    }

    function paymentWebhook($response)
    {
        if (isset($response[0]['rrr'])) {
            $gateway = "remita";
            $tenant_id = Redis::get($response[0]['rrr']);
            $domain = Redis::get('domain_' . $tenant_id);
        } else if (isset($response['data']['flw_ref'])) {
            $gateway = "flutterwave";
            $tenant_id = Redis::get($response['data']['tx_ref']);
            $domain = Redis::get('domain_' . $tenant_id);
        }

        $tenant = Tenant::find($tenant_id);
        if (!$tenant) {
            Log::error('failed_payment_webhook', $response);
            return 'ok';
        }
        $return = '';
        Log::info('tenant' . $tenant_id, $response);
        $tenant->run(function () use ($response, $gateway, $domain, &$return) {
            switch ($gateway) {
                case 'remita':
                    $return =  $this->webhookRemita($response, $domain);
                    break;
                case 'flutterwave':
                    $return = $this->webhookFlutterWave($response);
                    break;
                default:
                    # code...
                    break;
            }
        });

        return $return;
    }

    public function requery($ourTrxRef)
    {
        $payment = $this->getPaymentByourTrxRef($ourTrxRef);        
        switch ($payment?->payment_mode) {
            case 'remita':
                $status = $this->requeryRemita($payment->payment_reference);
                if ($status == 'successful' && $payment->status != 'successful') {                 
                }

                return $status;
                break;
            case 'flutterwave':
                $response = $this->checkPaymentStatusByFlutterwave($ourTrxRef);                
                if ($response->status == 'success') {                    
                    TenantController::updatePaymentRecord($response);                    
                    return $response->status;
                }
                return $response->status;
                break;
            default:
                throw new Exception("Payment Gateway not supported", 303);
                break;
        }
    }

    public function getPaymentByInvoiceID($invoice_id)
    {
        return $this->paymentRepository->getByInvoiceId($invoice_id);
    }

    public function getPaymentByourTrxRef($ref)
    {
        return $this->paymentRepository->getPaymentByourTrxRef($ref);
    }

    private function school_info($host)
    {
        return Http::withHeaders(["xtenant" => $host])->get('https://api.jspnigeria.com/api/school-info')->json();
    }


    private function generateTrxId()
    {
        $number = "ourTrxRef" . date("ymdhis") . rand(100, 999);
        return $number;
    }

    private function cbsCreateInvoice($details, $host)
    {
        $clientId = 'ZOrwu2CGlteL4ldRJTH2ABep2qYKIReok0i6fxSpjY8=';
        $secretKEY = 'aA+JvkOjJxjliqcYDvUQrVvbvGYFWubZSOD32UCYVwrgAEesv1ZaG5cBPr9+';
        $revenueHeadId = 3539;
        $callbackurl = "http://jsp-fw.test/hostelportal/students/cbs-webhook";
        $signature = hash_hmac('sha256', $revenueHeadId . str_replace(',', "", number_format($details['amount'], 2)) . $callbackurl . $clientId, $secretKEY, true);
        $signature = base64_encode($signature);
        //dd($signature);
        $headers = ["SIGNATURE" => $signature, "CLIENTID" => $clientId];
        $requestBody = [
            "RevenueHeadId" => $revenueHeadId,
            "TaxEntityInvoice" => [
                "TaxEntity" => [
                    "Recipient" => $details['full_name'],
                    "Email" => $details['email'],
                    "Address" => "API local",
                    "PhoneNumber" => "0804832361",
                    "TaxPayerIdentificationNumber" => 7777711,
                    "RCNumber" => null,
                    "PayerId" => null,
                ],
                "Amount" => str_replace(',', "", number_format($details['amount'], 2)),
                "InvoiceDescription" => "Hostel Fee payment invoice",
                "AdditionalDetails" => [[
                    "matric_number" => $details['matric_number'],
                    "invoice_id" => $details['invoice_id'],
                ]],
                "CategoryId" => 1
            ],
            "RequestReference" => $details['ourTrxRef'],
            "CallBackURL" => $callbackurl
        ];
        $request = Http::withHeaders($headers)->post($host, $requestBody)->body();
        return $request;
    }


    private function percentage($no, $amount)
    {
        return ($no / 100) * $amount;
    }

    public function generateStandardRRR($details, $host, $callback_url = null)
    {
        $school_info = $this->school_info($details['host']);
        $remita = $this->getGatewayByName('remita', $details['host']);
        $merchantId = $remita['extra']['merchant_id'];
        $serviceTypeId = $remita['extra']['service_type_id'];
        $apiKey = $remita['extra']['api_key'];
        $hash = hash('sha512', $merchantId . $serviceTypeId . $details['ourTrxRef'] . $details['amount'] . $apiKey);
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $hash,
        ];
        $request = Http::withHeaders($headers)->post($host . "echannelsvc/merchant/api/paymentinit", [
            "serviceTypeId" => $serviceTypeId,
            "amount" => $details['amount'],
            "orderId" => $details['ourTrxRef'],
            "responseurl" => 'https://api.jspnigeria.com/payments/webhook',
            "payerName" => $details['full_name'],
            "payerEmail" => $details['email'],
            "payerPhone" => $details['phone_number'],
            "description" => "Fund tespire wallet of " . $details['full_name'] . ", " . $details['phone_number'],

        ])->body();

        $resp_with_br = str_replace("jsonp ", "", $request);
        $resp_with_1br = str_replace("(", "", $resp_with_br);
        $resp = str_replace(")", "", $resp_with_1br);
        $response = json_decode($resp, true);
        if (!isset($response['RRR'])) {
            throw new Exception(json_encode($response), 400);
        }
        Redis::set($response['RRR'], $school_info['id']);
        Redis::expire($response['RRR'], 86400);
        Redis::set('domain_' . $school_info['id'], $details['host']);
        return $response;
    }

    public function generateSplitRRR($details, $host, $callback_url = null)
    {
        $school_info = $this->school_info($details['host']);
        // $beneficiaries = $this->beneficiariesRepository->getByTenantId($school_info['id']);
        $beneficiaries = $school_info['beneficiaries'];
        $lineItems = [];
        $total_beneficiaries_amount = 0;
        foreach ($beneficiaries as $beneficiary) {
            if ($beneficiary['commission_type'] == 'percentage') {
                $beneficiary_amount = $this->percentage($beneficiary['commission_amount'], $details['amount']);
            } else {
                $beneficiary_amount = $beneficiary['commission_amount'];
            }
            if ($beneficiary['type'] != "main") {
                $lineItem = [
                    "lineItemsId" => $beneficiary['id'],
                    "beneficiaryName" => $beneficiary['first_name'] . ' ' . $beneficiary['middle_name'] . ' ' . $beneficiary['surname'],
                    "beneficiaryAccount" => $beneficiary['account_number'],
                    "bankCode" => $beneficiary['bank_code'],
                    "beneficiaryAmount" => $beneficiary_amount,
                    "deductFeeFrom" => "0"
                ];

                $total_beneficiaries_amount += $beneficiary_amount;
                $lineItems[] = $lineItem;
            }
        }

        $main_beneficiary = collect($beneficiaries)->where('type', 'main')->first();
        $lineItems[] = [
            "lineItemsId" => $main_beneficiary['id'],
            "beneficiaryName" => $main_beneficiary['first_name'] . ' ' . $main_beneficiary['middle_name'] . ' ' . $main_beneficiary['surname'],
            "beneficiaryAccount" => $main_beneficiary['account_number'],
            "bankCode" => $main_beneficiary['bank_code'],
            "beneficiaryAmount" => ($details['amount'] - $total_beneficiaries_amount),
            "deductFeeFrom" => "1"
        ];
        /*$staticLineItems = [
            [
               "lineItemsId" => "itemid1",
               "beneficiaryName" => "Alozie Michael",
               "beneficiaryAccount" => "6020067886",
               "bankCode" => "058",
               "beneficiaryAmount" => $ninetyPer,
               "deductFeeFrom" => "1"
            ],
            [
               "lineItemsId" => "itemid2",
               "beneficiaryName" => "Folivi Joshua",
               "beneficiaryAccount" => "0360883515",
               "bankCode" => "058",
               "beneficiaryAmount" => $tenPer,
               "deductFeeFrom" => "0"
            ]
            ]; */
        //$tenPer = (10/100 * $details['amount']);
        //$ninetyPer = $details['amount'] - $tenPer;
        $remita = $this->getGatewayByName('remita', $details['host']);
        $merchantId = $remita['extra']['merchant_id'];
        $serviceTypeId = $this->getGatewayPaymentCategories('remita', $details['payment_category'], $details['host']);
        $apiKey = $remita['extra']['api_key'];
        $hash = hash('sha512', $merchantId . $serviceTypeId . $details['ourTrxRef'] . $details['amount'] . $apiKey);
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $hash,
        ];
        $request = Http::withHeaders($headers)->post($host . "echannelsvc/merchant/api/paymentinit", [
            "serviceTypeId" => $serviceTypeId,
            "amount" => $details['amount'],
            "orderId" => $details['ourTrxRef'],
            "responseurl" => $callback_url ?? 'https://api.jspnigeria.com/payments/webhook',
            "payerName" => $details['full_name'],
            "payerEmail" => $details['email'],
            "payerPhone" => "08036635543",
            "customFields" => [
                [
                    "matric_number" => isset($details['matric_number']) ? $details['matric_number'] : $details['application_number'],
                    "invoice_id" => $details['invoice_id'],
                ]
            ],
            "lineItems" => $lineItems
        ])->body();
        $resp_with_br = str_replace("jsonp ", "", $request);
        $resp_with_1br = str_replace("(", "", $resp_with_br);
        $resp = str_replace(")", "", $resp_with_1br);
        //dd($total_beneficiaries_amount);
        //dd($lineItems);
        //dd(json_decode($resp, true));
        $response = json_decode($resp, true);
        if (!isset($response['RRR'])) {
            throw new Exception($response['statusMessage'], 400);
        }
        Redis::hset($response['RRR'], 'tenant_id', $school_info['id']);
        Redis::set('domain_' . $school_info['id'], $details['host']);
        return $response;
    }

    private function getGatewayByName($name, $domain)
    {
        $gateways = $this->school_info($domain);
        $gateways = collect($gateways['payment_gateways']);
        $gateway = $gateways->where('payment_gateway', $name)->first();
        return $gateway;
    }

    private function getGatewayPaymentCategories($name, $category, $host)
    {
        $gateways = $this->school_info($host);
        $gateways = collect($gateways['payment_gateways']);
        $gateway = $gateways->where('payment_gateway', $name)->first();
        $categories = $gateway['payment_categories'];
        return $categories[$category];
    }
    private function checkPaymentStatusByRRR($rrr)
    {
        $school_id = Redis::get($rrr);
        $host = Redis::get('domain_' . $school_id);
        $remita = $this->getGatewayByName('remita', $host);
        $merchantId = $remita['extra']['merchant_id'];
        // $serviceTypeId = $remita['extra']['service_type_id'];
        $apiKey = $remita['extra']['api_key'];
        $hash = hash('sha512', $rrr . $apiKey . $merchantId);
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $hash,
        ];
        $url = config('ashlab.payment_gateways.remita.liveURL');
        $response = Http::withHeaders($headers)->get($url . 'echannelsvc/' . $merchantId . '/' . $rrr . '/' . $hash . '/status.reg')->json();
        // var_dump($response);
        return $response['status'];
    }

    public function checkPaymentStatusByFlutterwave($ourTrxRef)
    {

        $flwseck = env('APP_ENV') == 'production' ? config('ashlab.payment_gateways.flutterwave.live_secret_key') : config('ashlab.payment_gateways.flutterwave.test_secret_key');            
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' .$flwseck
        ])->get("https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=$ourTrxRef")->body();
        $response = json_decode($response);
        
        if ($response->status != 'success') {
            //Log::error($response, ['tx_ref' => $ourTrxRef, 'environment' => env('APP_ENV'), 'key' => config('ashlab.payment_gateways.flutterwave.live_secret_key')]);
            return json_decode(json_encode(['status' => 'pending']));
        }
        return $response;
    }

    public function getPaymentByFlutterwave($ourTrxRef)
    {
        return $this->paymentRepository->getPaymentByFlutterwave($ourTrxRef);
    }

    public function getInvoiceTransactions($invoice_id)
    {
        return $this->paymentRepository->getPaymentsByInvoice($invoice_id);
    }

    public function requeryFlutterWave($payment_reference)
    {
    }

    public function requeryRemita($payment_reference)
    {
        //temporarily add coeminna tenant id(5) to enable requery of payments
        $school_id = Redis::get($payment_reference) ?? tenant('id');
        $host = Redis::get('domain_' . $school_id);
        $remita = $this->getGatewayByName('remita', $host);
        $merchantId = $remita['extra']['merchant_id'];
        $serviceTypeId = $remita['extra']['service_type_id'];
        $apiKey = $remita['extra']['api_key'];
        $hash = hash('sha512', $payment_reference . $apiKey . $merchantId);
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $hash,
        ];
        $url = config('ashlab.payment_gateways.remita.liveURL');
        $response = Http::withHeaders($headers)->get($url . 'echannelsvc/' . $merchantId . '/' . $payment_reference . '/' . $hash . '/status.reg')->json();
        $payment = [
            "payment_mode" => "remita",
        ];
        switch ($response['status']) {
            case '00':
                $payment["status"] = "successful";
                $payment["paid_at"] = Carbon::parse($response['transactiontime'])->format('Y-m-d h:i:s');
                // $payment["payment_channel"] = $response['channel'];
                $payment["gateway_response"] = json_encode($response);
                break;
            case '01':
                $payment["status"] = "successful";
                $payment["paid_at"] = Carbon::parse($response['transactiontime'])->format('Y-m-d h:i:s');
                // $payment["payment_channel"] = $response['channel'];
                $payment["gateway_response"] = json_encode($response);
                break;
            case '021':
                $payment["status"] = "pending";
                $payment["gateway_response"] = json_encode($response);

                break;
            default:
                $payment["status"] = "failed";
                $payment["gateway_response"] = json_encode($response);
                // $payment["paid_at"] = Carbon::parse($response['transactiontime'])->format('Y-m-d h:i:s');

                break;
        }

        $this->paymentRepository->updatePaymentWithRRR($payment_reference, $payment);
        return $payment['status'];
    }

    public function webhookRemita($response, $domain = null)
    {
        $rrr = $response[0]['rrr'];
        $status = $this->checkPaymentStatusByRRR($rrr);
        $payment = $this->getPaymentByourTrxRef($rrr);
        $channel = $response[0]['channel'];
        $payment_details = [
            "payment_mode" => "remita",
            "payment_channel" => $channel,
            "gateway_response" => json_encode($response),
            "paid_at" => Carbon::parse($response[0]['transactiondate'])->format('Y-m-d h:i:s'),
        ];
        if (($status == '00' || $status == '01') && $payment->status != 'successful') {
            $payment_details["status"] = "successful";
            $payment_details["charges"] = $response[0]['chargeFee'];
            $this->paymentRepository->updatePaymentWithRRR($rrr, $payment_details);
            event(new WalletFunded($payment));
            return 'ok';
        } else if ($status == '021') {
            $payment_details["status"] = "pending";
        } else {
            $payment_details["status"] = "failed";
        }

        $this->paymentRepository->updatePaymentWithRRR($rrr, $payment_details);
        return 'ok';
    }

    public function webhookFlutterWave($response, $domain = null)
    {

        $status = $this->checkPaymentStatusByFlutterwave($response['data']['tx_ref'])['data']['status'] ?? 'pending';

        $payment_reference = $response['data']['flw_ref'];
        $ourTrxRef = $response['data']['tx_ref'];
        $channel = $response['data']['payment_type'];
        $payment = $this->getPaymentByFlutterwave($ourTrxRef);
        if ($status == 'successful' && $payment->status == 'pending') {
            event(new WalletFunded($payment));
        }
        $payment->payment_channel = $channel;
        $payment->payment_reference = $payment_reference;
        $payment->status = $status;
        $payment->gateway_response = json_encode($response);
        $payment->paid_at = Carbon::parse($response['data']['created_at'])->format('Y-m-d h:i:s');
        $payment->charges = $response['data']['app_fee'];

        $payment->save();

        return 'ok';
    }

    function getAllocationIdByInvoice($invoice_id)
    {
        $invoice = $this->invoiceRepository->getInvoiceById($invoice_id);
        $meta_data = json_decode($invoice->meta_data);
        $allocation_id = $meta_data->allocation_id;
        return $allocation_id;
    }
}
