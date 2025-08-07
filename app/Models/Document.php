<?php

namespace App\Models;

use App\Services\Util;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $fillable = ['owner_id','url','owner_type','document_type','name','description','file_type','file_size','status','verification_status','verification_notes','verified_by','verified_at','created_at','updated_at'];


    public function getUrlAttribute()
    {
        return Util::publicUrl($this->attributes['url']);
    }

}
