<?php
namespace Modules\Staff\Services;

use App\Exports\Export;
use App\Models\Applicant;
use App\Models\ApplicantExport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Staff\Repositories\ApplicantRepository;

class ApplicantService extends Utilities{

    private $applicantRepository;
    public function __construct(ApplicantRepository $applicantRepository)
    {
        $this->applicantRepository = $applicantRepository;        
    }

    public function updateApplicant($request){
        $data = $request->all();
        $id = $data['id'];
        unset($data['id']);
        return $this->applicantRepository->update($data, $id);
    }

    public function exportApplicants($request){
        $filters = $request->get('filters') ?? [];
        $filters['custom_fields'] = true;
        $applicants = $this->applicantRepository->getApplicantsWithoutAppends($filters);
        if ($applicants->isEmpty()) {
            throw new \Exception('No records found', 404);
        }

        // Split the applicants into chunks of 1000 or less
        $response = Excel::download(new Export($applicants), 'applicants.xlsx');
        ob_end_clean();
        return  $response;

    }

    /**
     * Get all applicants with filtering
     */
    public function getAllApplicants($request)
    {
        $filters = $request->all();
        return $this->applicantRepository->getAllApplicants($filters);
    }

    /**
     * Get applicant statistics
     */
    public function getApplicantStats($request)
    {
        return $this->applicantRepository->getApplicantStats();
    }

    /**
     * Update applicant status (admission status)
     */
    public function updateApplicantStatus($request)
    {
        $applicantId = $request->get('applicant_id');
        $status = $request->get('status');

        return $this->applicantRepository->updateApplicantStatus($applicantId, $status);
    }

    /**
     * Bulk update applicant status
     */
    public function bulkUpdateApplicantStatus($request)
    {
        $applicantIds = $request->get('applicant_ids');
        $status = $request->get('status');

        return $this->applicantRepository->bulkUpdateApplicantStatus($applicantIds, $status);
    }

    /**
     * Process application (qualification check)
     */
    public function processApplication($request)
    {
        $applicantId = $request->get('applicant_id');

        return $this->applicantRepository->processApplication($applicantId);
    }
}
