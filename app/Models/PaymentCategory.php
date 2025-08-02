<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentCategory extends Model
{
    use HasFactory, Searchable;

    protected $table = "payment_categories";
    protected $fillable = ["*"];
    public $timestamps = false;

    protected $casts = [
        'charges' => 'decimal:2',
    ];
    // public function __construct($attributes = array())
    // {
    //     parent::__construct($attributes);
    //     $this->connection = "school";        
    // }

    public function scopePaymentId($query,$name){
        $data = $query->where("short_name", $name)->first();
        return $data?->id;
    }

    /**
     * Calculate charges based on amount and charge type
     */
    public function calculateCharges($amount)
    {
        if ($this->charge_type === 'percentage') {
            return ($amount * $this->charges) / 100;
        }

        return $this->charges; // Fixed charge
    }

    /**
     * Get total amount including charges
     */
    public function getTotalAmount($amount)
    {
        return $amount + $this->calculateCharges($amount);
    }

}
