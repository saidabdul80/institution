<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;
    protected $table = "student_enrollments";

    public function getLevelToAttribute()
    {        
        $level_to = Level::find($this->level_id_to);
        if(!is_null($level_to)){
            return $level_to->title;
        }else{
            return '';
        }
    }

    public function getLevelFromAttribute()
    {
        $level_from = Level::find($this->level_id_from);        
        if(!is_null($level_from) ){
            return $level_from->title;
        }else{
            return '';
        }
    }

    public function getSessionAttribute()
    {
        $session = Session::find($this->session_id);        
        if(!is_null($session) ){
            return $session->name;
        }else{
            return '';
        }
    }
    protected $appends = ['level_from','level_to','session'];
}
