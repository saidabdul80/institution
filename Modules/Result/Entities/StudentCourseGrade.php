<?php

namespace Modules\Result\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Entities\Faculty;
use Modules\Staff\Entities\Department;
use Modules\Staff\Entities\Level;
use Modules\Staff\Entities\Course;
use Modules\Staff\Entities\Programme;
use Modules\Staff\Entities\CourseCategory;
use Modules\Staff\Entities\GradeSetting;
use Modules\Student\Entities\Student;
use Modules\Staff\Entities\Staff;

class StudentCourseGrade extends Model
{
    use HasFactory;

    protected $table = "student_courses_grades";

    protected $fillable = [
        'student_id',
        'course_id',
        'session_id',
        'semester',
        'level_id',
        'programme_id',
        'department_id',
        'faculty_id',
        'ca_score',
        'exam_score',
        'total_score',
        'grade',
        'grade_id',
        'grade_point',
        'credit_unit',
        'quality_point',
        'status',
        'remarks',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'ca_score' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'grade_point' => 'decimal:2',
        'quality_point' => 'decimal:2',
        'credit_unit' => 'integer'
    ];

    /**
     * Get the student that owns this grade
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course for this grade
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the programme for this grade
     */
    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Get the level for this grade
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the department for this grade
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the faculty for this grade
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the grade setting for this grade
     */
    public function gradeSetting()
    {
        return $this->belongsTo(GradeSetting::class, 'grade_id');
    }

    /**
     * Get the staff who created this grade
     */
    public function createdBy()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Get faculty name attribute
     */
    public function getFacultyAttribute()
    {
        $faculty = Faculty::find($this->faculty_id);
        if (!is_null($faculty)) {
            return "{$faculty->name}";
        } else {
            return '';
        }
    }

    /**
     * Get department name attribute
     */
    public function getDepartmentAttribute()
    {
        $department = Department::find($this->department_id);
        if (!is_null($department)) {
            return "{$department->name}";
        } else {
            return '';
        }
    }

    /**
     * Get level name attribute
     */
    public function getLevelAttribute()
    {
        $level = Level::find($this->level_id);
        if (!is_null($level)) {
            return "{$level->title}";
        } else {
            return '';
        }
    }

    /**
     * Get course title attribute
     */
    public function getTitleAttribute()
    {
        $course = Course::find($this->course_id);
        if (!is_null($course)) {
            return "{$course->title}";
        } else {
            return '';
        }
    }

    /**
     * Get course code attribute
     */
    public function getCodeAttribute()
    {
        $course = Course::find($this->course_id);
        if (!is_null($course)) {
            return "{$course->code}";
        } else {
            return '';
        }
    }

    /**
     * Get credit unit attribute
     */
    public function getCreditUnitAttribute()
    {
        $course = Course::find($this->course_id);
        if (!is_null($course)) {
            return "{$course->credit_unit}";
        } else {
            return '';
        }
    }

    /**
     * Get CA status attribute
     */
    public function getCaStatusAttribute()
    {
        if (!empty($this->ca_score) || !empty($this->exam_score)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get grade attribute
     */
    public function getGradeAttribute()
    {
        $grade = DB::table('grade_settings')->where("id", $this->grade_id)->first();

        if ($grade) {
            return $grade->grade;
        } else {
            return null;
        }
    }

    /**
     * Get total score attribute
     */
    public function getTotalScoreAttribute()
    {
        return $this->ca_score + $this->exam_score;
    }

    /**
     * Get course category attribute
     */
    public function getCourseCategoryAttribute()
    {
        $course_category_id = Course::where("id", $this->course_id)->first()->course_category_id ?? "";
        return CourseCategory::where("id", $course_category_id)->first()->short_name ?? "";
    }

    /**
     * Get grade point attribute
     */
    public function getGradePointAttribute()
    {
        $grade = DB::table('grade_settings')->where("id", $this->grade_id)->first();

        if ($grade) {
            return $grade->grade_point;
        } else {
            return null;
        }
    }

    /**
     * Get course status attribute
     */
    public function getCourseStatusAttribute()
    {
        return DB::table('programme_courses')->where('course_id', $this->course_id)->where('programme_id', $this->programme_id)->first()->status ?? "";
    }

    /**
     * Check if the grade is a pass
     */
    public function isPassed()
    {
        return $this->status === 'pass' || $this->grade_point > 0;
    }

    /**
     * Check if the grade is a fail
     */
    public function isFailed()
    {
        return $this->status === 'fail' || $this->grade_point == 0;
    }

    /**
     * Scope to get grades for a specific session and semester
     */
    public function scopeForSessionSemester($query, $sessionId, $semester)
    {
        return $query->where('session_id', $sessionId)
                    ->where('semester', $semester);
    }

    /**
     * Scope to get grades for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get grades for a specific course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    protected $appends = [
        'faculty', 'department', 'level', 'title', 'code', 'credit_unit', 
        'ca_status', 'grade', 'total_score', 'course_status', 'grade_point', 'course_category'
    ];

    protected static function newFactory()
    {
        return \Modules\Result\Database\factories\StudentCourseGradeFactory::new();
    }
}
