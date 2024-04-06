<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["name"];
    public $timestamps = false;
    
    /* 
    public function getCreatedByNameAttribute()
    {
        $staff = DB::table('staffs')->where("id", $this->created_by)->first();
        return "{$staff->first_name} {$staff->middle_name} {$staff->surname}";
    }

    protected $appends = ["created_by_name"]; */
    
}
