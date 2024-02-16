<?php

namespace App\Jobs;

use App\Http\Controllers\InvoiceController;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Wallet;
use App\Repositories\InvoiceRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\StudentPortalAPI\Repositories\PaymentRepository;
use Spatie\Activitylog\Contracts\Activity;

class CreateInvoiceApplicant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $applicant;
    public $tenant;

    public function __construct(Applicant $applicant)
    {
        $this->tenant = tenant();
        $this->applicant = $applicant;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $session_id = 8;
        try {

            $this->tenant->run(function () use ($session_id) {
                $query = [
                    "gender" => $this->applicant->gender,
                    "owner_type" => 'applicant',
                    "programme_id" => $this->applicant->programme_id,
                    "programme_type_id" => $this->applicant->programme_type_id,
                    "department_id" => $this->applicant->department_id,
                    "faculty_id" => $this->applicant->faculty_id,
                    "entry_mode_id" => $this->applicant->mode_of_entry_id,
                    "state_id" => $this->applicant->state_id,
                    "lga_id" => $this->applicant->lga_id,
                    "level_id" => $this->applicant->level_id,
                    "country_id" => $this->applicant->country_id,
                    "session_id" => $session_id,
                ];

                $invoiceTypes = InvoiceType::match($query)->where('status', 'Active')->latest()->orderBy('amount', 'DESC')->get();

                if (empty($invoiceTypes)) {
                    // throw new \Exception("Sorry, no payment setup for you yet", 404);
                    Log::info('Missing invoice created', ['no invoice type found for this applicant']);
                    return;
                }

                foreach ($invoiceTypes as &$invoiceType) {
                    $applicantInvoice = DB::table('invoices')
                        ->where(['invoice_type_id' => $invoiceType->id, 'owner_id' => $this->applicant->id, 'session_id' => $session_id, "owner_type" => "applicant", "status" => "paid"])
                        ->first();
                    if (!is_null($applicantInvoice)) {
                        $invoiceType->status = $applicantInvoice->status;
                    } else {
                        $invoiceType->status = 'unpaid';
                    }
                }


                //$invoiceTypes = $this->paymentRepository->getAllPaymentDetails(8, $this->applicant);
                $paymentAmount = Payment::where(['owner_id' => $this->applicant->id, 'owner_type' => 'applicant', 'status' => 'successful'])->sum('amount');

                if ($paymentAmount > 0) {
                    $wallet = Wallet::where('wallet_number', $this->applicant->wallet_number)->first();
                    if (is_null($wallet)) {
                        Log::error('Missing invoice created, no wallet found');
                        return;
                    }
                    $invoiceTypeTotalAmount = 0;

                    foreach ($invoiceTypes as $invoiceType) {
                        if ($invoiceType->status == 'unpaid') {
                            $invoiceTypeTotalAmount += $invoiceType->total_amount;
                        }
                    }

                    $amountLeft = $paymentAmount;
                    foreach ($invoiceTypes as $invoiceType) {
                        if ($invoiceType->status == 'unpaid') {
                            if ($wallet->balance < $invoiceTypeTotalAmount && $amountLeft >= $invoiceType->total_amount) {
                                $amountLeft -= $invoiceType->total_amount;

                                $invoiceDetails = [
                                    'invoice_number' => generateInvoiceNumber(),
                                    'owner_id' => $this->applicant->id,
                                    'owner_type' => 'applicant',
                                    'session_id' => $session_id,
                                    'invoice_type_id' => $invoiceType->id,
                                    'amount' => $invoiceType->amount,
                                    'charges' => $invoiceType->charges(),
                                    'paid_at' => now(),
                                    'status' => 'paid'
                                ];
                                $response  = Invoice::create($invoiceDetails);
                                Log::info('Missing invoice created', [$response]);
                            }
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('error Missing invoice:', $e->getMessage());
        }
    }
}
