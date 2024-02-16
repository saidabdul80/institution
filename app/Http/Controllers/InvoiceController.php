<?php

namespace App\Http\Controllers;

use App\Http\Resources\APIResource;
use App\Models\Applicant;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function getInvoice($invoice_number)
    {
        $invoice = $this->invoiceService->getInvoiceByNumber($invoice_number);
        return new APIResource($invoice, false, 200);
    }

    public function generateInvoice(Request $request)
    {
        try {
            $request->validate([
                "invoice_type_id" => "required",
                "session_id" => "required",                
            ]);
            
            $invoice = $this->invoiceService->generateInvoice($request, $request->user());
            return new APIResource($invoice, false, 200);
        } catch (ValidationException $e) {
            return  new APIResource($e->errors(), true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, $e->getCode());
        }
    }   
}
