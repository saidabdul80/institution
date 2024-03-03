<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantPaymentCategory extends Model
{
    use HasFactory;    
    protected $table = "payment_categories";
    public function __construct() {
        $this->connection ='mysql';
    }
}
