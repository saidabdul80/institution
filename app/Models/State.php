<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class State extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    public function applicant(){
        return $this->belongsTo(Applicant::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }
   
    public function lga(){
        return $this->hasMany(Lga::class);
    }

    public function getCountryNameAttribute() {
        $country = Country::where("country_id", $this->country_id)->first();
        return $country->name;
    }
}
