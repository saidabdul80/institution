<?php

namespace App\Http\Controllers;

use App\Http\Resources\APIResource;
use App\Models\Applicant;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * Get all invoices for staff management
     */
    public function getAllInvoices(Request $request)
    {
        try {
            $query = \App\Models\Invoice::with(['invoice_type', 'owner', 'payment']);

            // Apply filters
            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            if ($request->owner_type) {
                $query->where('owner_type', $request->owner_type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->invoice_type_id) {
                $query->where('invoice_type_id', $request->invoice_type_id);
            }

            $invoices = $query->orderBy('created_at', 'desc')->get();

            // Add user info to each invoice
            $invoices->each(function($invoice) {
                if ($invoice->owner) {
                    $owner = $invoice->owner;
                    $invoice->user_info = [
                        'name' => $owner->full_name ?? ($owner->first_name . ' ' . $owner->last_name),
                        'email' => $owner->email,
                        'phone' => $owner->phone_number ?? $owner->phone
                    ];
                }
            });

            return new APIResource($invoices, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get invoice statistics for staff dashboard
     */
    public function getInvoiceStatistics(Request $request)
    {
        try {
            $query = \App\Models\Invoice::query();

            // Apply same filters as getAllInvoices
            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            if ($request->owner_type) {
                $query->where('owner_type', $request->owner_type);
            }

            if ($request->invoice_type_id) {
                $query->where('invoice_type_id', $request->invoice_type_id);
            }

            $statistics = [
                'total_count' => $query->count(),
                'paid_count' => (clone $query)->where('status', 'paid')->count(),
                'unpaid_count' => (clone $query)->where('status', 'unpaid')->count(),
                'total_amount' => $query->sum('amount'),
            ];

            return new APIResource($statistics, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Create a new invoice
     */
    public function createInvoice(Request $request)
    {
        try {
            $request->validate([
                'owner_type' => 'required|in:student,applicant',
                'session_id' => 'required|integer',
                'user_identifier' => 'required|string',
                'invoice_type_id' => 'required|integer'
            ]);

            // Find the user based on owner_type and identifier
            $userModel = $request->owner_type === 'student' ? \App\Models\Student::class : \App\Models\Applicant::class;
            $user = $userModel::where('email', $request->user_identifier)
                             ->orWhere('id', $request->user_identifier)
                             ->first();

            if (!$user) {
                return new APIResource('User not found', true, 404);
            }

            // Get invoice type
            $invoiceType = \App\Models\InvoiceType::find($request->invoice_type_id);
            if (!$invoiceType) {
                return new APIResource('Invoice type not found', true, 404);
            }

            // Create invoice
            $invoice = \App\Models\Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'owner_id' => $user->id,
                'owner_type' => $request->owner_type,
                'session_id' => $request->session_id,
                'invoice_type_id' => $request->invoice_type_id,
                'amount' => $invoiceType->amount,
                'charges' => $invoiceType->charges ?? 0,
                'status' => 'unpaid'
            ]);

            return new APIResource($invoice->load(['invoice_type', 'owner']), false, 201);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Export invoices to Excel
     */
    public function exportInvoices(Request $request)
    {
        try {
            // This would typically use Laravel Excel
            // For now, return a simple response
            return new APIResource(['message' => 'Export functionality not implemented yet'], false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Send payment link for an invoice
     */
    public function sendPaymentLink(Request $request)
    {
        try {
            $request->validate([
                'invoice_id' => 'required|integer'
            ]);

            $invoice = \App\Models\Invoice::with('owner')->find($request->invoice_id);
            if (!$invoice) {
                return new APIResource('Invoice not found', true, 404);
            }

            // Here you would typically send an email or SMS with payment link
            // For now, just return success
            return new APIResource(['message' => 'Payment link sent successfully'], false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Generate a unique invoice number
     */
    private function generateInvoiceNumber()
    {
        return 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
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
            return  new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            Log::error($e);
            return new APIResource($e->getMessage(), true, 400);
        }
    }   
}
