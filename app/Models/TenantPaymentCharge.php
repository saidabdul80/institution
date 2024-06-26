<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TenantPaymentCharge extends Model
{
    use HasFactory;
    public function __construct() {
        $this->connection ="mysql";
    }

    public function convert($amount = 1,$from='USD',$to='NGN'){
       
        if(!Redis::get('dollar_rate')){
            
            $response = Http::withHeaders(["Content-Type"=>"text/plain","apikey"=> "EDlU71Qu2Y0NxPW6BxslxE2ybj30s17u"])->
            get("https://api.apilayer.com/fixer/convert?to=$to&from=$from&amount=$amount");
            
            if($response->failed()){
                Log::error($response['error']['info']);
            }

            if(!isset($response['info']['rate']) ){
                Log::error('unrecognized response format');
            }

            Redis::set('dollar_rate', $response['info']['rate']);
            Redis::expire('dollar_rate',28800 );                 
            
        }

        return Redis::get('dollar_rate');        
    }

    public function charge()
    {
        if ($this->payment_type== 'dollar'){
            $rate = $this->convert() * $this->amount;
        }else{
            $rate = $this->amount;
        }
        return $rate;
    }

    public function resolveCharges($value){
        if ($this->payment_type == 'percent') {
            // Calculate the charge based on a percentage of the value
            return ($this->amount / 100) * $value;
        } elseif ($this->payment_type == 'local') {
            // No conversion needed, charge is in local currency
            return $this->amount;
        } elseif ($this->payment_type == 'dollar') {
            // Convert the charge from dollars to local currency using the conversion rate
            $rate = $this->convert();
            return ($rate * $this->amount);
        }
    
    }
    
    public function standardCharge(){
        return $this->convert($this->standard_charge);
    }
}
