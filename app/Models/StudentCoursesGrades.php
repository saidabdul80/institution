<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StudentCoursesGrades extends Model
{
    use HasFactory;
    protected $table = "student_courses_grades";
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

    public function getTitleAttribute() {
        $course = Course::find($this->course_id);
        if(!is_null($course)){
            return "{$course->title}";
        }else{
            return '';
        }
    }
    public function getCodeAttribute() {
        $course = Course::find($this->course_id);
        if(!is_null($course)){
            return "{$course->code}";
        }else{
            return '';
        }
    }

    public function getCreditUnitAttribute() {
        $course = Course::find($this->course_id);
        if(!is_null($course)){
            return "{$course->credit_unit}";
        }else{
            return '';
        }
    }

    public function getCaStatusAttribute() {
        if(!empty($this->ca_score) || !empty($this->exam_score)){
            return true;
        }else{
            return false;
        }
    }

    public function getGradeAttribute()
    {
        $grade = DB::table('grade_settings')->where("id", $this->grade_id)->first();

        if ($grade) {
            return $grade->grade;
        } else {
            return null;
        }
    }

    public function getTotalScoreAttribute()
    {
        return $this->ca_score + $this->exam_score;
    }

    public function getCourseCategoryAttribute()
    {
        $course_category_id = Course::where("id", $this->course_id)->first()->course_category_id ?? "";
        return CourseCategory::where("id", $course_category_id)->first()->short_name ?? "";
    }

    public function getGradePointAttribute()
    {
        $grade = DB::table('grade_settings')->where("id", $this->grade_id)->first();
        
        if ($grade)
        {
            return $grade->grade_point;
        }else {
            return null;
        }
    }

    public function getCourseStatusAttribute()
    {
        return DB::table('programme_courses')->where('course_id', $this->course_id)->where('programme_id', $this->programme_id)->first()->status ?? "";
    }



    protected $appends = ['faculty','department','level','title','code','credit_unit', 'ca_status', 'grade', 'total_score', 'course_status', 'grade_point', 'course_category'];
}
