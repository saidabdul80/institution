<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use App\Models\Course;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Staff\Entities\StaffCourseAllocation;

class StaffCourseController extends Controller
{
    /**
     * Get all staff members
     */
    public function getAllStaff(Request $request)
    {
        try {
            $query = Staff::query();

            // Filter by department if provided
            if ($request->department_id) {
                $query->where('department_id', $request->department_id);
            }

            // Filter by faculty if provided
            if ($request->faculty_id) {
                $query->where('faculty_id', $request->faculty_id);
            }

            // Search by name or staff ID
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                      ->orWhere('last_name', 'like', '%' . $request->search . '%')
                      ->orWhere('staff_id', 'like', '%' . $request->search . '%');
                });
            }

            $staff = $query->with(['department', 'faculty'])
                          ->orderBy('first_name')
                          ->get();

            return new APIResource($staff, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get all courses
     */
    public function getAllCourses(Request $request)
    {
        try {
            $query = Course::query();

            // Filter by level if provided
            if ($request->level_id) {
                $query->where('level_id', $request->level_id);
            }

            // Filter by programme if provided
            if ($request->programme_id) {
                $query->where('programme_id', $request->programme_id);
            }

            // Filter by department if provided
            if ($request->department_id) {
                $query->where('department_id', $request->department_id);
            }

            // Filter by semester if provided
            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            // Search by course code or title
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('course_code', 'like', '%' . $request->search . '%')
                      ->orWhere('course_title', 'like', '%' . $request->search . '%');
                });
            }

            $courses = $query->with(['level', 'programme', 'department'])
                            ->orderBy('course_code')
                            ->get();

            return new APIResource($courses, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get courses allocated to a specific staff member
     */
    public function getStaffAllocatedCourses(Request $request, $staffId)
    {
        try {
            $query = StaffCourseAllocation::with([
                'course.level', 
                'course.programme', 
                'course.department',
                'session',
                'programme',
                'level'
            ])->where('staff_id', $staffId);

            // Filter by session if provided
            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            // Filter by semester if provided
            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            // Filter by allocation type if provided
            if ($request->allocation_type) {
                $query->where('allocation_type', $request->allocation_type);
            }

            // Only active allocations by default
            if (!$request->has('include_inactive')) {
                $query->where('is_active', true);
            }

            $allocations = $query->orderBy('created_at', 'desc')->get();

            return new APIResource($allocations, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get staff members allocated to a specific course
     */
    public function getCourseAllocatedStaff(Request $request, $courseId)
    {
        try {
            $query = StaffCourseAllocation::with([
                'staff.department', 
                'staff.faculty',
                'session',
                'programme',
                'level'
            ])->where('course_id', $courseId);

            // Filter by session if provided
            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            // Filter by semester if provided
            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            // Filter by allocation type if provided
            if ($request->allocation_type) {
                $query->where('allocation_type', $request->allocation_type);
            }

            // Only active allocations by default
            if (!$request->has('include_inactive')) {
                $query->where('is_active', true);
            }

            $allocations = $query->orderBy('created_at', 'desc')->get();

            return new APIResource($allocations, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Bulk create course allocations
     */
    public function bulkCreateAllocations(Request $request)
    {
        try {
            $request->validate([
                'allocations' => 'required|array|min:1',
                'allocations.*.staff_id' => 'required|exists:staff,id',
                'allocations.*.course_id' => 'required|exists:courses,id',
                'allocations.*.session_id' => 'required|exists:sessions,id',
                'allocations.*.semester' => 'required|integer|min:1|max:3',
                'allocations.*.programme_id' => 'required|exists:programmes,id',
                'allocations.*.level_id' => 'required|exists:levels,id',
                'allocations.*.allocation_type' => 'required|in:lecturer,coordinator,examiner',
                'allocations.*.remarks' => 'nullable|string|max:500'
            ]);

            $createdAllocations = [];
            $errors = [];

            foreach ($request->allocations as $index => $allocationData) {
                try {
                    // Check if allocation already exists
                    $existing = StaffCourseAllocation::where([
                        'staff_id' => $allocationData['staff_id'],
                        'course_id' => $allocationData['course_id'],
                        'session_id' => $allocationData['session_id'],
                        'semester' => $allocationData['semester']
                    ])->first();

                    if ($existing) {
                        $errors[] = "Allocation #{".$index++."}: Staff already allocated to this course for the same session/semester";
                        continue;
                    }

                    $allocation = StaffCourseAllocation::create(array_merge($allocationData, [
                        'allocated_by' => auth()->id(),
                        'allocated_at' => now()
                    ]));

                    $allocation->load(['staff', 'course', 'session', 'programme', 'level']);
                    $createdAllocations[] = $allocation;

                } catch (Exception $e) {
                    $errors[] = "Allocation #".$index++ . $e->getMessage();
                }
            }

            return new APIResource([
                'created_allocations' => $createdAllocations,
                'errors' => $errors,
                'success_count' => count($createdAllocations),
                'error_count' => count($errors)
            ], false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get allocation statistics
     */
    public function getAllocationStatistics(Request $request)
    {
        try {
            $query = StaffCourseAllocation::query();

            // Filter by session if provided
            if ($request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            // Filter by semester if provided
            if ($request->semester) {
                $query->where('semester', $request->semester);
            }

            $totalAllocations = $query->count();
            $activeAllocations = $query->where('is_active', true)->count();
            $lecturerAllocations = $query->where('allocation_type', 'lecturer')->count();
            $coordinatorAllocations = $query->where('allocation_type', 'coordinator')->count();
            $examinerAllocations = $query->where('allocation_type', 'examiner')->count();

            // Get top allocated staff
            $topAllocatedStaff = StaffCourseAllocation::with('staff')
                ->select('staff_id', DB::raw('count(*) as allocation_count'))
                ->groupBy('staff_id')
                ->orderBy('allocation_count', 'desc')
                ->limit(5)
                ->get();

            // Get courses with most allocations
            $topAllocatedCourses = StaffCourseAllocation::with('course')
                ->select('course_id', DB::raw('count(*) as allocation_count'))
                ->groupBy('course_id')
                ->orderBy('allocation_count', 'desc')
                ->limit(5)
                ->get();

            return new APIResource([
                'total_allocations' => $totalAllocations,
                'active_allocations' => $activeAllocations,
                'inactive_allocations' => $totalAllocations - $activeAllocations,
                'lecturer_allocations' => $lecturerAllocations,
                'coordinator_allocations' => $coordinatorAllocations,
                'examiner_allocations' => $examinerAllocations,
                'top_allocated_staff' => $topAllocatedStaff,
                'top_allocated_courses' => $topAllocatedCourses
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Copy allocations from previous session/semester
     */
    public function copyAllocations(Request $request)
    {
        try {
            $request->validate([
                'from_session_id' => 'required|exists:sessions,id',
                'from_semester' => 'required|integer|min:1|max:3',
                'to_session_id' => 'required|exists:sessions,id',
                'to_semester' => 'required|integer|min:1|max:3',
                'allocation_types' => 'nullable|array',
                'allocation_types.*' => 'in:lecturer,coordinator,examiner',
                'staff_ids' => 'nullable|array',
                'staff_ids.*' => 'exists:staff,id'
            ]);

            $query = StaffCourseAllocation::where('session_id', $request->from_session_id)
                                        ->where('semester', $request->from_semester)
                                        ->where('is_active', true);

            // Filter by allocation types if provided
            if ($request->allocation_types) {
                $query->whereIn('allocation_type', $request->allocation_types);
            }

            // Filter by staff IDs if provided
            if ($request->staff_ids) {
                $query->whereIn('staff_id', $request->staff_ids);
            }

            $sourceAllocations = $query->get();
            $copiedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($sourceAllocations as $allocation) {
                try {
                    // Check if allocation already exists in target session/semester
                    $existing = StaffCourseAllocation::where([
                        'staff_id' => $allocation->staff_id,
                        'course_id' => $allocation->course_id,
                        'session_id' => $request->to_session_id,
                        'semester' => $request->to_semester
                    ])->first();

                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }

                    // Create new allocation
                    StaffCourseAllocation::create([
                        'staff_id' => $allocation->staff_id,
                        'course_id' => $allocation->course_id,
                        'session_id' => $request->to_session_id,
                        'semester' => $request->to_semester,
                        'programme_id' => $allocation->programme_id,
                        'level_id' => $allocation->level_id,
                        'allocation_type' => $allocation->allocation_type,
                        'remarks' => $allocation->remarks,
                        'allocated_by' => auth()->id(),
                        'allocated_at' => now()
                    ]);

                    $copiedCount++;

                } catch (Exception $e) {
                    $errors[] = "Failed to copy allocation for staff {$allocation->staff_id}: " . $e->getMessage();
                }
            }

            return new APIResource([
                'copied_count' => $copiedCount,
                'skipped_count' => $skippedCount,
                'error_count' => count($errors),
                'errors' => $errors,
                'message' => "Successfully copied {$copiedCount} allocations, skipped {$skippedCount} existing allocations"
            ], false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
