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

     protected function updatePaymentRecords($payment_reference, $amount, $channel, $charges = 0, $paymentDate = null, $time=null)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::where('payment_reference', $payment_reference)->firstOrFail();
            $taxInvoices = $payment->taxInvoices;
            $lastInvoice = $taxInvoices[$taxInvoices->count() - 1];

            if ($taxInvoices->count() == 1 && $lastInvoice->status == 'paid' && $payment->status == 'successful') {
                throw new \Exception('Payment has already been completed');
            }

            $unpaidInvoices = $taxInvoices->filter(fn($invoice) => $invoice->status !== 'paid');
            if ($unpaidInvoices->isEmpty() && $payment->status == 'successful') {
                throw new \Exception('Payment has already been completed for all invoices.');
            }

            $payment->update([
                'paid_amount' => $amount,
                'channel' => $channel,
                'status' => 'successful',
            ]);
            
            Util::processPaymentCompletion($payment, $amount, $paymentDate, $time);

            DB::commit();
            return Payment::with('taxInvoices.taxable')->where('payment_reference', $payment_reference)->orWhere('payment_reference', $payment_reference)->first();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating payment records: " . $e->getMessage());
            throw $e;
        }
    }

    public function preparePaymentData($gateway, $totalAmount, $owner_type, $owner_id, $description, $invoice, $internalReference, $payment_reference, $rrr) 
    {
        $paymentData = [
            'date' => now()->toDateString(),
            'amount' => $totalAmount,
            'charges' => 0,
            'channel' => '-',
            'status' => 'pending',
            'ourTrxRef' => $internalReference,
            'gateway' => $gateway,
            'owner_type' => $owner_type,
            'owner_id' => $owner_id,
            'description' => $description,
            'invoice_id ' => $invoice->id,
            'session_id' => $invoice->session_id,
        ];
        //Log::info("paymentData : " . json_encode($paymentData));
        return $paymentData;
    }


}