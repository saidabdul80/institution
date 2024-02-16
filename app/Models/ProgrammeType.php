<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeType extends Model
{
    use HasFactory;

    public function scopeSearch($query, $search)
    {
        if(!is_null($search)){
            return $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('short_name', 'like', '%' . $search . '%');
        }
    }
    protected $fillable = ['name','short_name'];
}
