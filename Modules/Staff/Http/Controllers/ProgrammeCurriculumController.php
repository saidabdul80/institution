<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\APIResource;
use App\Models\ProgrammeCurriculum;
use App\Models\Curriculum;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProgrammeCurriculumController extends Controller
{
    /**
     * Get all programme curriculums
     */
    public function index(Request $request)
    {
        try {
            $query = ProgrammeCurriculum::with(['curriculum', 'programme']);
            
            // Filter by curriculum
            if ($request->has('curriculum_id')) {
                $query->where('curriculum_id', $request->curriculum_id);
            }
            
            // Filter by programme
            if ($request->has('programme_id')) {
                $query->where('programme_id', $request->programme_id);
            }
            
            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->notActive();
                }
            }
            
            // Filter by active curriculum only
            if ($request->get('active_curriculum_only', false)) {
                $query->withActiveCurriculum();
            }
            
            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('programme', function($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            }
            
            $programmeCurriculums = $query->orderBy('created_at', 'desc')->paginate(20);
            
            return new APIResource($programmeCurriculums, false, 200);
            
        } catch (Exception $e) {
            Log::error('Error fetching programme curriculums: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Store a new programme curriculum
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'curriculum_id' => 'required|exists:curriculums,id',
                'programme_id' => 'required|exists:programmes,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'duration_years' => 'required|integer|min:1|max:10',
                'duration_semesters' => 'required|integer|min:2|max:20',
                'minimum_cgpa' => 'required|numeric|min:0|max:5',
                'minimum_credit_units' => 'required|integer|min:1',
                'admission_requirements' => 'nullable|array',
                'graduation_requirements' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);

            // Check for existing programme curriculum with same curriculum and programme
            $existing = ProgrammeCurriculum::where('curriculum_id', $request->curriculum_id)
                                         ->where('programme_id', $request->programme_id)
                                         ->first();

            if ($existing) {
                return new APIResource('A programme curriculum already exists for this curriculum and programme combination', true, 400);
            }

            DB::beginTransaction();
            
            $programmeCurriculum = ProgrammeCurriculum::create([
                'curriculum_id' => $request->curriculum_id,
                'programme_id' => $request->programme_id,
                'name' => $request->name,
                'description' => $request->description,
                'duration_years' => $request->duration_years,
                'duration_semesters' => $request->duration_semesters,
                'minimum_cgpa' => $request->minimum_cgpa,
                'minimum_credit_units' => $request->minimum_credit_units,
                'admission_requirements' => $request->admission_requirements,
                'graduation_requirements' => $request->graduation_requirements,
                'metadata' => $request->metadata,
                'created_by' => auth()->user()->name ?? 'System'
            ]);
            
    
            DB::commit();
            
            return new APIResource([
                'message' => 'Programme curriculum created successfully',
                'programme_curriculum' => $programmeCurriculum->load(['curriculum', 'programme'])
            ], false, 201);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating programme curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Show a specific programme curriculum
     */
    public function show($id)
    {
        try {
            $programmeCurriculum = ProgrammeCurriculum::with(['curriculum', 'programme'])
                                                    ->findOrFail($id);
            
            return new APIResource($programmeCurriculum, false, 200);
            
        } catch (Exception $e) {
            Log::error('Error fetching programme curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 404);
        }
    }

    /**
     * Update a programme curriculum
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'curriculum_id' => 'required|exists:curriculums,id',
                'programme_id' => 'required|exists:programmes,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'duration_years' => 'required|integer|min:1|max:10',
                'duration_semesters' => 'required|integer|min:2|max:20',
                'minimum_cgpa' => 'required|numeric|min:0|max:5',
                'minimum_credit_units' => 'required|integer|min:1',
                'admission_requirements' => 'nullable|array',
                'graduation_requirements' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);

            // Check for existing programme curriculum with same curriculum and programme (excluding current record)
            $existing = ProgrammeCurriculum::where('curriculum_id', $request->curriculum_id)
                                         ->where('programme_id', $request->programme_id)
                                         ->where('id', '!=', $id)
                                         ->first();

            if ($existing) {
                return new APIResource('A programme curriculum already exists for this curriculum and programme combination', true, 400);
            }

            DB::beginTransaction();
            
            $programmeCurriculum = ProgrammeCurriculum::findOrFail($id);
            
            $programmeCurriculum->update([
                'curriculum_id' => $request->curriculum_id,
                'programme_id' => $request->programme_id,
                'name' => $request->name,
                'description' => $request->description,
                'duration_years' => $request->duration_years,
                'duration_semesters' => $request->duration_semesters,
                'minimum_cgpa' => $request->minimum_cgpa,
                'minimum_credit_units' => $request->minimum_credit_units,
                'admission_requirements' => $request->admission_requirements,
                'graduation_requirements' => $request->graduation_requirements,
                'metadata' => $request->metadata,
                'updated_by' => auth()->user()->name ?? 'System'
            ]);
     
            DB::commit();
            
            return new APIResource([
                'message' => 'Programme curriculum updated successfully',
                'programme_curriculum' => $programmeCurriculum->load(['curriculum', 'programme'])
            ], false, 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating programme curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Delete a programme curriculum
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $programmeCurriculum = ProgrammeCurriculum::findOrFail($id);
            
            // Check if programme curriculum has students or applicants
            $hasStudents = $programmeCurriculum->students()->count() > 0;
            $hasApplicants = $programmeCurriculum->applicants()->count() > 0;
            
            if ($hasStudents || $hasApplicants) {
                return new APIResource('Cannot delete programme curriculum with associated students or applicants', true, 400);
            }
            
            $programmeCurriculum->delete();
            
            DB::commit();
            
            return new APIResource('Programme curriculum deleted successfully', false, 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting programme curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Activate a programme curriculum
     */
    public function activate($id)
    {
        try {
            DB::beginTransaction();
            
            $programmeCurriculum = ProgrammeCurriculum::findOrFail($id);
            
            DB::commit();
            
            return new APIResource([
                'message' => 'Programme curriculum activated successfully',
                'programme_curriculum' => $programmeCurriculum->load(['curriculum', 'programme'])
            ], false, 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error activating programme curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get active programme curriculums for frontend display
     */
    public function getActiveForFrontend()
    {
        try {
            $programmeCurriculums = ProgrammeCurriculum::active()
                                                     ->withActiveCurriculum()
                                                     ->with(['curriculum', 'programme'])
                                                     ->get();
            
            return new APIResource($programmeCurriculums, false, 200);
            
        } catch (Exception $e) {
            Log::error('Error fetching active programme curriculums: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
