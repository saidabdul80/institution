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
    // public function __construct($attributes = array())
    // {
    //     parent::__construct($attributes);
    //     $this->connection = "school";        
    // }

    public function scopePaymentId($query,$name){
        $data = $query->where("short_name", $name)->first();
        return $data?->id;
    }

  
}
