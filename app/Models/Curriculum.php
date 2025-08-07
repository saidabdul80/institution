<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    use HasFactory;

    protected $table = 'curriculums';

    protected $fillable = [
        'name',
        'description',
        'academic_year',
        'is_active',
        'effective_date',
        'expiry_date',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
        'academic_year' => 'integer'
    ];

    /**
     * Get the programme curriculums for this curriculum
     */
    public function programmeCurriculums(): HasMany
    {
        return $this->hasMany(ProgrammeCurriculum::class);
    }

    /**
     * Get active programme curriculums
     */
    public function activeProgrammeCurriculums(): HasMany
    {
        return $this->hasMany(ProgrammeCurriculum::class)->where('is_active', true);
    }

    /**
     * Scope to get active curriculums
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get curriculums by academic year
     */
    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Scope to get effective curriculums
     */
    public function scopeEffective($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('effective_date', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', $date);
                    });
    }

    /**
     * Activate this curriculum and deactivate others
     */
    public function activate()
    {
        // Deactivate all other curriculums
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        
        // Activate this curriculum
        $this->update(['is_active' => true]);
    }

    /**
     * Check if curriculum is currently effective
     */
    public function isEffective($date = null): bool
    {
        $date = $date ?? now();
        
        return $this->effective_date <= $date && 
               ($this->expiry_date === null || $this->expiry_date >= $date);
    }

    /**
     * Get curriculum status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if (!$this->isEffective()) {
            return 'not_effective';
        }
        
        return 'active';
    }
}
