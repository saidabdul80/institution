<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\PaymentMade;
use App\Services\DocumentGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerateVerificationSlip implements ShouldQueue
{
    use InteractsWithQueue;

    private $documentService;

    /**
     * Create the event listener.
     */
    public function __construct(DocumentGenerationService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Handle the event.
     */
    public function handle(InvoicePaid|PaymentMade $event)
    {
        try {
            $invoice = null;
            $payment = null;

            // Get invoice and payment data based on event type
            if ($event instanceof InvoicePaid) {
                $invoice = $event->invoice;
                $payment = $invoice->payments()->where('status', 'successful')->latest()->first();
            } elseif ($event instanceof PaymentMade) {
                $payment = $event->payment;
                $invoice = $payment->invoice;
            }

            if (!$invoice || !$payment) {
                Log::warning('GenerateVerificationSlip: Missing invoice or payment data');
                return;
            }

            // Check if this is an acceptance fee payment for an applicant
            if (!$this->isAcceptanceFeePayment($invoice)) {
                return; // Not an acceptance fee, skip
            }

            // Ensure the payment is successful and for an applicant
            if ($payment->status !== 'successful' || $invoice->owner_type !== 'applicant') {
                return;
            }

            $applicant = $invoice->owner;
            if (!$applicant) {
                Log::warning('GenerateVerificationSlip: Applicant not found for invoice ' . $invoice->id);
                return;
            }

            // Check if applicant is admitted
            if ($applicant->admission_status !== 'admitted') {
                Log::info('GenerateVerificationSlip: Applicant not admitted, skipping verification slip generation');
                return;
            }

            Log::info('GenerateVerificationSlip: Processing acceptance fee payment for applicant ' . $applicant->application_number);

            // Prepare payment data for the template
            $paymentData = [
                'payment_date' => $payment->paid_at ? $payment->paid_at->format('F j, Y') : now()->format('F j, Y'),
                'payment_reference' => $payment->payment_reference,
                'amount' => number_format($payment->amount, 2)
            ];

            // Generate verification slip
            $verificationSlip = $this->documentService->generateVerificationSlip($applicant, $paymentData);

            if (!$verificationSlip) {
                Log::error('GenerateVerificationSlip: Failed to generate verification slip for applicant ' . $applicant->application_number);
                return;
            }

            // Send verification slip via email
            $this->sendVerificationSlipEmail($applicant, $verificationSlip);

            // Log successful generation
            Log::info('GenerateVerificationSlip: Successfully generated and sent verification slip for applicant ' . $applicant->application_number);

        } catch (\Exception $e) {
            Log::error('GenerateVerificationSlip: Error processing verification slip - ' . $e->getMessage(), [
                'event_type' => get_class($event),
                'invoice_id' => $invoice?->id,
                'payment_id' => $payment?->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if the invoice is for acceptance fee payment
     */
    private function isAcceptanceFeePayment($invoice): bool
    {
        // Check if the invoice is for acceptance fee
        // This can be determined by the payment category or invoice type
        $paymentCategory = $invoice->payment_category ?? '';
        $invoiceType = $invoice->invoiceType ?? null;

        // Check by payment category short name
        if ($paymentCategory === 'acceptance_fee') {
            return true;
        }

        // Check by invoice type payment short name
        if ($invoiceType && $invoiceType->payment_short_name === 'acceptance_fee') {
            return true;
        }

        // Check by invoice type payment category
        if ($invoiceType && $invoiceType->paymentCategory && $invoiceType->paymentCategory->short_name === 'acceptance_fee') {
            return true;
        }

        return false;
    }

    /**
     * Send verification slip via email
     */
    private function sendVerificationSlipEmail($applicant, $verificationSlip)
    {
        try {
            $schoolName = \App\Services\Util::getConfigValue('school_name') ?? 'Institution';
            $schoolEmail = \App\Services\Util::getConfigValue('school_email') ?? 'admissions@institution.edu';

            $subject = "Acceptance Fee Payment Confirmed - Next Steps | {$schoolName}";

            $data = [
                'applicant' => $applicant,
                'schoolName' => $schoolName,
                'verificationSlipHtml' => $verificationSlip['html'],
                'currentDate' => now()->format('F j, Y')
            ];

            Mail::send('emails.verification_slip', $data, function($message) use ($applicant, $subject, $schoolName, $schoolEmail) {
                $message->to($applicant->email)
                        ->subject($subject)
                        ->from($schoolEmail, $schoolName . ' Admissions Office');
            });

            Log::info('GenerateVerificationSlip: Verification slip email sent to ' . $applicant->email);

        } catch (\Exception $e) {
            Log::error('GenerateVerificationSlip: Failed to send verification slip email - ' . $e->getMessage());
        }
    }
}
