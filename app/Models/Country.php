<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    public function applicants(){
        return $this->hasMany(Applicant::class);
    }

    public function states(){
        return $this->hasMany(State::class);
    }
    
}
