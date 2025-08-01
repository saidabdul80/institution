<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\InvoiceType;
use App\Models\Payer;
use App\Models\RevenueSource;
use App\Models\Staffer;
use App\Models\TaxInvoice;
use App\Models\Ticket;
use App\Models\TicketCollection;
use App\Models\Vendor;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketPurchaseService
{
    public function purchase(array $data, $purchaser)
    {
        return DB::transaction(function () use ($data, $purchaser) {

            $filter = ['revenue_sub_head' => $data['revenue_sub_head_id']];

            $ward = null;
            if (!empty($data['ward_id'])) {
                $ward = Ward::find($data['ward_id']);
                $filter['revenue_type_category'] = $ward?->region_type;
            }

            $invoiceType = InvoiceType::filter($filter)->first();
            if (!$invoiceType) {
                abort(400, 'Invoice Type not found');
            }

            // Prepare ticket data
            $ticketData = $this->prepareTicketData($data, $purchaser, $invoiceType, $ward);
            $ticket = Ticket::create($ticketData);

            $payer = Payer::createPayer([
                'first_name' => $purchaser->full_name,
                'phone_number' => $purchaser->phone_number,
                'email' => $purchaser->email,
            ]);

            TaxInvoice::createInvoice([
                'due_date' => now()->toDateString(),
                'description' => $ticketData['quantity'] . ' Quantity',
                'variables' => '',
                'invoice_type_id' => $ticketData['invoice_type_id'],
                'template' => '(' . $invoiceType->template . ')*' . $ticketData['quantity'],
                'related_type' => 'ticket',
                'related_id' => $ticket->id,
                'revenue_sub_head_id' => $ticketData['revenue_sub_head_id'],
                'ward_id' => $ticketData['ward_id'] ?? null,
                'amount' => $ticket->total_amount,
            ], $purchaser, $payer, true);

           /// $this->createTicketCollections($ticket, $purchaser, $ward);

            return $ticket;
        });
    }

    private function prepareTicketData(array $data, $purchaser, $invoiceType, $ward = null): array
    {
        $amount = (float) $invoiceType->template;
        $quantity = (int) $data['quantity'];

        return [
            'code' => $ward?->name ?? 'GEN',
            'total_ticket' => $quantity,
            'total_amount' => $amount * $quantity,
            'revenue_sub_head_id' => $data['revenue_sub_head_id'],
            'ticket_date' => Carbon::now()->format('Y-m-d'),
            'template' => $invoiceType->template,
            'status' => TicketStatus::UNPAID,
            'purchaser_type' => get_class($purchaser),
            'purchaser_id' => $purchaser->id,
            'invoice_type_id' => $invoiceType->id,
            'quantity' => $quantity,
            'description' => $data['description'] ?? null,
            'ward_id' => $data['ward_id'] ?? null,
        ];
    }

    private function createTicketCollections(Ticket $ticket, $purchaser, $ward = null): void
    {
        $collections = [];
        $unitAmount = $ticket->total_amount / $ticket->total_ticket;
        $prefix = substr($ward?->name ?? 'TX', 0, 2);

        for ($i = 0; $i < $ticket->total_ticket; $i++) {
            $collections[] = [
                'ticket_id' => $ticket->id,
                'generator_type' => get_class($purchaser),
                'generator_id' => $purchaser->id,
                'allocated_to_type' => $purchaser instanceof Staffer ? get_class($purchaser) : null,
                'allocated_to_id' => $purchaser instanceof Staffer ? $purchaser->id : null,
                'amount' => $unitAmount,
                'ticket_number' => $ticket->id . '/' . $prefix . '/' . strtoupper(Str::random(3)),
                //'generated_date_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TicketCollection::insert($collections);
    }
}
