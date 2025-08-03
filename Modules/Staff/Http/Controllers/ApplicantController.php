<?php
namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\ApplicantService;
use Modules\Staff\Transformers\UtilResource;


class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $applicantService;
    public function __construct(ApplicantService $applicantService)
    {
        $this->applicantService = $applicantService;
    }
    public function updateApplicant(Request $request)
    {

        try{

            Validator::make($request->all(), [
                'id' => 'required'
            ]);

            $applicant = $this->applicantService->updateApplicant($request);
            return new APIResource($applicant, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(\Exception $e){
            return new APIResource($e->getMessage(), true, $e->getCode() );
        }

    }

    public function exportApplicants(Request $request){
        try{
            return $this->applicantService->exportApplicants($request);
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    /**
     * Get all applicants with filtering
     */
    public function getAllApplicants(Request $request)
    {
        try {
            $response = $this->applicantService->getAllApplicants($request);
            return new APIResource($response, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get applicant statistics
     */
    public function getApplicantStats(Request $request)
    {
        try {
            $response = $this->applicantService->getApplicantStats($request);
            return new APIResource($response, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Update applicant status (admission status)
     */
    public function updateApplicantStatus(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required',
                'status' => 'required|in:admitted,not_admitted,rejected'
            ]);

            $response = $this->applicantService->updateApplicantStatus($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Bulk update applicant status
     */
    public function bulkUpdateApplicantStatus(Request $request)
    {
        try {
            $request->validate([
                'applicant_ids' => 'required|array',
                'applicant_ids.*' => 'required|integer',
                'status' => 'required|in:admitted,not_admitted,pending'
            ]);

            $response = $this->applicantService->bulkUpdateApplicantStatus($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Process application (qualification check)
     */
    public function processApplication(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required'
            ]);

            $response = $this->applicantService->processApplication($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
}
