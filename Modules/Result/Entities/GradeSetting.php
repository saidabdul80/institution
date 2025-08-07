<?php

namespace Modules\Result\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Staff\Entities\Programme;
use Modules\Staff\Entities\Staff;

class GradeSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grade_settings';

    protected $fillable = [
        'programme_id', // nullable - null for general settings, specific ID for programme-specific
        'min_score',
        'programme_curriculum_id',
        'max_score',
        'grade',
        'grade_point',
        'status',
        'description',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'grade_point' => 'decimal:2'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the programme for this grade setting
     */
    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Get the staff who created this grade setting
     */
    public function createdBy()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Get the staff who last updated this grade setting
     */
    public function updatedBy()
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    /**
     * Get the staff who deleted this grade setting
     */
    public function deletedBy()
    {
        return $this->belongsTo(Staff::class, 'deleted_by');
    }

    /**
     * Get the status display name
     */
    public function getStatusDisplayAttribute()
    {
        return ucfirst($this->status);
    }

    /**
     * Get the grade range display
     */
    public function getGradeRangeAttribute()
    {
        return "{$this->min_score} - {$this->max_score}";
    }

    /**
     * Check if a score falls within this grade range
     */
    public function isScoreInRange($score)
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }

    /**
     * Check if this is a passing grade
     */
    public function isPassingGrade()
    {
        return $this->status === 'pass';
    }

    /**
     * Check if this is a failing grade
     */
    public function isFailingGrade()
    {
        return $this->status === 'fail';
    }

    /**
     * Check if this is a general grade setting (applies to all programmes)
     */
    public function isGeneralSetting()
    {
        return is_null($this->programme_curriculum_id);
    }

    /**
     * Check if this is a programme-specific grade setting
     */
    public function isProgrammeSpecific()
    {
        return !is_null($this->programme_curriculum_id);
    }

    /**
     * Scope to get general grade settings (programme_curriculum_id is null)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('programme_curriculum_id');
    }

    /**
     * Scope to get programme-specific grade settings
     */
    public function scopeProgrammeSpecific($query)
    {
        return $query->whereNotNull('programme_curriculum_id');
    }

    /**
     * Scope to get grade settings for a specific programme
     */
    public function scopeForProgramme($query, $programmeId)
    {
        return $query->where('programme_curriculum_id', $programmeId);
    }

    /**
     * Scope to get passing grades only
     */
    public function scopePassingGrades($query)
    {
        return $query->where('status', 'pass');
    }

    /**
     * Scope to get failing grades only
     */
    public function scopeFailingGrades($query)
    {
        return $query->where('status', 'fail');
    }

    /**
     * Scope to order by grade point descending
     */
    public function scopeOrderByGradePoint($query, $direction = 'desc')
    {
        return $query->orderBy('grade_point', $direction);
    }

    /**
     * Scope to order by min score ascending
     */
    public function scopeOrderByScore($query, $direction = 'asc')
    {
        return $query->orderBy('min_score', $direction);
    }

    /**
     * Get grade setting by score with programme-specific fallback to general
     */
    public static function getGradeByScore($score, $programmeId = null)
    {
        // First, try to find programme-specific grade setting
        if ($programmeId) {
            $programmeGrade = static::where('min_score', '<=', $score)
                ->where('max_score', '>=', $score)
                ->where('programme_curriculum_id', $programmeId)
                ->first();

            if ($programmeGrade) {
                return $programmeGrade;
            }
        }

        // Fallback to general grade settings
        return static::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->whereNull('programme_curriculum_id')
            ->first();
    }

    /**
     * Get all grade settings for a programme with fallback to general settings
     */
    public static function getGradeScaleForProgramme($programmeId = null)
    {
        if ($programmeId) {
            // First check if programme has specific grade settings
            $programmeSettings = static::forProgramme($programmeId)
                ->orderByGradePoint('desc')
                ->get();

            if ($programmeSettings->isNotEmpty()) {
                return $programmeSettings;
            }
        }

        // Fallback to general grade settings
        return static::general()
            ->orderByGradePoint('desc')
            ->get();
    }

    /**
     * Get general grade settings only
     */
    public static function getGeneralGradeScale()
    {
        return static::general()
            ->orderByGradePoint('desc')
            ->get();
    }

    /**
     * Calculate grade and grade point for a given score
     */
    public static function calculateGrade($score, $programmeId = null)
    {
        $gradeSetting = static::getGradeByScore($score, $programmeId);

        if ($gradeSetting) {
            return [
                'grade' => $gradeSetting->grade,
                'grade_point' => $gradeSetting->grade_point,
                'status' => $gradeSetting->status,
                'description' => $gradeSetting->description ?? ''
            ];
        }

        // Default to F grade if no grade setting found
        return [
            'grade' => 'F',
            'grade_point' => 0.0,
            'status' => 'fail',
            'description' => 'Fail'
        ];
    }

    protected static function newFactory()
    {
        return \Modules\Result\Database\factories\GradeSettingFactory::new();
    }
}
