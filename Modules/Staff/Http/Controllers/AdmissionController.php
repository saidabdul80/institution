<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Staff\Services\AdmissionService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;
use App\Models\Applicant;
use App\Jobs\SendAdmissionEmail;
use App\Models\Level;
use App\Services\Util;

class AdmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $admissionService;
    private $utilities;
    public function __construct(AdmissionService $admissionService, Utilities $utilities)
    {
        $this->admissionService = $admissionService;
        $this->utilities=  $utilities;
    }

    public function applicantAdmission(Request $request){

        try{

            $request->validate([
                "applicant_ids" => "required|array", //[]
                "session_id"   =>"required",
                "admission_options" => "nullable|array",
                "admission_options.change_programme" => "nullable|boolean",
                "admission_options.change_level" => "nullable|boolean",
                "admission_options.just_admit" => "nullable|boolean",
                "admission_options.new_programme_id" => "nullable|integer|exists:programmes,id",
                "admission_options.new_programmed_curriculum_id" => "nullable|integer|exists:programmed_curriculums,id",
                "admission_options.new_level_id" => "nullable|integer|exists:levels,id",
            ]);

            $response = $this->admissionService->acceptApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            Log::error($e);
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function bulkApplicantAdmission(Request $request){
        try{

            $request->validate([
                "file" => "required",
            ]);

            $response = $this->admissionService->bulkAcceptApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }
    public function unAdmitApplicant(Request $request){
        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                "session_id"   =>"required",
            ]);

            $response = $this->admissionService->rejectApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function rejectApplicant(Request $request){

    }

    public function allApplicants(Request $request){
        try{

            $request->validate([
                'paginateBy' => 'required',
                'status' => 'required',//paid, unpaid
                'session_id'=>'required',
                'payment_name'=>'required'

            ]);

            $response = $this->admissionService->paidApplicants($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function activateStudent(Request $request){

        try{

            $request->validate([
                "matric_number" => "required", //[]
                "session_id" => "required", //this is used to check if student paid current school fees
            ]);

            $response = $this->admissionService->activateStudent($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateAdmissionStatus(Request $request){

        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                "status" => "required", //
            ]);
            $response = $this->admissionService->updateAdmissionStatus($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateQualifiedStatus(Request $request){

        try{

            $request->validate([
                "applicant_ids" => "required", //[]
                "status" => "required", //
            ]);
            $response = $this->admissionService->updateQualifiedStatus($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }



    public function getApplicant(Request $request){

        try{

            $request->validate([
                "session_id" => "required", //
                //paginateBy
            ]);

            $response = $this->admissionService->getApplicant($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getStudents(Request $request){

        try{

            $request->validate([
                "session_id" => "required", //
                //paginateBy
                //"search" =>"required" //{applicant_state:...,}
            ]);

            $response = $this->admissionService->getStudents($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getBatches(Request $request){

        try{

            $request->validate([
                "session_id" => "required", //
            ]);

            $response = $this->admissionService->getBatches($request->get('session_id'));
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }


    public function changeApplicantProgramme(Request $request){

        try{
            $request->validate([
                'applicant_id'   =>'required',
                'faculty_id' => 'required',
                'department_id' => 'required',
                'programme_id' => 'required'
            ]);
            $response = $this->admissionService->changeAdmittedProgramme($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function createBatch(Request $request){

        try{

            $request->validate([
                "name"=>"required"
            ]);

            $response = $this->admissionService->createBatch($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function updateBatch(Request $request){

        try{

            $request->validate([
                "id" => "required",
                "name"=>"required"
            ]);

            $response = $this->admissionService->updateBatch($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function deleteBatch(Request $request){

        try{

            $request->validate([
                "id" => "required",
            ]);

            $response = $this->admissionService->deleteBatch($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getAllBatches(Request $request){
        try{

            $response = $this->admissionService->fetchAdmissionBatches();
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getTemplate(){
        return  $this->utilities->getFile('admissionUploadTemplate.csv');
    }

    /**
     * Get unpublished admitted applicants by batch
     */
    public function getUnpublishedAdmissions(Request $request)
    {
        try {
            $request->validate([
                'batch_id' => 'nullable|exists:admission_batches,id',
                'programme_id' => 'nullable|exists:programmes,id',
                'session_id' => 'nullable|exists:sessions,id',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $query = Applicant::with(['programme', 'level', 'batch'])
                ->admittedUnpublished();

            if ($request->batch_id) {
                $query->where('batch_id', $request->batch_id);
            }

            if ($request->programme_id) {
                $query->where('programme_id', $request->programme_id);
            }

            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            $perPage = $request->get('per_page', 20);
            $applicants = $query->paginate($perPage);

            return new APIResource($applicants, false, 200);

        } catch (Exception $e) {
            Log::error('Error fetching unpublished admissions: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Publish admitted applicants by batch
     */
    public function publishAdmissions(Request $request)
    {
        try {
            $request->validate([
                'applicant_ids' => 'required|array|min:1',
                'applicant_ids.*' => 'exists:applicants,id',
                'notes' => 'nullable|string|max:500',
                'send_emails' => 'boolean'
            ]);

            $publishedBy = auth()->user()->name ?? 'System';
            $notes = $request->get('notes');
            $sendEmails = $request->get('send_emails', true);

            DB::beginTransaction();

            $applicants = Applicant::with(['programme', 'level'])
                ->whereIn('id', $request->applicant_ids)
                ->where('admission_status', 'admitted')
                ->whereNull('published_at')
                ->get();

            if ($applicants->isEmpty()) {
                return new APIResource('No unpublished admitted applicants found', true, 404);
            }

            $publishedCount = 0;
            $schoolName = Util::getConfigValue('school_name');
            $schoolLogo = Util::getConfigValue('school_logo');
            $level = Level::find(1);    
            foreach ($applicants as $applicant) {
                $applicant->publish($publishedBy, $notes);
                $publishedCount++;

                // Send admission email if requested
                if ($sendEmails && $applicant->email) {
                    SendAdmissionEmail::dispatch(
                        $applicant,
                        $schoolName,
                        $schoolLogo,
                        $applicant->programme,
                        $applicant->level ?? $level
                    )->onQueue('default');
                }
            }

            DB::commit();

            $message = "Successfully published {$publishedCount} admission(s)";
            if ($sendEmails) {
                $message .= " and queued admission emails";
            }

            return new APIResource([
                'message' => $message,
                'published_count' => $publishedCount,
                'emails_queued' => $sendEmails ? $publishedCount : 0
            ], false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error publishing admissions: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Unpublish admitted applicants
     */
    public function unpublishAdmissions(Request $request)
    {
        try {
            $request->validate([
                'applicant_ids' => 'required|array|min:1',
                'applicant_ids.*' => 'exists:applicants,id'
            ]);

            DB::beginTransaction();

            $applicants = Applicant::whereIn('id', $request->applicant_ids)
                ->where('admission_status', 'admitted')
                ->whereNotNull('published_at')
                ->get();

            if ($applicants->isEmpty()) {
                return new APIResource('No published admitted applicants found', true, 404);
            }

            $unpublishedCount = 0;
            foreach ($applicants as $applicant) {
                $applicant->unpublish();
                $unpublishedCount++;
            }

            DB::commit();

            return new APIResource([
                'message' => "Successfully unpublished {$unpublishedCount} admission(s)",
                'unpublished_count' => $unpublishedCount
            ], false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error unpublishing admissions: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get publication statistics
     */
    public function getPublicationStats(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'nullable|exists:sessions,id',
                'batch_id' => 'nullable|exists:admission_batches,id'
            ]);

            $query = Applicant::where('admission_status', 'admitted');

            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            if ($request->batch_id) {
                $query->where('batch_id', $request->batch_id);
            }

            $stats = [
                'total_admitted' => (clone $query)->count(),
                'published' => (clone $query)->published()->count(),
                'unpublished' => (clone $query)->unpublished()->count(),
            ];

            return new APIResource($stats, false, 200);

        } catch (Exception $e) {
            Log::error('Error fetching publication stats: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Publish all admitted applicants by batch
     */
    public function publishByBatch(Request $request)
    {
        try {
            $request->validate([
                'batch_id' => 'required|exists:admission_batches,id',
                'notes' => 'nullable|string|max:500',
                'send_emails' => 'boolean'
            ]);

            $publishedBy = auth()->user()->name ?? 'System';
            $notes = $request->get('notes');
            $sendEmails = $request->get('send_emails', true);

            DB::beginTransaction();

            $applicants = Applicant::with(['programme', 'level'])
                ->where('batch_id', $request->batch_id)
                ->where('admission_status', 'admitted')
                ->whereNull('published_at')
                ->get();

            if ($applicants->isEmpty()) {
                return new APIResource('No unpublished admitted applicants found in this batch', true, 404);
            }

            $publishedCount = 0;
            $schoolName = Util::getConfigValue('school_name');
            $schoolLogo = Util::getConfigValue('school_logo');
            $level = Level::find(1);    
            foreach ($applicants as $applicant) {
                $applicant->publish($publishedBy, $notes);
                $publishedCount++;

                // Send admission email if requested
                if ($sendEmails && $applicant->email) {
                    SendAdmissionEmail::dispatch(
                        $applicant,
                        $schoolName,
                        $schoolLogo,
                        $applicant->programme,
                        $applicant->level ?? $level
                    )->onQueue('default');
                }
            }

            DB::commit();

            $message = "Successfully published {$publishedCount} admission(s) from batch";
            if ($sendEmails) {
                $message .= " and queued admission emails";
            }

            return new APIResource([
                'message' => $message,
                'published_count' => $publishedCount,
                'emails_queued' => $sendEmails ? $publishedCount : 0,
                'batch_id' => $request->batch_id
            ], false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error publishing batch admissions: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Unpublish all admitted applicants by batch
     */
    public function unpublishByBatch(Request $request)
    {
        try {
            $request->validate([
                'batch_id' => 'required|exists:admission_batches,id'
            ]);

            DB::beginTransaction();

            $applicants = Applicant::where('batch_id', $request->batch_id)
                ->where('admission_status', 'admitted')
                ->whereNotNull('published_at')
                ->get();

            if ($applicants->isEmpty()) {
                return new APIResource('No published admitted applicants found in this batch', true, 404);
            }

            $unpublishedCount = 0;
            foreach ($applicants as $applicant) {
                $applicant->unpublish();
                $unpublishedCount++;
            }

            DB::commit();

            return new APIResource([
                'message' => "Successfully unpublished {$unpublishedCount} admission(s) from batch",
                'unpublished_count' => $unpublishedCount,
                'batch_id' => $request->batch_id
            ], false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error unpublishing batch admissions: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Handle bulk actions on selected applicants
     */
    public function handleBulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:publish,unpublish,send_emails',
                'applicant_ids' => 'required|array|min:1',
                'applicant_ids.*' => 'exists:applicants,id',
                'notes' => 'nullable|string|max:500'
            ]);

            $action = $request->get('action');
            $applicantIds = $request->get('applicant_ids');
            $notes = $request->get('notes');

            DB::beginTransaction();

            switch ($action) {
                case 'publish':
                    $result = $this->bulkPublish($applicantIds, $notes, true);
                    break;

                case 'unpublish':
                    $result = $this->bulkUnpublish($applicantIds);
                    break;

                case 'send_emails':
                    $result = $this->bulkSendEmails($applicantIds);
                    break;

                default:
                    return new APIResource('Invalid action', true, 400);
            }

            DB::commit();
            return new APIResource($result, false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error handling bulk action: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Bulk publish applicants
     */
    private function bulkPublish($applicantIds, $notes = null, $sendEmails = false)
    {
        $publishedBy = auth()->user()->name ?? 'System';
        $applicants = Applicant::with(['programme', 'level'])
            ->whereIn('id', $applicantIds)
            ->where('admission_status', 'admitted')
            ->whereNull('published_at')
            ->get();

        $publishedCount = 0;
         $schoolName = Util::getConfigValue('school_name');
        $schoolLogo = Util::getConfigValue('school_logo');
        $level = Level::find(1);      
        foreach ($applicants as $applicant) {
            $applicant->publish($publishedBy, $notes);
            $publishedCount++;

            if ($sendEmails && $applicant->email) {
                SendAdmissionEmail::dispatch(
                    $applicant,
                    $schoolName,
                    $schoolLogo,
                    $applicant->programme,
                    $applicant->level?? $level
                )->onQueue('default');
            }
        }

        return [
            'message' => "Successfully published {$publishedCount} admission(s)",
            'published_count' => $publishedCount,
            'emails_queued' => $sendEmails ? $publishedCount : 0
        ];
    }

    /**
     * Bulk unpublish applicants
     */
    private function bulkUnpublish($applicantIds)
    {
        $applicants = Applicant::whereIn('id', $applicantIds)
            ->where('admission_status', 'admitted')
            ->whereNotNull('published_at')
            ->get();

        $unpublishedCount = 0;
        foreach ($applicants as $applicant) {
            $applicant->unpublish();
            $unpublishedCount++;
        }

        return [
            'message' => "Successfully unpublished {$unpublishedCount} admission(s)",
            'unpublished_count' => $unpublishedCount
        ];
    }

    /**
     * Bulk send emails to published applicants
     */
    private function bulkSendEmails($applicantIds)
    {
        $applicants = Applicant::with(['programme', 'level'])
            ->whereIn('id', $applicantIds)
            ->where('admission_status', 'admitted')
            ->whereNotNull('published_at')
            ->get();

        $emailsQueued = 0;
            $schoolName = Util::getConfigValue('school_name');
            $schoolLogo = Util::getConfigValue('school_logo');
            $level = Level::find(1);    
        foreach ($applicants as $applicant) {
            if ($applicant->email) {
                SendAdmissionEmail::dispatch(
                    $applicant,
                    $schoolName,
                    $schoolLogo,
                    $applicant->programme,
                    $applicant->level || $level
                )->onQueue('default');
                $emailsQueued++;
            }
        }

        return [
            'message' => "Successfully queued {$emailsQueued} admission email(s)",
            'emails_queued' => $emailsQueued
        ];
    }

    /**
     * Get applicants for document verification
     */
    public function getDocumentVerificationApplicants(Request $request)
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
                'programme_id' => 'nullable|exists:programmes,id',
                'verification_status' => 'nullable|in:pending,under_review,verified,rejected',
                'session_id' => 'nullable|exists:sessions,id'
            ]);

            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $programmeId = $request->get('programme_id');
            $verificationStatus = $request->get('verification_status');
            $sessionId = $request->get('session_id', session('current_session_id'));
            $filter = $request->all();
            $filter['session_id'] = $sessionId;
            $filter['acceptance_fee_paid'] = true;
            $query = Applicant::filter($filter)->with(['programme', 'level', 'session','documents']);
            if ($search) {
                $query->search($search);
            }

            $applicants = $query->paginate($perPage);

            // Add additional data for each applicant
            $applicants->getCollection()->transform(function ($applicant) {
                $applicant->documents_count = $applicant->documents->count();
                $applicant->verification_status = $applicant->verification_status ?? 'pending';
                return $applicant;
            });

            return new APIResource($applicants, false, 200);

        } catch (Exception $e) {
            Log::error('Error getting document verification applicants: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStats(Request $request)
    {
        try {
            $sessionId = $request->get('session_id', session('current_session_id'));

            $stats = [
                'pending' => Applicant::where('admission_status', 'admitted')
                    ->where('session_id', $sessionId)
                    ->where('verification_status', 'pending')
                    ->count(),
                'under_review' => Applicant::where('admission_status', 'admitted')
                    ->where('session_id', $sessionId)
                    ->where('verification_status', 'under_review')
                    ->count(),
                'verified' => Applicant::where('admission_status', 'admitted')
                    ->where('session_id', $sessionId)
                    ->where('verification_status', 'verified')
                    ->count(),
                'rejected' => Applicant::where('admission_status', 'admitted')
                    ->where('session_id', $sessionId)
                    ->where('verification_status', 'rejected')
                    ->count(),
            ];

            return new APIResource($stats, false, 200);

        } catch (Exception $e) {
            Log::error('Error getting verification stats: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get applicant documents for verification
     */
    public function getApplicantDocuments(Request $request, $applicantId)
    {
        try {
            $applicant = Applicant::findOrFail($applicantId);

            $documents = $applicant->documents()
                ->select(['id', 'document_type', 'file_name', 'file_path', 'file_type', 'verification_status', 'verification_notes', 'uploaded_at'])
                ->orderBy('document_type')
                ->get();

            return new APIResource($documents, false, 200);

        } catch (Exception $e) {
            Log::error('Error getting applicant documents: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get document preview URL
     */
    public function getDocumentPreview(Request $request, $applicantId, $documentId)
    {
        try {
            $applicant = Applicant::findOrFail($applicantId);
            $document = $applicant->documents()->findOrFail($documentId);

            // Generate secure preview URL
            $previewUrl = route('staff.documents.preview', [
                'applicant' => $applicantId,
                'document' => $documentId,
                'token' => encrypt(['applicant_id' => $applicantId, 'document_id' => $documentId, 'expires' => now()->addHours(2)])
            ]);

            return new APIResource([
                'preview_url' => $previewUrl,
                'file_type' => $document->file_type,
                'file_name' => $document->file_name
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error getting document preview: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Verify a document
     */
    public function verifyDocument(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|exists:applicants,id',
                'document_id' => 'required|integer',
                'verification_status' => 'required|in:approved,rejected,resubmit',
                'verification_notes' => 'nullable|string|max:1000'
            ]);

            $applicant = Applicant::findOrFail($request->applicant_id);
            $document = $applicant->documents()->findOrFail($request->document_id);

            $document->update([
                'verification_status' => $request->verification_status,
                'verification_notes' => $request->verification_notes,
                'verified_by' => auth()->id(),
                'verified_at' => now()
            ]);

            // Update applicant verification status if needed
            if ($request->verification_status === 'approved') {
                $this->updateApplicantVerificationStatus($applicant);
            }

            return new APIResource([
                'message' => 'Document verification updated successfully',
                'document' => $document
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error verifying document: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Complete document verification for an applicant
     */
    public function completeDocumentVerification(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|exists:applicants,id'
            ]);

            $applicant = Applicant::findOrFail($request->applicant_id);

            // Check if all documents are approved
            $totalDocuments = $applicant->documents()->count();
            $approvedDocuments = $applicant->documents()->where('verification_status', 'approved')->count();

            if ($totalDocuments === 0) {
                return new APIResource('No documents found for verification', true, 400);
            }

            if ($approvedDocuments !== $totalDocuments) {
                return new APIResource('Not all documents have been approved', true, 400);
            }

            // Update applicant verification status
            $applicant->update([
                'verification_status' => 'verified',
                'documents_verified_at' => now(),
                'documents_verified_by' => auth()->id()
            ]);

            return new APIResource([
                'message' => 'Document verification completed successfully',
                'applicant' => $applicant
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error completing document verification: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Generate admission letter preview
     */
    public function generateAdmissionLetterPreview(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|exists:applicants,id',
                'letter_type' => 'required|in:standard,conditional,provisional',
                'issue_date' => 'required|date',
                'issued_by' => 'required|string|max:255',
                'notes' => 'nullable|string|max:1000'
            ]);

            $applicant = Applicant::with(['programme', 'level', 'session'])->findOrFail($request->applicant_id);

            // Generate admission letter using the document service
            $documentService = new \App\Services\DocumentGenerationService();
            $admissionLetter = $documentService->generateAdmissionLetter($applicant);

            if (!$admissionLetter) {
                return new APIResource('Unable to generate admission letter preview', true, 500);
            }

            return new APIResource([
                'letter_html' => $admissionLetter['html'],
                'filename' => $admissionLetter['filename']
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error generating admission letter preview: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Issue admission letter
     */
    public function issueAdmissionLetter(Request $request)
    {
        try {
            $request->validate([
                'applicant_id' => 'required|exists:applicants,id',
                'letter_type' => 'required|in:standard,conditional,provisional',
                'issue_date' => 'required|date',
                'issued_by' => 'required|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'send_email' => 'boolean',
                'process_mode' => 'required|in:manual,automatic'
            ]);

            $applicant = Applicant::with(['programme', 'level', 'session'])->findOrFail($request->applicant_id);

            // Check if applicant is verified
            if ($applicant->verification_status !== 'verified') {
                return new APIResource('Applicant documents must be verified before issuing admission letter', true, 400);
            }

            DB::beginTransaction();

            // Generate admission letter
            $documentService = new \App\Services\DocumentGenerationService();
            $admissionLetter = $documentService->generateAdmissionLetter($applicant);

            if (!$admissionLetter) {
                return new APIResource('Unable to generate admission letter', true, 500);
            }

            // Update applicant status
            $applicant->update([
                'admission_letter_issued' => true,
                'admission_letter_issued_at' => now(),
                'admission_letter_issued_by' => auth()->id(),
                'admission_letter_type' => $request->letter_type,
                'admission_letter_notes' => $request->notes
            ]);

            // Send email if requested
            if ($request->send_email && $applicant->email) {
                // Queue admission letter email
                \App\Jobs\SendAdmissionLetterEmail::dispatch($applicant, $admissionLetter)
                    ->onQueue('default');
            }

            DB::commit();

            return new APIResource([
                'message' => 'Admission letter issued successfully',
                'applicant' => $applicant,
                'letter_html' => $admissionLetter['html']
            ], false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error issuing admission letter: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Update applicant verification status based on document approvals
     */
    private function updateApplicantVerificationStatus($applicant)
    {
        $totalDocuments = $applicant->documents()->count();
        $approvedDocuments = $applicant->documents()->where('verification_status', 'approved')->count();
        $rejectedDocuments = $applicant->documents()->where('verification_status', 'rejected')->count();

        if ($totalDocuments === 0) {
            return;
        }

        if ($approvedDocuments === $totalDocuments) {
            $status = 'verified';
        } elseif ($rejectedDocuments > 0) {
            $status = 'rejected';
        } else {
            $status = 'under_review';
        }

        $applicant->update(['verification_status' => $status]);
    }

    /**
     * Get admission configuration settings
     */
    public function getAdmissionConfiguration(Request $request)
    {
        try {
            $settings = [
                'admission_letter_process_mode' => \App\Services\Util::getConfigValue('admission_letter_process_mode') ?? 'manual',
                'auto_send_acknowledgment_email' => \App\Services\Util::getConfigValue('auto_send_acknowledgment_email') ?? true,
                'auto_send_notification_email' => \App\Services\Util::getConfigValue('auto_send_notification_email') ?? true,
                'auto_send_verification_email' => \App\Services\Util::getConfigValue('auto_send_verification_email') ?? true,
                'auto_send_admission_letter_email' => \App\Services\Util::getConfigValue('auto_send_admission_letter_email') ?? true,
            ];

            return new APIResource($settings, false, 200);

        } catch (Exception $e) {
            Log::error('Error getting admission configuration: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Save admission configuration settings
     */
    public function saveAdmissionConfiguration(Request $request)
    {
        try {
            $request->validate([
                'admission_letter_process_mode' => 'required|in:manual,automated',
                'auto_send_acknowledgment_email' => 'boolean',
                'auto_send_notification_email' => 'boolean',
                'auto_send_verification_email' => 'boolean',
                'auto_send_admission_letter_email' => 'boolean'
            ]);

            $settings = $request->only([
                'admission_letter_process_mode',
                'auto_send_acknowledgment_email',
                'auto_send_notification_email',
                'auto_send_verification_email',
                'auto_send_admission_letter_email'
            ]);

            foreach ($settings as $key => $value) {
                \App\Models\Configuration::updateOrCreate(
                    ['name' => $key],
                    ['value' => $value]
                );
            }

            return new APIResource([
                'message' => 'Admission configuration saved successfully',
                'settings' => $settings
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error saving admission configuration: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }


}
