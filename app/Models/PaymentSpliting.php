<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use EloquentFilter\Filterable;

class PaymentSpliting extends Model
{
    use Filterable;
    protected $fillable = ['payment_id','beneficiary_id','share','collected_share','status','transfer_reference'];

    protected $with = ['payment'];
    protected $appends = ['commission','amount'];
    
    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function getCommissionAttribute()
    {
        $paymentAmount = $this->payment?->paid_amount;
        if($paymentAmount == 0){
            return 0;
        }
        return number_format(($paymentAmount * $this->share) / 100, 2);
    }

    public function getAmountAttribute()
    {
        return $this->payment->amount;
      
    }
}
