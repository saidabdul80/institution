<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["name"];
    public $timestamps = false;
    
    protected static function newFactory()
    {
        return \Modules\ApplicationPortalAPI\Database\factories\SessionFactory::new();
    }

    
}
