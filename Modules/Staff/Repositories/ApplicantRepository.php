<?php
namespace Modules\Staff\Repositories;

use App\Models\Applicant;

class ApplicantRepository{

    private $applicant;
    public function __construct(Applicant $applicant)
    {
      $this->applicant = $applicant;
    }

    public function update($data,$id)
    {                        
        $this->applicant->where('id',$id)->update($data);                       
        return 'Updated successfuly';
    }

    public function getApplicantsWithoutPaginate($filters)
    {
        return $this->applicant->filter($filters)->get();     
    }

    public function getApplicantsWithoutAppends($filters=[])
    {
        $this->applicant::$withoutAppends = true;
        return $this->applicant->filter($filters)->get();
    }

    /**
     * Get all applicants with filtering and pagination
     */
    public function getAllApplicants($filters = [])
    {
        $query = $this->applicant->query();

        // Apply filters
        if (isset($filters['programme_id']) && $filters['programme_id']) {
            $query->where('programme_id', $filters['programme_id']);
        }

        if (isset($filters['session_id']) && $filters['session_id']) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('qualified_status', $filters['status']);
        }

        if (isset($filters['admission_status']) && $filters['admission_status']) {
            $query->where('admission_status', $filters['admission_status']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('application_number', 'like', "%{$search}%");
            });
        }

        $paginateBy = $filters['paginateBy'] ?? 25;

        return $query->with(['programme', 'session'])
                    ->latest()
                    ->paginate($paginateBy);
    }

    /**
     * Get applicant statistics
     */
    public function getApplicantStats()
    {
        $total = $this->applicant->count();
        $qualified = $this->applicant->where('qualified_status', 'qualified')->count();
        $admitted = $this->applicant->where('admission_status', 'admitted')->count();
        $pending = $this->applicant->where('admission_status', 'pending')->count();
        $notAdmitted = $this->applicant->where('admission_status', 'not_admitted')->count();

        return [
            'total' => $total,
            'qualified' => $qualified,
            'admitted' => $admitted,
            'pending' => $pending,
            'not_admitted' => $notAdmitted
        ];
    }

    /**
     * Update applicant admission status
     */
    public function updateApplicantStatus($applicantId, $status)
    {
        $this->applicant->where('id', $applicantId)->update([
            'admission_status' => $status,
            'updated_at' => now()
        ]);

        return 'Applicant status updated successfully';
    }

    /**
     * Bulk update applicant admission status
     */
    public function bulkUpdateApplicantStatus($applicantIds, $status)
    {
        $updated = $this->applicant->whereIn('id', $applicantIds)->update([
            'admission_status' => $status,
            'updated_at' => now()
        ]);

        return "Updated {$updated} applicants successfully";
    }

    /**
     * Process application (qualification check)
     */
    public function processApplication($applicantId)
    {
        $applicant = $this->applicant->find($applicantId);

        if (!$applicant) {
            throw new \Exception('Applicant not found', 404);
        }

        // Basic qualification logic - can be enhanced
        $isQualified = $this->checkQualification($applicant);

        $applicant->update([
            'status' => $isQualified ? 'qualified' : 'not_qualified',
            'updated_at' => now()
        ]);

        return [
            'applicant_id' => $applicantId,
            'status' => $isQualified ? 'qualified' : 'not_qualified',
            'message' => $isQualified ? 'Applicant is qualified' : 'Applicant is not qualified'
        ];
    }

    /**
     * Check if applicant meets qualification criteria
     */
    private function checkQualification($applicant)
    {
        // Basic qualification check - enhance as needed
        // Check JAMB score, O'Level results, etc.

        $jambScore = $applicant->jamb_score ?? 0;
        $minimumJambScore = 180; // Can be made configurable

        return $jambScore >= $minimumJambScore;
    }
}
