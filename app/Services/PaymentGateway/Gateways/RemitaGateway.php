<?php

namespace App\Services\PaymentGateway\Gateways;

use App\Enums\TransactionStatus;
use App\Jobs\QueueMail;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\PaymentGateway\GatewayInterface;
use App\Services\Remita;
use App\Services\Util;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RemitaGateway implements GatewayInterface
{
    use Main;
    protected $remitaService;

    public function __construct(Remita $remitaService)
    {
        $this->remitaService = $remitaService;
    }

    /**
     * Process payment with Remita gateway
     */
    public function processPayment(
        Collection $invoices,
        Payment $payment,
        array $paymentData,
        float $totalAmount,
        ?Wallet $wallet,
        ?string $description
    ) {
        $invoice = $invoices->count() == 1 ? $invoices->first() : (object)[
            "taxable" => (object) [
                "full_name" => $invoices->first()->issuer->full_name,
                "email" => $invoices->first()->issuer->email,
                "phone_number" => $invoices->first()->issuer->phone_number,
            ],
            "description" => $description
        ];

        $randomEmail = Str::random(10) . '@irs.gm.gov.ng';
        $lineItems = [];
        
        if (Util::getConfigValue('enable_remita_split_payment') == 'true') {
            $lineItems = Util::lineItems($payment, round($totalAmount, 2));
        }
        $payload = [
            'orderId' => $payment->reference,
            'amount' => round($totalAmount, 2),
            'serviceTypeId' => config('remita.service_type'),
            'payerName' => $invoice?->taxable?->full_name,
            'payerEmail' => isValidEmail($invoice?->taxable?->email) 
                ? $invoice?->taxable?->email 
                : ($invoices->first()->issuer->email ?? $randomEmail),
            'payerPhone' => $invoice?->taxable?->phone_number,
            'description' => $description,
            'lineItems' => !is_array($lineItems) ? $lineItems->toArray() : $lineItems,
        ];
         Log::info("Payload : " , $payload); 
        $response = $this->remitaService->generateRRR($payload);
        // Log::info("RRR response: " . json_encode($response));
        
        if ($response['status'] == "INVALID_SERVICE_MERCHANT") {
            throw new \Exception('Failed to generate RRR');
        }else if(!isset($response['RRR'])) {
            abort(400, $response['status']);
        }

        $payment->update(["gateway_reference" => $response["RRR"]]);
        return $response["RRR"];
    }

    /**
     * Handle Remita payment callback
     */
    public function handleCallback(string $reference): Payment
    {
        try{
            $payment = Payment::where('gateway_reference', $reference)->orWhere('reference', $reference)->first();
                $response = $this->verifyTransaction($payment->gateway_reference);

                if ($response['status'] == 'success') {
                    $paymentData = $response['data'];

                    if (isset($paymentData['message']) && $paymentData['message'] == "Successful") {
                        $amount = $paymentData["amount"];
                        //$payment = Payment::where('gateway_reference', $reference)->first();
                        
                        $paymentDate = explode(" ", $paymentData["paymentDate"])[0] ?? Carbon::now()->format('Y-m-d');
                        $time = explode(" ", $paymentData["paymentDate"])[1] ?? Carbon::now()->format('H:i:s');
                        
                        return $this->updatePaymentRecords(
                            $payment->reference,
                            $amount,
                            $paymentData["channel"] ?? '-',
                            0,
                            $paymentDate,
                            $time
                        );
                    } else {
                        $payment = Payment::where('gateway_reference', $reference)->first();

                        if ($payment) {
                            foreach ($payment->taxInvoices as $taxInvoice) {
                                $taxInvoice->update(['payment_status' => 0, 'paid_amount' => 0]);
                            }
                            $payment->update(['status' => 0, 'paid_amount' => 0]);
                        }

                        throw new \Exception('Payment has not been made or is pending');
                    }
                } else {
                    if($response['status'] == 'Pending'){
                        abort(400, 'Payment has not been made or is pending');
                    }

                    $payment = Payment::where('gateway_reference', $reference)->first();
                    if ($payment) {
                        $payment->update(['status' => TransactionStatus::FAILED]);
                    }
                    abort(400, 'Verification failed for reference');
                }
            }catch(\Exception $e){
                Log::error($e->getMessage());
                throw $e;
            }
    }

    /**
     * Handle Remita webhook
     */
    public function handleWebhook(Request $request)
    {
        $response = $request->all()[0];
        Log::info('Remita Webhook Response:', $response);

        // Queue email notification for webhook received
        QueueMail::dispatch(
            ['email' => 'admin@example.com', 'message' => json_encode($request->all())],
            'message',
            'Remita Webhook response'
        );

        $rrr = $response['rrr'] ?? $response->rrr;
        $channel = $response['channel'] ?? $response->channel;

        try {
            $this->handleCallback($rrr, $channel);
            return 'OK';
        } catch (\Exception $e) {
            Log::error("Remita webhook processing error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify Remita transaction
     */
    public function verifyTransaction(string $reference): array
    {
        
        $apiKey = config('remita.api_key');
        $merchantId = config('remita.merchant_id');
        $baseUrl = rtrim(config('remita.remita_verify'), '/'); // ensures no trailing slash

        $hashKey = hash("sha512", $reference . $apiKey . $merchantId);

        $url = "$baseUrl/$merchantId/$reference/$hashKey/status.reg";
        $url = str_replace(['{', '}'], '', $url);
        $response = Http::get($url);
        $paymentData = json_decode($response->body(), true);
        // Log::info("Remita Response:", [ 
        //     $response->successful(),
        //     $response->body()
        // ]);
        if ($response->successful()) {

            if($paymentData['message'] != 'Successful'){
                return [
                    "data" => null,
                    "status" => "Pending"
                ];
            }
            $paymentData["amount"] = $paymentData['amount'];
            $paymentData["paymentDate"] = $paymentData['paymentDate'];

            return [
                "data" => $paymentData,
                "status" => ($paymentData['message'] ?? '') === 'Successful' ? "success" : "failed"
            ];
        }

        return [
            "data" => null,
            "status" => "failed"
        ];
    }

   
}