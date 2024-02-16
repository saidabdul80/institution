<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CertificateType extends Model
{
    use HasFactory;
    protected $table = "certificate_types";

    protected $fillable = [];
    
    protected static function newFactory()
    {
      //  return \Modules\ApplicationPortalAPI\Database\factories\CertificateTypeFactory::new();
    }
}
