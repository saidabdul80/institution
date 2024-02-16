<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentGateway extends Model
{
    use HasFactory;

    public function tenant_gateway(){
        return $this->hasOne(TenantPaymentGateway::class)->where('tenant_id', auth('api')->user()->tenant_id);
    }
}
