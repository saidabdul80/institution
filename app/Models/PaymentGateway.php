<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

   protected $fillable = [
        'name',
        'charge_amount_flat',
        'charge_amount_percentage',
        'charge_description',
        'position'
    ];  

   static public function getAll($amount)
{
    $amount = (float) $amount; // Cast amount once at the top

    $gateways = self::orderBy('position')->get();

    try {
        $gatewaysWithCharges = $gateways->map(function ($gateway) use ($amount) {
            $gateway->charges = 0;

            return $gateway;
        });
        // ->filter(function ($gateway) {
        //     return strtolower($gateway->name) !== 'etranzact';
        // });

        return $gatewaysWithCharges;

    } catch (\Exception $e) {
        return $gateways->map(function ($gateway) {
            $gateway->charges = 0;
            return $gateway;
        });
    }
}


    static public function getByName($name, $amount)
    {
        $gateway = self::where('name', $name)->first();
        if ($gateway) {
            // Calculate charges based on flat and percentage
            $flatCharge = $gateway->charge_amount_flat;
            $percentageCharge = ($gateway->charge_amount_percentage / 100) * $amount;

            $charge = max($flatCharge, $percentageCharge);
            $charges = min($charge, $gateway->cap_amount);
            $gateway->charges = $charges;
        }

        return $gateway;
    }

}


// static public function charges(float $amount, $gateway): float
// {
//     $g = self::where('name', $gateway)->first();
//     if ($g?->charge_type === 'percentage') {
//         return $amount * ($g->charge_amount / 100);
//     }
//     return $g?->charge_amount ?? 0;
// }
