<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicantQualification extends Model
{
    use HasFactory;
    protected $table = "applicants_qualifications";
    protected $fillable = ["*"];
   
    public function qualification(){
      return $this->belongsTo(Qualification::class,'qualification_id');
    }

    public function getNameAttribute() {
      $qualification = Qualification::find($this->qualification_id);
      return "{$qualification->name}";
    }

    public function getShortNameAttribute() {
      $qualification = Qualification::find($this->qualification_id);
      return "{$qualification->short_name}";
    }

    protected $appends = ['name', 'short_name'];

    protected static function newFactory()
    {
      //  return \Modules\ApplicationPortalAPI\Database\factories\ApplicantQualificationFactory::new();
    }
}
