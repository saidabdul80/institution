<?php

namespace Modules\Result\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Staff\Entities\Session;
use Modules\Staff\Entities\Level;
use Modules\Staff\Entities\Programme;
use Modules\Staff\Entities\Department;
use Modules\Staff\Entities\Faculty;
use Modules\Staff\Entities\Staff;

class ResultCompilationLog extends Model
{
    use HasFactory;

    protected $table = 'result_compilation_logs';

    protected $fillable = [
        'session_id',
        'semester',
        'level_id',
        'programme_id',
        'department_id',
        'faculty_id',
        'compilation_type',
        'students_processed',
        'results_processed',
        'compilation_summary',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'processing_time_seconds',
        'compiled_by',
        'compilation_parameters'
    ];

    protected $casts = [
        'compilation_parameters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected $dates = [
        'started_at',
        'completed_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the session for this compilation log
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the level for this compilation log
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the programme for this compilation log
     */
    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Get the department for this compilation log
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the faculty for this compilation log
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the staff who compiled the results
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
     * Get the status display name
     */
    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed'
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the compilation type display name
     */
    public function getCompilationTypeDisplayAttribute()
    {
        $types = [
            'semester' => 'Semester Compilation',
            'session' => 'Session Compilation',
            'level' => 'Level Compilation',
            'programme' => 'Programme Compilation'
        ];

        return $types[$this->compilation_type] ?? ucfirst($this->compilation_type);
    }

    /**
     * Get processing time in human readable format
     */
    public function getProcessingTimeHumanAttribute()
    {
        if (!$this->processing_time_seconds) {
            return 'N/A';
        }

        $seconds = $this->processing_time_seconds;
        
        if ($seconds < 60) {
            return "{$seconds} seconds";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return "{$minutes}m {$remainingSeconds}s";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }

    /**
     * Scope to get logs by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed logs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get processing logs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get logs for a specific session and semester
     */
    public function scopeForSessionSemester($query, $sessionId, $semester)
    {
        return $query->where('session_id', $sessionId)
                    ->where('semester', $semester);
    }

    /**
     * Check if compilation was successful
     */
    public function isSuccessful()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if compilation failed
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if compilation is still processing
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Mark compilation as completed
     */
    public function markAsCompleted($studentsProcessed = 0, $resultsProcessed = 0, $summary = null)
    {
        $this->update([
            'status' => 'completed',
            'students_processed' => $studentsProcessed,
            'results_processed' => $resultsProcessed,
            'compilation_summary' => $summary,
            'completed_at' => now(),
            'processing_time_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null
        ]);
    }

    /**
     * Mark compilation as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'processing_time_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : null
        ]);
    }

    protected static function newFactory()
    {
        return \Modules\Result\Database\factories\ResultCompilationLogFactory::new();
    }
}
