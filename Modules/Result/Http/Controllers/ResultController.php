<?php

namespace Modules\Result\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Resources\APIResource;
use Modules\Result\Services\ResultService;
use Modules\Result\Services\ResultComputationService;
use Modules\Result\Entities\StudentSemesterGpa;
use Modules\Result\Entities\ResultCompilationLog;
use Modules\Result\Entities\GradeSetting;

class ResultController extends Controller
{
    private $resultService;
    private $resultComputationService;

    public function __construct(ResultService $resultService, ResultComputationService $resultComputationService)
    {
        $this->resultService = $resultService;
        $this->resultComputationService = $resultComputationService;
    }

    public function computeResults(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required',
                'level_id' => 'required',
                'programme_id' => 'required',
                'semester' => 'required'
            ]);

            $response = $this->resultService->computeResults($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function computeIndividualResult(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required',
                'session_id' => 'required',
                'semester' => 'required'
            ]);

            $response = $this->resultService->computeIndividualResult($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function getStudentsForComputation(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required',
                'level_id' => 'required',
                'programme_id' => 'required',
                'semester' => 'required'
            ]);

            $response = $this->resultService->getStudentsForComputation($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function getStudentsWithResults(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required',
                'level_id' => 'required',
                'programme_id' => 'required',
                'course_id' => 'required',
                'semester' => 'required'
            ]);

            $response = $this->resultService->getStudentsWithResults($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function saveBatchResults(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required',
                'session_id' => 'required',
                'semester' => 'required',
                'results' => 'required|array',
                'results.*.student_id' => 'required',
                'results.*.ca_score' => 'nullable|numeric|min:0|max:30',
                'results.*.exam_score' => 'nullable|numeric|min:0|max:70',
                'results.*.total_score' => 'nullable|numeric|min:0|max:100',
                'results.*.grade' => 'nullable|string',
                'results.*.grade_point' => 'nullable|numeric|min:0|max:5'
            ]);

            $response = $this->resultService->saveBatchResults($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function bulkUploadResults(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls',
                'session_id' => 'required',
                'level_id' => 'required',
                'programme_id' => 'required',
                'course_id' => 'required',
                'semester' => 'required'
            ]);

            $response = $this->resultService->bulkUploadResults($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function compileAdvancedResults(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:sessions,id',
                'semester' => 'required|integer|min:1|max:3',
                'level_id' => 'required|exists:levels,id',
                'programme_id' => 'nullable|exists:programmes,id',
                'department_id' => 'nullable|exists:departments,id'
            ]);

            $result = $this->resultComputationService->compileResults(
                $request->session_id,
                $request->semester,
                $request->level_id,
                $request->programme_id,
                $request->department_id,
                auth()->id()
            );

            return new APIResource($result, !$result['success'], $result['success'] ? 200 : 400);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get student semester GPA records
     */
    public function getStudentSemesterGpa(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'nullable|exists:students,id',
                'session_id' => 'nullable|exists:sessions,id',
                'semester' => 'nullable|integer|min:1|max:3',
                'level_id' => 'nullable|exists:levels,id',
                'programme_id' => 'nullable|exists:programmes,id'
            ]);

            $query = StudentSemesterGpa::with(['student', 'session', 'level', 'programme', 'compiledBy']);

            if ($request->student_id) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            if ($request->level_id) {
                $query->where('level_id', $request->level_id);
            }

            if ($request->programme_id) {
                $query->where('programme_id', $request->programme_id);
            }

            $gpaRecords = $query->orderBy('session_id', 'desc')
                ->orderBy('semester', 'desc')
                ->paginate(50);

            return new APIResource($gpaRecords, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get result compilation logs
     */
    public function getResultCompilationLogs(Request $request)
    {
        try {
            $query = ResultCompilationLog::with(['session', 'level', 'programme', 'department', 'faculty', 'compiledBy']);

            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            if ($request->level_id) {
                $query->where('level_id', $request->level_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate(20);

            return new APIResource($logs, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get grade settings for a programme (with fallback to general)
     */
    public function getGradeSettings(Request $request)
    {
        try {
            $programmeId = $request->get('programme_id');

            $gradeSettings = GradeSetting::getGradeScaleForProgramme($programmeId);

            return new APIResource([
                'grade_settings' => $gradeSettings,
                'is_programme_specific' => $programmeId && $gradeSettings->first()?->isProgrammeSpecific(),
                'programme_id' => $programmeId
            ], false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get general grade settings only
     */
    public function getGeneralGradeSettings()
    {
        try {
            $gradeSettings = GradeSetting::getGeneralGradeScale();

            return new APIResource($gradeSettings, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Create or update grade setting
     */
    public function saveGradeSetting(Request $request)
    {
        try {
            $request->validate([
                'programme_id' => 'nullable|exists:programmes,id',
                'min_score' => 'required|numeric|min:0|max:100',
                'max_score' => 'required|numeric|min:0|max:100|gte:min_score',
                'grade' => 'required|string|max:2',
                'grade_point' => 'required|numeric|min:0|max:5',
                'status' => 'required|in:pass,fail'
            ]);

            $gradeSettingData = $request->only([
                'programme_id',
                'min_score',
                'max_score',
                'grade',
                'grade_point',
                'status'
            ]);
            $gradeSettingData['created_by'] = auth('api-staff')->id();

            if ($request->has('id') && $request->id) {
                // Update existing
                $gradeSetting = GradeSetting::findOrFail($request->id);
                $gradeSetting->update($gradeSettingData);
                $message = 'Grade setting updated successfully';
            } else {
                // Create new
                $gradeSetting = GradeSetting::create($gradeSettingData);
                $message = 'Grade setting created successfully';
            }

            return new APIResource([
                'grade_setting' => $gradeSetting,
                'message' => $message
            ], false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
