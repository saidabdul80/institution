<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\PaymentMade;
use App\Events\WalletSynced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\AllocationRepository;
use App\Services\PaymentService;
use Exception;
use Illuminate\Support\Facades\Log;

class ActivateHostelAllocation implements ShouldQueue
{
    use InteractsWithQueue;

    public $allocationRepository;
    public $paymentService;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(AllocationRepository $allocationRepository, PaymentService $paymentService)
    {
        $this->allocationRepository = $allocationRepository;
        $this->paymentService = $paymentService;
        Log::info('ActivateHostelAllocation listener created');
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\WalletSynced  $event
     * @return void
     */
    public function handle(WalletSynced $event)
    {
        try
        {
            $tenant = $event->tenant;
            if ($event instanceof WalletSynced) {
                $tenant->run(
                    function () use ($event) {
                        $invoice = $event->invoice->fresh();
                        Log::info('Invoice: ', $invoice->toArray());
                        if ($invoice->status == 'paid' && $invoice->payment_category == 'accommodation_fee') {
                            $allocation_id = $this->paymentService->getAllocationIdByInvoice($invoice->id);
                            Log::info('Allocation ID: ', [$allocation_id]);
                            $this->allocationRepository->updateAllocation($allocation_id, ["status" => "active"]);
                        }
                    }
                );
            }
        }
        catch (Exception $e)
        {
            Log::error($e->getMessage(), $event->invoice->toArray());
        }
    }
}
