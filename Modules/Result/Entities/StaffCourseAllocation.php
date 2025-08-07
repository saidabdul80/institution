<?php

namespace Modules\Result\Entities;

use App\Models\Course;
use App\Models\Level;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Staff;
use App\Models\StudentCourseRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class StaffCourseAllocation extends Model
{
    use HasFactory;

    protected $table = 'staff_course_allocations';

    protected $fillable = [
        'staff_id',
        'course_id',
        'session_id',
        'semester',
        'programme_id',
        'programme_curriculum_id',
        'level_id',
        'allocation_type',
        'remarks',
        'is_active',
        'allocated_by',
        'allocated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allocated_at' => 'datetime'
    ];

    protected $dates = [
        'allocated_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the staff member assigned to this course
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the course for this allocation
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the session for this allocation
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the programme for this allocation
     */
    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Get the level for this allocation
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the staff who made this allocation
     */
    public function allocatedBy()
    {
        return $this->belongsTo(Staff::class, 'allocated_by');
    }

    /**
     * Get the semester name
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
     * Get the allocation type display name
     */
    public function getAllocationTypeDisplayAttribute()
    {
        $types = [
            'lecturer' => 'Lecturer',
            'coordinator' => 'Course Coordinator',
            'examiner' => 'External Examiner'
        ];

        return $types[$this->allocation_type] ?? ucfirst($this->allocation_type);
    }

    /**
     * Scope to get active allocations only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get allocations for a specific session and semester
     */
    public function scopeForSessionSemester($query, $sessionId, $semester)
    {
        return $query->where('session_id', $sessionId)
                    ->where('semester', $semester);
    }

    /**
     * Scope to get allocations for a specific staff member
     */
    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    /**
     * Scope to get allocations for a specific course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to get allocations by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('allocation_type', $type);
    }

    /**
     * Scope to get lecturer allocations
     */
    public function scopeLecturers($query)
    {
        return $query->where('allocation_type', 'lecturer');
    }

    /**
     * Scope to get coordinator allocations
     */
    public function scopeCoordinators($query)
    {
        return $query->where('allocation_type', 'coordinator');
    }

    /**
     * Scope to get examiner allocations
     */
    public function scopeExaminers($query)
    {
        return $query->where('allocation_type', 'examiner');
    }

    /**
     * Scope to get allocations for a specific programme
     */
    public function scopeForProgramme($query, $programmeId)
    {
        return $query->where('programme_id', $programmeId);
    }

    /**
     * Scope to get allocations for a specific level
     */
    public function scopeForLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    /**
     * Check if this allocation allows result submission
     */
    public function canSubmitResults()
    {
        return $this->is_active && in_array($this->allocation_type, ['lecturer', 'coordinator']);
    }

    /**
     * Check if this allocation allows result approval
     */
    public function canApproveResults()
    {
        return $this->is_active && in_array($this->allocation_type, ['coordinator', 'examiner']);
    }

    /**
     * Get all students registered for this course allocation
     */
    public function registeredStudents()
    {
        return $this->hasMany(StudentCourseRegistration::class, 'course_id', 'course_id')
                    ->where('session_id', $this->session_id)
                    ->where('semester', $this->semester);
    }

    /**
     * Get results for this course allocation
     */
    public function results()
    {
        return $this->hasMany(Result::class, 'course_id', 'course_id')
                    ->where('session_id', $this->session_id)
                    ->where('semester', $this->semester);
    }

    /**
     * Deactivate this allocation
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate this allocation
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    protected static function newFactory()
    {
        return \Modules\Result\Database\factories\StaffCourseAllocationFactory::new();
    }
}
