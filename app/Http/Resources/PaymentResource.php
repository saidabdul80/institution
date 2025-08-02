<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $owner = $this->owner ?? [];
        
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'paid_amount' => $this->paid_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'gateway' => $this->gateway,
            'payment_mode' => $this->payment_mode,
            'owner_type' => $this->owner_type,            
            'owner_id' => $this->owner_id,
            'user_info' => [
                'name' => $owner['fullname'] ?? null,
                'email' => $owner['email'] ?? null,
                'number' => $owner['number'] ?? null,
            ],
            'invoice_id' => $this->invoice_id,
            'session_id' => $this->session_id,
            'description' => $this->description,
            'ourTrxRef' => $this->ourTrxRef,
            'gateway_reference' => $this->gateway_reference,
            'channel' => $this->channel,
            'transaction_id' => $this->transaction_id,
            'payment_date' => $this->payment_date,
            'owner' => $this->owner,
            'payment_category_name' => $this->payment_category_name,
            'invoice' => $this->whenLoaded('invoice', function () {
                return [
                    'id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                    'description' => $this->invoice->description,
                    'amount' => $this->invoice->amount,
                    'status' => $this->invoice->status,
                    'payment_category' => $this->invoice->payment_category,
                ];
            }),
        ];
    }
}
