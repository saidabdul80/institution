<?php

namespace App\Services\PaymentGateway\Gateways;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Jobs\ProcessPaymentCompletion;
use App\Models\InitiatedPaymentTaxInvoice;
use App\Models\Payment;
use App\Services\Util;
use Illuminate\Auth\Access\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Main
{

     protected function updatePaymentRecords($reference, $amount, $channel, $charges = 0, $paymentDate = null, $time=null)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::where('reference', $reference)->orWhere('gateway_reference', $reference)->firstOrFail();
            $taxInvoices = $payment->taxInvoices;
            $lastInvoice = $taxInvoices[$taxInvoices->count() - 1];

            if ($taxInvoices->count() == 1 && $lastInvoice->payment_status == PaymentStatus::PAID && $payment->status == TransactionStatus::SUCCESSFUL) {
                throw new \Exception('Payment has already been completed');
            }

            $unpaidInvoices = $taxInvoices->filter(fn($invoice) => $invoice->payment_status !== PaymentStatus::PAID);
            if ($unpaidInvoices->isEmpty() && $payment->status == TransactionStatus::SUCCESSFUL) {
                throw new \Exception('Payment has already been completed for all invoices.');
            }

            $payment->update([
                'paid_amount' => $amount,
                'channel' => $channel,
                'status' => TransactionStatus::SUCCESSFUL,
            ]);

            if (count($unpaidInvoices) > 50) {
                ProcessPaymentCompletion::dispatch($payment, $amount, $paymentDate, $time);
            } else {
                Util::processPaymentCompletion($payment, $amount, $paymentDate, $time);
            }

            DB::commit();
            return Payment::with('taxInvoices.taxable')->where('reference', $reference)->orWhere('gateway_reference', $reference)->first();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating payment records: " . $e->getMessage());
            throw $e;
        }
    }

    public function preparePaymentData($gateway, $totalAmount, $owner_type, $owner_id, $description, $client_id, $vaultType, $internalReference, $reference, $rrr) 
    {
        $paymentData = [
            'date' => now()->toDateString(),
            'amount' => $totalAmount,
            'charges' => 0,
            'channel' => '-',
            'status' => TransactionStatus::PENDING,
            'gateway' => $gateway,
            'vault' => $vaultType,
            'owner_type' => $owner_type,
            'owner_id' => $owner_id,
            'description' => $description,
            'client_id' => $client_id,
        ];

        switch ($gateway) {
            case 'paystack':
                $paymentData['reference'] = $internalReference;
                $paymentData['gateway_reference'] = $reference ?? null;
                break;
            case 'remita':
                $paymentData['gateway_reference'] = $rrr ?? null;
                $paymentData['reference'] = $reference ?? $internalReference;
                break;
            default:
                $paymentData['reference'] = $internalReference;
                $paymentData['gateway_reference'] = $reference ?? null;
        }

        return $paymentData;
    }


     public function rollbackPayment($payment){
        InitiatedPaymentTaxInvoice::where("payment_id", $payment->id)->update(['paid_date' => null, 'paid_amount' => null]);
        foreach ($payment->taxInvoices as $taxInvoice) {
            $paidAmount = $taxInvoice->paid_amount >0? ($taxInvoice->paid_amount - $payment->amount) :0;
            $taxInvoice->update(['payment_status' => $paidAmount > 0 ? PaymentStatus::PART_PAID : PaymentStatus::UNPAID, 'paid_amount' => $paidAmount]);
        }
        $payment->update(['status' => 0,'paid_amount' => 0, 'status_rollback' => 1]);
    }
}