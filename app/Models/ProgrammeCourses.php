<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Casts\Attribute;

class ProgrammeCourses extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    protected $table = "programme_courses";

    protected $fillable =[
        'programme_id',
        'session_id',
        'semester_id',
        'tp',
        'course_id',
        'level_id',
        'staff_id'
    ];
    public function getProgrammeNameAttribute() {
        $programme = Programme::find($this->programme_id);
        if(isset($programme->name)){
            return $programme->name;
        }
    }
    
    public function getCourseCodeAttribute() {
        $id = (int) $this->course_id;
        $course = Course::find($id);
        if(isset($course->code)){
            return $course->code;
        }
    }

    public function getCourseTitleAttribute() {
        $id = (int) $this->course_id;
        $course = Course::find($id);
        if(isset($course->title)){
            return $course->title;
        }
    }

    public function getCourseCreditUnitAttribute() {
        $id = (int) $this->course_id;
        $course = Course::find($id);
        if(isset($course->credit_unit)){
            return $course->credit_unit;
        }
    }
    
    public function getCourseStatusAttribute() {
        $id = (int) $this->course_id;
        $course = Course::find($id);
        if(isset($course->status)){
            return $course->status;
        }
    }

    public function course(){
        return $this->belongsTo(Course::class,'course_id','id');
    }

    public function programme(){
        return $this->belongsTo(Programme::class,'programme_id', 'id');
    }

    public function scopeSearch($query, $search)
    {
        $query->whereHas('course',function($course) use($search){            
            $course->where('title','like',"%$search%")
                    ->orWhere('code','like',"%$search%");
        })->orWhereHas('programme',function($programme) use($search){            
            $programme->orWhere('name','like',"%$search%");
        });
    }
   
    public function getLevelAttribute() {
        return Level::find($this->level_id)?->title;
    }

    public function getSemesterAttribute() {
        return Semester::find($this->semester_id)?->name;
     }

    public $appends = ['programme_name', 'course_code', 'course_title', 'course_credit_unit','course_status','level','semester'];    
}
