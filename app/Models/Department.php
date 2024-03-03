<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'faculty_id',
        'name',
        'abbr',
        'deleted_at'
    ];
    public function getFacultyAttribute()
    {
        $faculty = Faculty::find($this->faculty_id);
        if(!empty($faculty)){
            return $faculty->name;
        }
    }

    public function scopeSearch($query, $search)
    {
        if(!is_null($search)){
            return $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('abbr', 'like', '%' . $search . '%');
        }
    }


    protected $appends = ['faculty'];
}
