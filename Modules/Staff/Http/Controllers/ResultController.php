<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Resources\APIResource;
use Modules\Staff\Services\ResultService;
use Modules\Staff\Services\ResultComputationService;
use Modules\Staff\Entities\StudentSemesterGpa;
use Modules\Staff\Entities\StaffCourseAllocation;
use Modules\Staff\Entities\ResultCompilationLog;

class ResultController extends Controller
{
    protected $resultService;
    protected $resultComputationService;

    public function __construct(ResultService $resultService, ResultComputationService $resultComputationService)
    {
        $this->resultService = $resultService;
        $this->resultComputationService = $resultComputationService;
    }

    /**
     * Compute results for students
     */
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

    /**
     * Compute individual student result
     */
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

    /**
     * Get students for result computation
     */
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

    /**
     * Get students with existing results/scores
     */
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

    /**
     * Save batch results
     */
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

    /**
     * Bulk upload results from file
     */
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

    /**
     * Compile results using advanced GPA calculation
     */
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
            return new APIResource($e->getMessage(), true, 500);
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
     * Get staff course allocations
     */
    public function getStaffCourseAllocations(Request $request)
    {
        try {
            $request->validate([
                'staff_id' => 'nullable|exists:staff,id',
                'session_id' => 'nullable|exists:sessions,id',
                'semester' => 'nullable|integer|min:1|max:3',
                'course_id' => 'nullable|exists:courses,id',
                'allocation_type' => 'nullable|in:lecturer,coordinator,examiner'
            ]);

            $query = StaffCourseAllocation::with(['staff', 'course', 'session', 'programme', 'level', 'allocatedBy']);

            if ($request->staff_id) {
                $query->where('staff_id', $request->staff_id);
            }

            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            if ($request->course_id) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->allocation_type) {
                $query->where('allocation_type', $request->allocation_type);
            }

            $allocations = $query->active()
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);

            return new APIResource($allocations, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Create staff course allocation
     */
    public function createStaffCourseAllocation(Request $request)
    {
        try {
            $request->validate([
                'staff_id' => 'required|exists:staff,id',
                'course_id' => 'required|exists:courses,id',
                'session_id' => 'required|exists:sessions,id',
                'semester' => 'required|integer|min:1|max:3',
                'programme_id' => 'required|exists:programmes,id',
                'level_id' => 'required|exists:levels,id',
                'allocation_type' => 'required|in:lecturer,coordinator,examiner',
                'remarks' => 'nullable|string|max:500'
            ]);

            $allocation = StaffCourseAllocation::create([
                'staff_id' => $request->staff_id,
                'course_id' => $request->course_id,
                'session_id' => $request->session_id,
                'semester' => $request->semester,
                'programme_id' => $request->programme_id,
                'level_id' => $request->level_id,
                'allocation_type' => $request->allocation_type,
                'remarks' => $request->remarks,
                'allocated_by' => auth()->id(),
                'allocated_at' => now()
            ]);

            $allocation->load(['staff', 'course', 'session', 'programme', 'level']);

            return new APIResource($allocation, false, 201);

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
     * Update staff course allocation
     */
    public function updateStaffCourseAllocation(Request $request, $id)
    {
        try {
            $allocation = StaffCourseAllocation::findOrFail($id);

            $request->validate([
                'staff_id' => 'sometimes|exists:staff,id',
                'course_id' => 'sometimes|exists:courses,id',
                'session_id' => 'sometimes|exists:sessions,id',
                'semester' => 'sometimes|integer|min:1|max:3',
                'programme_id' => 'sometimes|exists:programmes,id',
                'level_id' => 'sometimes|exists:levels,id',
                'allocation_type' => 'sometimes|in:lecturer,coordinator,examiner',
                'remarks' => 'nullable|string|max:500',
                'is_active' => 'sometimes|boolean'
            ]);

            $allocation->update($request->only([
                'staff_id', 'course_id', 'session_id', 'semester',
                'programme_id', 'level_id', 'allocation_type',
                'remarks', 'is_active'
            ]));

            $allocation->load(['staff', 'course', 'session', 'programme', 'level']);

            return new APIResource($allocation, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Delete staff course allocation
     */
    public function deleteStaffCourseAllocation($id)
    {
        try {
            $allocation = StaffCourseAllocation::findOrFail($id);
            $allocation->delete();

            return new APIResource('Allocation deleted successfully', false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
