<?php 
namespace App\Repositories;


use App\Models\Invoice;

class InvoiceRepository
{
    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function createInvoice($invoiceDetails) 
    {
        return $this->invoice->create($invoiceDetails);
    }

    public function deleteInvoice($invoiceId)
    {
        $this->invoice->where("id", $invoiceId)->update(["deleted_by" => auth('api-staff')->id()]);
        $this->invoice->where("id", $invoiceId)->delete();
    }

    public function getAllInvoices() 
    {
        return $this->invoice->all();
    }

    public function getInvoiceById($invoiceId) 
    {
        return $this->invoice->findOrFail($invoiceId);
    }

    public function invoiceNumberExists($number) 
    {
        return $this->invoice->where("invoice_number", $number)->exists();
    }

    public function updateInvoice($invoiceId, $newDetails) 
    {
        return $this->invoice->where($invoiceId)->update($newDetails);
    }

    public function countPaid($sessionId)
    {
        return $this->invoice->where("status", "paid")->where("session_id", $sessionId)->count();
    }

    public function countUnpaid($sessionId)
    {
        return $this->invoice->distinct()->where("status", "unpaid")->where("session_id", $sessionId)->count(["owner_id"]);
    }

    public function getPaidInvoices($sessionId) 
    {
        return $this->invoice->where("status", "paid")->where("session_id", $sessionId)->with('student')->get();
    }

    public function getUnpaidInvoices($sessionId) 
    {
        return $this->invoice->where("status", "unpaid")->where("session_id", $sessionId)->with('student')->get();
    }

    public function confirmPay($invoiceId) 
    {
        return $this->invoice->where("id", $invoiceId)->update(["status"=>"paid", "confirmed_by" => auth('api-staff')->id()]);
    }

    public function search($invoice_type_id)
    {
        return $this->invoice->where("invoice_type_id", $invoice_type_id)->first();
    }

    public function isAllocationPaid($allocationId, $sessionId)
    {
        return $this->invoice->where("meta_data->allocation_id", $allocationId)->where("session_id", $sessionId)->first();
    }

    public function getInvoiceByNumber($invoice_number)
    {
        return $this->invoice->where("invoice_number", $invoice_number)->first();
    }

    public function getStudentInvoices($student_id, $sessionId)
    {
        return $this->invoice->where("owner_id", $student_id)->where("session_id", $sessionId)->get();
    }

    public function updateStatusByNumber($no, $details)
    {
        return $this->invoice->where("id", $no)->update($details);
    }
}