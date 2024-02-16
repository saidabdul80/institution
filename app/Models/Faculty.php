<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    use HasFactory, SoftDeletes;

    public function scopeSearch($query, $search)
    {
        if(!is_null($search)){
            return $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('abbr', 'like', '%' . $search . '%');
        }
    }
}
