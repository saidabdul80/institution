<?php

namespace Modules\Result\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Staff\Entities\Faculty;
use Modules\Staff\Entities\Department;
use Modules\Staff\Entities\Level;
use Modules\Staff\Entities\Session;
use Modules\Staff\Entities\Course;
use Modules\Staff\Entities\Programme;
use Modules\Student\Entities\Student;
use Modules\Staff\Entities\Staff;

class Result extends Model
{
    use HasFactory;

    protected $table = 'results';

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
     * Get the student that owns this result
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course for this result
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the session for this result
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the level for this result
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the programme for this result
     */
    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Get the department for this result
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the faculty for this result
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the staff who created this result
     */
    public function createdBy()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Get the staff who last updated this result
     */
    public function updatedBy()
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    /**
     * Get faculty name attribute
     */
    public function getFacultyNameAttribute()
    {
        return $this->faculty ? $this->faculty->name : '';
    }

    /**
     * Get department name attribute
     */
    public function getDepartmentNameAttribute()
    {
        return $this->department ? $this->department->name : '';
    }

    /**
     * Get level name attribute
     */
    public function getLevelNameAttribute()
    {
        return $this->level ? $this->level->title : '';
    }

    /**
     * Get session name attribute
     */
    public function getSessionNameAttribute()
    {
        return $this->session ? $this->session->title : '';
    }

    /**
     * Get semester name attribute
     */
    public function getSemesterNameAttribute()
    {
        $semesters = [
            1 => 'First Semester',
            2 => 'Second Semester',
            3 => 'Third Semester'
        ];

        return $semesters[$this->semester] ?? "Semester {$this->semester}";
    }

    /**
     * Check if the result is a pass
     */
    public function isPassed()
    {
        return $this->status === 'pass' || $this->grade_point > 0;
    }

    /**
     * Check if the result is a fail
     */
    public function isFailed()
    {
        return $this->status === 'fail' || $this->grade_point == 0;
    }

    /**
     * Scope to get results for a specific session and semester
     */
    public function scopeForSessionSemester($query, $sessionId, $semester)
    {
        return $query->where('session_id', $sessionId)
                    ->where('semester', $semester);
    }

    /**
     * Scope to get results for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get results for a specific course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to get passed results only
     */
    public function scopePassed($query)
    {
        return $query->where('status', 'pass')
                    ->orWhere('grade_point', '>', 0);
    }

    /**
     * Scope to get failed results only
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'fail')
                    ->orWhere('grade_point', '=', 0);
    }

    protected static function newFactory()
    {
        return \Modules\Result\Database\factories\ResultFactory::new();
    }
}
