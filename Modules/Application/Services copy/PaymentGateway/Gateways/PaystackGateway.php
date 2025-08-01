<?php

namespace App\Services\PaymentGateway\Gateways;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\VaultType;
use App\Models\InitiatedPaymentTaxInvoice;
use App\Models\Payment;
use App\Models\PaymentSpliting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaymentGateway\GatewayInterface;
use App\Services\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackGateway implements GatewayInterface
{
    use Main;
    public function processPayment(
        $invoices, 
        Payment $payment, 
        array $paymentData, 
        float $totalAmount, 
        ?Wallet $wallet, 
        ?string $description
    ) {
        $invoice = $invoices->count() == 1 ? $invoices->first() : (object)[
            "taxable" => (object) [
                "full_name" => $invoices->first()->taxable->full_name,
                "email" => $invoices->first()->taxable->email,
                "phone_number" => $invoices->first()->taxable->phone_number,
            ],
            "description" => $description
        ];

        $randomEmail = str::random(10).'@irs.gm.gov.ng';
        $data = [
            'amount' => ceil($totalAmount * 100),
            'reference' => $payment->reference,
            'email' => isValidEmail($invoice?->taxable?->email) ? $invoice?->taxable?->email : $randomEmail,
            'first_name' => $invoice?->taxable?->full_name ?? '',
            'phone' => $invoice?->taxable?->phone_number ?? '',
            'currency' => 'NGN',
            'metadata' => [
                'type' => 'normal',
                'order_id' => $invoices->pluck('id')->toArray(),
            ],
        ];

        if (Util::getConfigValue('enable_split_payment') == 'true') {
            $splitCode = Util::getSplitCode($payment, round($totalAmount, 2));
            $data['split_code'] = $splitCode;
        }
        return Paystack::getAuthorizationUrl($data)->url;
    }

    public function handleCallback(string $reference): Payment
    {
        $response = $this->verifyTransaction($reference);


        if ($response['status'] == 'success') {
             $paymentData = $response['data'];
            if($paymentData['status'] == "success"){
                if ($paymentData['metadata']['type'] == 'wallet') {
                    $this->handleWalletFundingCallback($reference, $paymentData);
                    $payment  = Payment::first();
                } else {
                    $payment = Payment::where('reference', $reference)->first();
                  
                    $amount  = ($paymentData["amount"]);
                    $charges = ( $paymentData["fees"] / 100);
                    $paymentDate = Carbon::parse($paymentData["paidAt"])->format('Y-m-d');
                    $time = Carbon::parse($paymentData["paidAt"])->format('H:i:s');
                    $payment = $this->updatePaymentRecords($reference, $amount, $paymentData["channel"], $charges, $paymentDate, $time);
                    if(getVault($payment->taxInvoices) == VaultType::WALLET){
                        $this->settleReceiverWallet($payment);
                    }
                }
                return $payment;
            }else if($paymentData['status']== "abandoned"){
                $payment = Payment::where('reference', $reference)->first();
                if($payment?->status == 1){
                    $this->rollbackPayment($payment);
                }
                // if ($payment) {
                //     $payment->initiatedPaymentTaxInvoices->update(['paid_date' => null, 'paid_amount' => null]);
                //     foreach ($payment->taxInvoices as $taxInvoice) {
                //         $taxInvoice->update(['payment_status' => 0, 'paid_amount' => 0]);
                //     }
                //     $payment->update(['status' => 0,'paid_amount' => 0]);
                // }
                //$payment->update(['status' => TransactionStatus::FAILED]);
                abort(422,'Payment was abandoned');
            }
        } else {
            $payment = Payment::where('reference', $reference)->first();
            if ($payment) {
                $payment->update(['status' => TransactionStatus::FAILED]);
            }
            abort(422, 'Verification failed for reference');
        }
    }

    private function settleReceiverWallet($payment){
        foreach($payment->taxInvoices as $taxInvoice){
            $wallet = Wallet::getWalletWithUser($taxInvoice->taxable);
            $wallet->credit($taxInvoice->paid_amount, $taxInvoice->description, $payment->reference);
        }
    }

    public function handleWebhook(Request $request)
    {
        $secretKey = config('paystack.secretKey');
        $signature = $request->header('x-paystack-signature');
        $computedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);

        if ($signature !== $computedSignature) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        switch ($event) {
            case 'transfer.success':
                $this->handleTransfer($data, 1);
                break;
            case 'transfer.failed':
                $this->handleTransfer($data, 2);
                break;
            case 'paymentrequest.success':
                // Handle payment request success
                break;
            default:
                Log::info('Unhandled Paystack event: ' . $event);
                break;
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('paystack.secretKey')
        ])->get('https://api.paystack.co/transaction/verify/' . $reference);

        if ($response->successful()) {
            $paymentData = json_decode($response->body(), true);
            if($paymentData['data']['status'] == 'success'){
                $paymentData['data']["amount"] = $paymentData['data']['amount']/100;
                $paymentData['data']['fees'] = $paymentData['data']['fees']/100;
                $paymentData['data']["paymentDate"] = $paymentData['data']['paidAt'];
                return [
                    "data" => $paymentData['data'],
                    "status" => "success"
                ];
            }
        }

        return [
            "data" => null,
            "status" => "failed"
        ];
    }

    protected function handleTransfer(array $data, int $status): void
    {
        $reference = $data['reference'];
        PaymentSpliting::where('transfer_reference', $reference)
            ->update(['status' => $status, 'transfer_reference' => $reference]);
    }

    protected function rollbackPayment(Payment $payment): void
    {
        InitiatedPaymentTaxInvoice::where("payment_id", $payment->id)
            ->update(['paid_date' => null, 'paid_amount' => null]);
            
        foreach ($payment->taxInvoices as $taxInvoice) {
            $paidAmount = $taxInvoice->paid_amount > 0 
                ? ($taxInvoice->paid_amount - $payment->amount) 
                : 0;
                
            $taxInvoice->update([
                'payment_status' => $paidAmount > 0 ? PaymentStatus::PART_PAID : PaymentStatus::UNPAID,
                'paid_amount' => $paidAmount
            ]);
        }
        
        $payment->update([
            'status' => 0,
            'paid_amount' => 0,
            'status_rollback' => 1
        ]);
    }


      public function handleWalletFundingCallback($reference, $paymentData)
    {
        $transaction = WalletTransaction::with('wallet')
            ->where('reference', $reference)
            ->first();
        $wallet = $transaction->wallet;
        
        if ($transaction->status ==  TransactionStatus::SUCCESSFUL) {
            throw new \Exception('Payment Already Completed');
        }

        $funded = DB::transaction(function () use ($wallet, $transaction, $paymentData) {
            if(isset($paymentData['data'])) {
                $paymentData= $paymentData['data'];
            }

            if($paymentData['status'] !== 'success') {
                abort(400, 'Payment Failed');
            }

            $amount =  ($paymentData['amount'])- ($paymentData['fees']);
            // $charges = PaymentGateway::getByName('paystack',$amount)->charges;
            $fundAmount = $amount;
            $wallet->balance += $fundAmount;
            $wallet->total_collection += $fundAmount;
            $wallet->save();

            $transaction->amount = $amount;
            $transaction->status = TransactionStatus::SUCCESSFUL;
            $transaction->channel = $paymentData['channel'];
            $transaction->save();

            return $wallet;
        });

        if ($funded) {
            return response()->json(['message' => 'Wallet funded successfully', 'wallet' => $wallet], 200);
        } else {
            return response()->json(['message' => 'Failed to fund wallet'], 400);
        }
    }

}