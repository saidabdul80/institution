<?php

namespace Modules\Result\Entities;

use App\Models\Level;
use App\Models\ProgrammeCurriculum;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentSemesterGpa extends Model
{
    use HasFactory;

    protected $table = 'student_semester_gpa';

    protected $fillable = [
        'student_id',
        'session_id',
        'semester',
        'level_id',
        'programme_id',
        'programme_curriculum_id',
        'registered_credit_units',
        'earned_credit_units',
        'total_credit_points',
        'gpa',
        'total_registered_credit_units',
        'total_earned_credit_units',
        'total_cumulative_points',
        'total_department_credit_points',
        'previous_cgpa',
        'cgpa',
        'carry_over_courses',
        'number_of_semesters',
        'academic_status',
        'is_compiled',
        'compiled_at',
        'compiled_by'
    ];

    protected $casts = [
        'gpa' => 'decimal:2',
        'previous_cgpa' => 'decimal:2',
        'cgpa' => 'decimal:2',
        'is_compiled' => 'boolean',
        'compiled_at' => 'datetime'
    ];

    protected $dates = [
        'compiled_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the student that owns the GPA record
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the session for this GPA record
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the level for this GPA record
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the programme for this GPA record
     */
    public function programme()
    {
        return $this->belongsTo(ProgrammeCurriculum::class, 'programme_curriculum_id');
    }

    /**
     * Get the staff who compiled this record
     */
    public function compiledBy()
    {
        return $this->belongsTo(Staff::class, 'compiled_by');
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
     * Get carry over courses as array
     */
    public function getCarryOverCoursesArrayAttribute()
    {
        if (empty($this->carry_over_courses)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->carry_over_courses)));
    }

    /**
     * Check if student is on probation
     */
    public function isOnProbation()
    {
        return $this->academic_status === 'probation';
    }

    /**
     * Check if student should be withdrawn
     */
    public function shouldBeWithdrawn()
    {
        return $this->academic_status === 'withdrawal';
    }

    /**
     * Calculate GPA based on credit points and credit units
     */
    public function calculateGpa()
    {
        if ($this->total_department_credit_points == 0) {
            return 0.00;
        }

        return round(($this->total_credit_points / $this->total_department_credit_points) * 5, 2);
    }

    /**
     * Calculate CGPA based on total cumulative points and total department credit points
     */
    public function calculateCgpa()
    {
        if ($this->total_department_credit_points == 0) {
            return 0.00;
        }

        return round(($this->total_cumulative_points / $this->total_department_credit_points) * 5, 2);
    }

    /**
     * Get academic status based on CGPA
     */
    public function determineAcademicStatus()
    {
        $cgpa = $this->cgpa;

        if ($cgpa >= 1.50) {
            return 'good_standing';
        } elseif ($cgpa >= 1.00) {
            return 'probation';
        } else {
            return 'withdrawal';
        }
    }

    /**
     * Scope to get records for a specific session and semester
     */
    public function scopeForSessionSemester($query, $sessionId, $semester)
    {
        return $query->where('session_id', $sessionId)
                    ->where('semester', $semester);
    }

    /**
     * Scope to get compiled records only
     */
    public function scopeCompiled($query)
    {
        return $query->where('is_compiled', true);
    }

    /**
     * Scope to get records for a specific level
     */
    public function scopeForLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    /**
     * Scope to get records for a specific programme
     */
    public function scopeForProgramme($query, $programmeId)
    {
        return $query->where('programme_curriculum_id', $programmeId);
    }

    /**
     * Get the class of degree based on CGPA
     */
    public function getClassOfDegree()
    {
        $cgpa = $this->cgpa;

        if ($cgpa >= 4.50) {
            return 'First Class';
        } elseif ($cgpa >= 3.50) {
            return 'Second Class Upper';
        } elseif ($cgpa >= 2.40) {
            return 'Second Class Lower';
        } elseif ($cgpa >= 1.50) {
            return 'Third Class';
        } else {
            return 'Pass';
        }
    }

    /**
     * Check if student meets graduation requirements
     */
    public function meetsGraduationRequirements($minCgpa = 1.50, $minCreditUnits = 120)
    {
        return $this->cgpa >= $minCgpa && 
               $this->total_earned_credit_units >= $minCreditUnits &&
               empty($this->carry_over_courses);
    }

    protected static function newFactory()
    {
        return \Modules\Result\Database\factories\StudentSemesterGpaFactory::new();
    }
}
