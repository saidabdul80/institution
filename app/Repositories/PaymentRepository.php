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
        return $this->payment->where('payment_reference', $ref)->orWhere('jtr', $ref)->first();
    }

    public function createPayment($paymentDetails)
    {
        return $this->payment->create([
            "amount" => $paymentDetails['amount'],
            "payment_reference" => $paymentDetails['payment_reference'] ?? null,
            "owner_id" => $paymentDetails['owner_id'],
            "owner_type" => $paymentDetails['owner_type'],
            "session_id" => $paymentDetails['session_id'],
            "jtr" => $paymentDetails['jtr'],
            "payment_mode" => $paymentDetails['payment_mode'],
            "charges" => $paymentDetails['charges'] ?? 0,
        ]);
    }

    public function updatePaymentWithRRR($rrr, array $newDetails)
    {
        return $this->payment->where("payment_reference", $rrr)->update($newDetails);
    }

    public function updatePaymentWithFlutterwave($jtr, array $newDetails)
    {
        return $this->payment->where("jtr", $jtr)->update($newDetails);
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
        $this->payment->where("invoice_id", $invoice_id)->where("status", "!=", "paid")->exists();
    }

    public function isPaymentComplete($invoice_id)
    {
        $this->payment->where("invoice_id", $invoice_id)->where("status", "paid")->exists();
    }

    public function getPaymentByJTR($reference)
    {
        return $this->payment->where("jtr", $reference)->first();
    }

    public function getPaymentByFlutterwave($jtr)
    {
        return $this->payment->where("jtr", $jtr)->first();
    }

    public function getPaymentsByInvoice($invoice_id)
    {
        return $this->payment->where("invoice_id", $invoice_id)->get();
    }
}
