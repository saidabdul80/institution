<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StaffCourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['staff_id', 'faculty_id', 'department_id', 'programme_id', 'session_id', 'semester_id', 'course_id', 'status', 'upload_status', 'created_by'];
    protected $appends = ["staff_name", "course_name", "course_code", "programme_name", "session_name", "semester_name", "department_name", 'full_name', 'course_title'];

    protected static function newFactory()
    {
        //return \Modules\ResultPortalAPI\Database\factories\StaffCourseFactory::new();
    }

    public function getStaffNameAttribute()
    {
        $staff = DB::table('staffs')->where("id", $this->staff_id)->first();
        return "{$staff->first_name} {$staff->middle_name} {$staff->surname}";
    }

    public function getCourseNameAttribute()
    {
        return DB::table('courses')->where("id", $this->course_id)->first()?->title;
    }

    public function getCourseCodeAttribute()
    {
        return DB::table('courses')->where("id", $this->course_id)->first()?->code;
    }

    public function getProgrammeNameAttribute()
    {
        return DB::table('programmes')->where("id", $this->programme_id)->first()?->name;
    }

    public function getDepartmentNameAttribute()
    {
        return DB::table('departments')->where("id", $this->department_id)->first()?->name;
    }

    public function getSemesterNameAttribute()
    {
        return DB::table('semesters')->where("id", $this->semester_id)->first()?->name;
    }
    public function getSessionNameAttribute()
    {
        return DB::table('sessions')->where("id", $this->session_id)->first()?->name;
    }

    public function getFullNameAttribute()
    {

        $staff = Staff::find($this->staff_id);
        if (isset($staff->first_name)) {
            return $staff->first_name . ' ' . $staff->middle_name . ' ' . $staff->surname;
        }
        
    }

    public function getCourseTitleAttribute()
    {
        $id = (int) $this->course_id;
        $course = Course::find($id);
        if (isset($course->title)) {
            return $course->title;
        }
    }

    public function course(){
        return $this->belongsTo(Course::class,'course_id','id');
    }
    
    public function staff(){
        return $this->belongsTo(Staff::class,'staff_id','id');
    }

    public function scopeSearch($query, $search)
    {
        $query->whereHas('course',function($course) use($search){            
            $course->where('title','like',"%$search%")
                    ->orWhere('code','like',"%$search%");
        })->orWhereHas('staff',function($staff) use($search){            
            $staff->orWhere('staff_number','like',"%$search%")
                    ->orWhere('email','like',"%$search%")
                    ->orWhere('first_name','like',"%$search%")
                    ->orWhere('middle_name','like',"%$search%")
                    ->orWhere('surname','like',"%$search%");                                                                        
        });
    }

}
