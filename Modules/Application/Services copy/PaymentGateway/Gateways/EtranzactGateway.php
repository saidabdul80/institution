<?php

namespace App\Services\PaymentGateway\Gateways;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Wallet;
use App\Services\PaymentGateway\GatewayInterface;
use App\Services\Util;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EtranzactGateway implements GatewayInterface
{
    use Main;
    /**
     * Process payment with eTranzact gateway
     */
    public function processPayment(
        Collection $invoices,
        Payment $payment,
        array $paymentData,
        float $totalAmount,
        ?Wallet $wallet,
        ?string $description
    ) {
        $names = explode(' ', $invoices->first()->taxable->full_name ?? 'Customer Name');
        $randomEmail = Str::random(10).'@irs.gm.gov.ng';
        $email = $invoices->first()->taxable->email ?? $randomEmail;

        $data = [
            'amount' => $totalAmount * 100, // Convert to kobo
            'bearer' => 1,
            'callbackUrl' => config('default.portal.backend_base_url')."/api/payment?gateway=etranzact",
            'reference' => $payment->reference,
            'email' => isValidEmail($email) ? $email : $randomEmail,
            'customerFirstName' => $names[0] ?? 'Customer',
            'customerLastName' => $names[1] ?? 'Name',
            'customerPhoneNumber' => $invoices->first()->taxable->phone_number ?? '',
            'channel' => ["Card", "bank", "USSD", "QR", "mobile_money", "bank_transfer"],
            //'serviceCode' => config('default.etranzact.service_code'),
            'currency' => 'NGN',
            'metadata' => [
                'type' => 'normal',
                'order_id' => $invoices->pluck('id')->toArray(),
            ],
            'initializeAccount' => 1,
            'splitConfiguration' => [],
        ];

        // Add split configuration if enabled
        if (Util::getConfigValue('enable_split_payment') == 'true') {
            $lineItems = Util::prepareSubaccounts($payment, round($totalAmount, 2), false, 'etranzact');
            foreach ($lineItems as $item) {
                $data['splitConfiguration'][] = [
                    'accountId' => $item['subaccount'],
                    'splitType' => 0,
                    'splitValue' => intval($item['share']), 
                    'isDefault' => $item['deductFeeFrom'] == 1 ? 1 : 0,
                ];
            }
        }
        //Log::error($data);
        $response = Http::withHeaders([
            'Authorization' => config('default.etranzact.privateKey'),
            'Content-Type' => 'application/json',
        ])->post(config('default.etranzact.base_url') . '/transaction/initialize', $data);

        if ($response->successful()) {
            $responseData = json_decode($response->body(), true);
            if ($responseData['status'] == 200) {
                $payment->update([
                    'gateway_reference' => $responseData['data']['credoReference'],
                    'redirect' => $responseData['data']['authorizationUrl']
                ]);
                return $responseData['data']['authorizationUrl'];
            }
        }

        Log::info("eTranzact initialization failed: ", [$response->json(), $data]);
        throw new \Exception('Failed to initialize eTranzact transaction: ' . $response->body());
    }

    /**
     * Handle eTranzact payment callback
     */
    public function handleCallback(string $reference): Payment
    {
        $response = $this->verifyTransaction($reference);

        if ($response['status'] == 'success') {
            $paymentData = $response['data'];

            if ($paymentData['status'] === 0) { // eTranzact uses 0 for success
                $amount = $paymentData['amount'];
                $payment = Payment::where('gateway_reference', $reference)
                                ->orWhere('reference', $reference)
                                ->firstOrFail();
                
                $paymentDate = Carbon::parse($paymentData['paymentDate'] ?? now())->format('Y-m-d');
                $time = Carbon::parse($paymentData['paymentDate'] ?? now())->format('H:i:s');
                
                return $this->updatePaymentRecords(
                    $payment->reference,
                    $amount,
                    $paymentData['paymentChannel'] ?? 'etranzact',
                    0,
                    $paymentDate,
                    $time 
                );
            } else {
                $this->handleFailedPayment($reference);
                throw new \Exception('Payment failed or was abandoned');
            }
        } else {
            $this->handleFailedPayment($reference);
            throw new \Exception('Verification failed for reference');
        }
    }

    /**
     * Handle eTranzact webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        ///Log::info('eTranzact Webhook Received:', $payload);

        try {
            if (isset($payload['event']) && $payload['event'] == "transaction.successful") {
                $reference = $payload['data']['transRef'];
                return $this->handleCallback($reference);
            }
            
            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            Log::error("eTranzact webhook error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify eTranzact transaction
     */
    public function verifyTransaction(string $reference): array
    {
        $payment = Payment::where('reference', $reference)->orWhere('gateway_reference', $reference)->first(); 
        
        $response = Http::withHeaders([
            'Authorization' => config('default.etranzact.privateKey')
        ])->get(config('default.etranzact.base_url') . "/transaction/{$payment->gateway_reference}/verify");
        
        if ($response->successful()) {
            $paymentData = json_decode($response->body(), true);
            if($paymentData['data']['status'] === 0) {
                $paymentData['data']['amount'] =  $paymentData['data']['transAmount'];
                $paymentData['data']['gateway_reference'] = $paymentData['data']['transRef'];
                $paymentData['data']['paymentDate'] = $paymentData['data']['transactionDate'];
                $paymentData['data']['reference'] = $payment->reference;
                return [
                        "data" => $paymentData['data'] ?? $paymentData,
                        "status" => ($paymentData['data']['status'] ?? $paymentData['status']) === 0 ? "success" : "failed"
                    ];
            }

        }

        return [
            "data" => null,
            "status" => "failed",
            "message" => $response->body()
        ];
    }

   

    /**
     * Handle failed payment
     */
    protected function handleFailedPayment(string $reference): void
    {
        $payment = Payment::where('gateway_reference', $reference)
                         ->orWhere('reference', $reference)
                         ->first();

        if ($payment) {
            $payment->update(['status' => TransactionStatus::FAILED]);
            
            foreach ($payment->taxInvoices as $taxInvoice) {
                $taxInvoice->update([
                    'payment_status' => PaymentStatus::UNPAID,
                    'paid_amount' => 0
                ]);
            }
        }
    }
}