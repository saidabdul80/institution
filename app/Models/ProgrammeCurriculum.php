<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgrammeCurriculum extends Model
{
    use HasFactory;

    protected $table = 'programme_curriculums';

    protected $fillable = [
        'curriculum_id',
        'programme_id',
        'name',
        'description',

        'duration_years',
        'duration_semesters',
        'minimum_cgpa',
        'minimum_credit_units',
        'admission_requirements',
        'graduation_requirements',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_years' => 'integer',
        'duration_semesters' => 'integer',
        'minimum_cgpa' => 'decimal:2',
        'minimum_credit_units' => 'integer',
        'admission_requirements' => 'array',
        'graduation_requirements' => 'array',
        'metadata' => 'array'
    ];

    //protected $appends = ['is_active'];
    /**
     * Get the curriculum that owns this programme curriculum
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    /**
     * Get the programme that this curriculum belongs to
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('curriculum', function($q) {
            $q->where('is_active', true);
        });
    }
    /**
     * Get applicants using this programme curriculum
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class, 'programme_curriculum_id');
    }

    /**
     * Get students using this programme curriculum
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'programme_curriculum_id');
    }

    /**
     * Scope to get programme curriculums by curriculum
     */
    public function scopeByCurriculum($query, $curriculumId)
    {
        return $query->where('curriculum_id', $curriculumId);
    }

    /**
     * Scope to get programme curriculums by programme
     */
    public function scopeByProgramme($query, $programmeId)
    {
        return $query->where('programme_id', $programmeId);
    }

    /**
     * Scope to get programme curriculums with active curriculum
     */
    public function scopeWithActiveCurriculum($query)
    {
        return $query->whereHas('curriculum', function($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Activate this programme curriculum and deactivate others for the same programme
     */
    public function activate()
    {
        // Deactivate all other programme curriculums for the same programme
        static::where('programme_id', $this->programme_id)
              ->where('id', '!=', $this->id)
              ->update(['is_active' => false]);
        
        // Activate this programme curriculum
        $this->update(['is_active' => true]);
    }

    /**
     * Get the full programme name with curriculum info
     */
    public function getFullNameAttribute(): string
    {
        return $this->name . ' (' . $this->curriculum->name . ')';
    }

    /**
     * Get programme curriculum status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if (!$this->curriculum->is_active) {
            return 'curriculum_inactive';
        }
        
        if (!$this->curriculum->isEffective()) {
            return 'curriculum_not_effective';
        }
        
        return 'active';
    }

    /**
     * Check if this programme curriculum is available for new admissions
     */
    public function isAvailableForAdmission(): bool
    {
        return $this->is_active && 
               $this->curriculum->is_active && 
               $this->curriculum->isEffective();
    }
}
