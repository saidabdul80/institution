<?php

namespace App\Services\PaymentGateway\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaymentGateway\GatewayInterface;
use App\Enums\TransactionStatus;
use App\Enums\WalletTransactionType;
use App\Enums\VaultType;
use App\Services\Util;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WalletGateway implements GatewayInterface
{
    use Main;
    /**
     * Process payment using wallet balance
     */
    public function processPayment(
        Collection $invoices,
        Payment $payment,
        array $paymentData,
        float $totalAmount,
        ?Wallet $wallet,
        ?string $description
    ) {
        if ($invoices->count() == 1 && $invoices[0]->payment_status == PaymentStatus::PART_PAID) {
            $totalAmount -= $invoices[0]->paid_amount;
        }

        try {
            DB::beginTransaction();

            if ($paymentData['vault'] == VaultType::WALLET) {
                $this->updatePaymentRecords($payment->reference, $totalAmount, 'wallet', 0, Carbon::now()->format('Y-m-d'));
                $this->settleReceiverWallet($payment);
            } else {
                $this->updatePaymentRecords($payment->reference, $totalAmount, 'wallet', 0, Carbon::now()->format('Y-m-d'));
                if (config('enable_split_payment') == 'true' && $payment->vault == VaultType::MAIN) {
                    Util::getSplitCode($payment, $totalAmount);
                }
            }

            $wallet->debit($totalAmount, 'Used on Invoice', $paymentData['reference']);
            
            DB::commit();
            
            return "/completed";
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Wallet payment error: " . $e->getMessage());
            throw new \Exception("Error processing wallet payment: " . $e->getMessage());
        }
    }


    /**
     * Handle wallet webhook (not typically used for wallet payments)
     */
    public function handleWebhook(Request $request)
    {
        // Wallet payments don't typically use webhooks
        return response()->json(['message' => 'Webhooks not supported for wallet payments'], 400);
    }

    /**
     * Verify wallet transaction
     */
    public function verifyTransaction(string $reference): array
    {
        $payment = WalletTransaction::where('reference', $reference)
            ->where('type', WalletTransactionType::DEBIT)
            ->where('status', TransactionStatus::SUCCESSFUL)
            ->first();

        if (!$payment) {
            return [
                "data" => null,
                "status" => "failed",
                "message" => "Payment not found"
            ];
        }

        return [
            "data" => [
                "status" => $payment->status == TransactionStatus::SUCCESSFUL ? "success" : "failed",
                "amount" => $payment->amount,
                "date" => $payment->date,
                "reference" => $payment->reference
            ],
            "status" => $payment->status == TransactionStatus::SUCCESSFUL ? "success" : "failed"
        ];
    }

    /**
     * Credit receiver wallets when payment vault is WALLET type
     */
    protected function settleReceiverWallet(Payment $payment): void
    {
        foreach ($payment->taxInvoices as $taxInvoice) {
            $wallet = Wallet::getWalletWithUser($taxInvoice->taxable);
            $wallet->credit($taxInvoice->paid_amount, $taxInvoice->description, $payment->reference);
        }
    }

    public function handleCallback(string $reference): Payment
    {
        $paystackGateway = new PaystackGateway;
        $response = $paystackGateway->verifyTransaction($reference);
       Log::info('wallet',$response);
        if ($response['status'] == 'success') {
             $paymentData = $response['data'];
            if($paymentData['status'] == "success"){
                if ($paymentData['metadata']['type'] == 'wallet') {
                    $payment = $this->handleWalletFundingCallback($reference, $paymentData);
                } 
                abort(400, 'Payment Failed');
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
            $response = $this->verifyTransaction($reference);
            if ($response['status'] == 'success') {
                   $paymentData = $response['data'];
                   if($paymentData['status'] == "success"){
                    $payment =   $this->updatePaymentRecords($reference, $paymentData['amount'], 'wallet', 0,$paymentData['date']); 
                    return $payment;
                   }
            }
            // $payment = Payment::where('reference', $reference)->first();
            // if ($payment) {
            //     $payment->update(['status' => TransactionStatus::FAILED]);
            // }
            abort(422, 'Verification failed for reference');
        }
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
             abort(200,'Wallet funded successfully');
        } else {
             abort(400, 'Failed to fund wallet');
        }
    }



    public function handleWalletPayment($invoices, $paymentData, $totalAmount, $wallet) {
        // if ($invoices->count() == 1 && $invoices[0]->payment_status == PaymentStatus::PART_PAID) {
        //     $totalAmount -= $invoices[0]->paid_amount;
        // }
        try {
        
            DB::beginTransaction();
                $paymentDate = Carbon::now()->format('Y-m-d');
                $time = Carbon::now()->format('H:i:s');
                if($paymentData['vault'] == VaultType::WALLET){
                    // $this->settleReceiverWallet($paymentData);
                    abort(400,'Wallet payments are only supported with paystack');
                }else{
                    $payment = $this->updatePaymentRecords($paymentData['reference'], $totalAmount, 'wallet',0,$paymentDate, $time );
                    if(Util::getConfigValue('enable_split_payment') == 'true' && $payment->vault == VaultType::MAIN){
                        Util::getSplitCode($payment, $totalAmount); //this will create settlement record that will later transfer money
                    }
                }

                $wallet->debit($totalAmount, 'Used on Invoice', $paymentData['reference']);
            DB::commit();
        return "/completed";
        } catch (\Exception $e) {
            DB::rollBack();
            abort(400,"Error: PC-,". $e->getMessage());
        }
    }
 
}