<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;
    protected $fillable = [
        '*'      
    ];
    public function getFacultyAttribute() {
        $faculty = Faculty::find($this->faculty_id);
        if(!is_null($faculty)){
            return "{$faculty->name}";
        }else{
            return '';
        }
    }

    public function getDepartmentAttribute() {
        $department = Department::find($this->department_id);
        if(!is_null($department)){
            return "{$department->name}";
        }else{
            return '';
        }
    }

    public function getLevelAttribute() {
        $level = Level::find($this->level_id);
        if(!is_null($level)){
            return "{$level->title}";
        }else{
            return '';
        }
    }

    public function getSessionAttribute()
    {
        return Session::find($this->session_id)->title ?? '';
    }
       
    protected $appends = ['faculty','department','level', 'session'];
}
