<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class LGA extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = 'l_g_as';


    public function state(){
        return $this->belongsTo(State::class);
    }
    
    public function applicants(){
        return $this->hasMany(Applicant::class);
    }

    public function getStateNameAttribute() {
        $state = State::where("state_id", $this->state_id)->first();
        return $state->name;
    }

  
}
