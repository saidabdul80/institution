<?php

namespace App\Models;

use App\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class OlevelResult extends Model
{
    use HasFactory, SoftDeletes;    
    protected $fillable = ['*'];
    protected $casts = [
        'subjects_grades'=>ArrayObject::class,
    ];
    public function applicant(){
        return $this->belongsTo(Applicant::class);
    }
    
    protected static function newFactory()
    {
        return \Modules\ApplicationPortalAPI\Database\factories\OlevelResultFactory::new();       
    }

    public function getExamTypeAttribute() {
        $exam = ExamType::find($this->exam_type_id);
        if(!is_null($exam)){
            return "{$exam->name}";
        }else{
            return '';
        }        
    }    
    protected $appends = ['exam_type'];

    
}
