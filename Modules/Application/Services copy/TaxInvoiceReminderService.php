<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\Status;
use App\Models\CorporateTaxPayer;
use App\Models\IndividualTaxPayer;
use App\Models\TaxInvoice;
use App\Notifications\UnpaidInvoiceReminderNotification;
use App\Services\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class TaxInvoiceReminderService
{
    public static function sendReminders()
    {
        // Retrieve reminder interval from configuration
        $intervalDays = Util::getConfigValue('reminder_interval');
        
        // Get the date threshold for unpaid invoices
        $dateThreshold = Carbon::now()->subDays($intervalDays);

        // Send reminders for unpaid invoices to each taxpayer model
        self::sendRemindersForUnpaidInvoices(CorporateTaxPayer::class, $dateThreshold);
        self::sendRemindersForUnpaidInvoices(IndividualTaxPayer::class, $dateThreshold);
    }

    protected static function sendRemindersForUnpaidInvoices($taxpayerClass, $dateThreshold)
    {
        // Fetch all taxpayers with unpaid invoices past the threshold
        $taxpayers = $taxpayerClass::whereHas('taxInvoicesAsTaxable', function ($query) use ($dateThreshold) {
            $query->where('status',  PaymentStatus::UNPAID)
                  ->where('updated_at', '<=', $dateThreshold);
        })->get();

        // Send notification to each taxpayer
        foreach ($taxpayers as $taxpayer) {
            // Filter the unpaid invoices for this taxpayer
            $unpaidInvoices = $taxpayer->taxInvoicesAsTaxable()->where('status',  PaymentStatus::UNPAID)->get();
            
            // Trigger notification to each taxpayer with the list of unpaid invoices
            $taxpayer->notify(new \App\Notifications\UnpaidInvoiceReminder($unpaidInvoices));
        }
    }

    /**
     * Sends reminders for unpaid invoices.
     *
     * @param  string|null  $startDate
     * @param  string|null  $endDate
     * @return void
     */
    public function unpaidInvoicesReminder($startDate = null, $endDate = null)
    {
        // Set up query with 'reminder = 1' and 'status' for unpaid invoices
        $query = TaxInvoice::where('reminder', 1)->where('status', PaymentStatus::UNPAID);

        // Apply date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }else{
            $query->whereDate('created_at','<', Carbon::now()->addMonth(2));
        }

        // Retrieve invoices that match the criteria
        $invoices = $query->get();

        // Send notifications to each taxpayer for the retrieved invoices
        foreach ($invoices as $invoice) {
            $taxpayer = $invoice->taxable;  // Assumes 'taxable' relation is the taxpayer

            Notification::send($taxpayer, new UnpaidInvoiceReminderNotification($invoice));
        }
    }
}
