<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    protected $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function getByInvoiceId($invoice_id)
    {
        return $this->payment->where(['invoice_id' => $invoice_id, 'status' => 'successful'])->with('invoice')->first();
    }

    public function getByRef($ref)
    {
        return $this->payment->where('payment_reference', $ref)->orWhere('ourTrxRef', $ref)->first();
    }

    public function createPayment($paymentDetails)
    {

        $response = $this->payment->create([
            "amount" => $paymentDetails['amount'],
            "invoice_id"=>$paymentDetails['invoice_id'],
            "payment_reference" => $paymentDetails['payment_reference'] ?? null,
            "owner_id" => $paymentDetails['owner_id'],
            "owner_type" => $paymentDetails['owner_type'],
            "session_id" => $paymentDetails['session_id'],
            "ourTrxRef" => $paymentDetails['ourTrxRef'],
            "payment_mode" => $paymentDetails['payment_mode'],
            "charges" => $paymentDetails['charges'] ?? 0,
        ]);
        return $response;        
    }

    public function updatePaymentWithRRR($rrr, array $newDetails)
    {
        return $this->payment->where("payment_reference", $rrr)->update($newDetails);
    }

    public function updatePaymentWithFlutterwave($ourTrxRef, array $newDetails)
    {
        return $this->payment->where("ourTrxRef", $ourTrxRef)->update($newDetails);
    }

    public function deletePayment($paymentId)
    {
        $this->payment->where("id", $paymentId)->update(["deleted_by" => auth('api:staffhostel')->id()]);
        $this->payment->where("id", $paymentId)->delete();
    }

    public function getAllPayments()
    {
        return $this->payment->all();
    }

    public function getPaymentById($paymentId)
    {
        return $this->payment->findOrFail($paymentId);
    }

    public function getPaymentByInvoiceNumber($invoice_id)
    {
        return $this->payment->where("invoice_id", $invoice_id)->latest()->first();
    }

    public function isPaymentExist($invoice_id)
    {
        $this->payment->where("invoice_id", $invoice_id)->where("status", "!=", "successful")->exists();
    }

    public function isPaymentComplete($invoice_id)
    {
        return $this->payment->where("invoice_id", $invoice_id)->where("status", "successful")->exists();
    }

    public function getPaymentByourTrxRef($reference)
    {
        return $this->payment->where("ourTrxRef", $reference)->first();
    }

    public function getPaymentByFlutterwave($ourTrxRef)
    {
        return $this->payment->where("ourTrxRef", $ourTrxRef)->first();
    }

    public function getPaymentsByInvoice($invoice_id)
    {
        return $this->payment->where("invoice_id", $invoice_id)->get();
    }
}
