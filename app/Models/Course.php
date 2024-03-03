<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{

    use HasFactory, SoftDeletes;
    protected $fillable = [
        'title',
        'code',
        'course_category_id',
        'department_id',
        'tp',
        'credit_unit',
        'level_id',
        'status'
    ];
    public function getLevelAttribute() {
        return Level::find($this->level_id)?->title;
    }

    public function staff_courses(){
        return $this->hasManyThrough(Staff::class,StaffCourse::class,'course_id', 'id','id','staff_id');
    }

    public function getSemesterAttribute() {
       return Semester::find($this->semester_id)?->name;
    }

    public function getDepartmentAttribute() {
        return Department::find($this->department_id)?->abbr;
    }

    public function getCourseCodeAttribute()
    {
        return $this->code;
    }

    protected $appends = ['department','semester','level','course_code'];

    public function scopeSearch($query, $search)
    {
        if(!empty($search)){
            return $query->where('title', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');
        }
    }

}
