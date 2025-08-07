<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\APIResource;
use App\Models\Curriculum;
use App\Models\ProgrammeCurriculum;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CurriculumController extends Controller
{
    /**
     * Get all curriculums
     */
    public function index(Request $request)
    {
        try {
            $query = Curriculum::with(['programmeCurriculums.programme']);
            
            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }
            
            // Filter by academic year
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }
            
            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $curriculums = $query->orderBy('created_at', 'desc')->get();
            
            return new APIResource($curriculums, false, 200);
            
        } catch (Exception $e) {
            Log::error('Error fetching curriculums: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Store a new curriculum
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'academic_year' => 'required|integer|min:2020|max:2050',
                'effective_date' => 'required|date',
                'expiry_date' => 'nullable|date|after:effective_date',
                'is_active' => 'boolean',
                'metadata' => 'nullable|array'
            ]);
            
            DB::beginTransaction();
            
            // If setting as active, deactivate all other curriculums first
            if ($request->get('is_active', false)) {
                Curriculum::where('is_active', true)->update(['is_active' => false]);
            }

            $curriculum = Curriculum::create([
                'name' => $request->name,
                'description' => $request->description,
                'academic_year' => $request->academic_year,
                'effective_date' => $request->effective_date,
                'expiry_date' => $request->expiry_date,
                'is_active' => $request->get('is_active', false),
                'metadata' => $request->metadata,
                'created_by' => auth()->user()->name ?? 'System'
            ]);
            
            DB::commit();
            
            return new APIResource([
                'message' => 'Curriculum created successfully',
                'curriculum' => $curriculum->load('programmeCurriculums.programme')
            ], false, 201);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Show a specific curriculum
     */
    public function show($id)
    {
        try {
            $curriculum = Curriculum::with(['programmeCurriculums.programme'])
                                  ->findOrFail($id);
            
            return new APIResource($curriculum, false, 200);
            
        } catch (Exception $e) {
            Log::error('Error fetching curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 404);
        }
    }

    /**
     * Update a curriculum
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'academic_year' => 'required|integer|min:2020|max:2050',
                'effective_date' => 'required|date',
                'expiry_date' => 'nullable|date|after:effective_date',
                'is_active' => 'boolean',
                'metadata' => 'nullable|array'
            ]);
            
            DB::beginTransaction();

            $curriculum = Curriculum::findOrFail($id);

            // If setting as active, deactivate all other curriculums first
            if ($request->get('is_active', false)) {
                Curriculum::where('id', '!=', $id)->where('is_active', true)->update(['is_active' => false]);
            }

            $curriculum->update([
                'name' => $request->name,
                'description' => $request->description,
                'academic_year' => $request->academic_year,
                'effective_date' => $request->effective_date,
                'expiry_date' => $request->expiry_date,
                'is_active' => $request->get('is_active', false),
                'metadata' => $request->metadata,
                'updated_by' => auth()->user()->name ?? 'System'
            ]);
            
            DB::commit();
            
            return new APIResource([
                'message' => 'Curriculum updated successfully',
                'curriculum' => $curriculum->load('programmeCurriculums.programme')
            ], false, 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Delete a curriculum
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $curriculum = Curriculum::findOrFail($id);
            
            // Check if curriculum has programme curriculums
            if ($curriculum->programmeCurriculums()->count() > 0) {
                return new APIResource('Cannot delete curriculum with associated programme curriculums', true, 400);
            }
            
            $curriculum->delete();
            
            DB::commit();
            
            return new APIResource('Curriculum deleted successfully', false, 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Activate a curriculum
     */
    public function activate($id)
    {
        try {
            DB::beginTransaction();

            $curriculum = Curriculum::findOrFail($id);
            $curriculum->activate(); // This method handles deactivating others

            DB::commit();

            return new APIResource([
                'message' => 'Curriculum activated successfully. All other curriculums have been deactivated.',
                'curriculum' => $curriculum->load('programmeCurriculums.programme')
            ], false, 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error activating curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get active curriculum
     */
    public function getActive()
    {
        try {
            $curriculum = Curriculum::active()
                                  ->with(['programmeCurriculums.programme'])
                                  ->first();

            if (!$curriculum) {
                return new APIResource('No active curriculum found', true, 404);
            }

            return new APIResource($curriculum, false, 200);

        } catch (Exception $e) {
            Log::error('Error fetching active curriculum: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get applications statistics by programme
     */
    public function getApplicationsByProgramme()
    {
        try {
            // Get current active session
            $currentSession = \App\Models\Session::where('is_active', true)->first();

            if (!$currentSession) {
                return new APIResource('No active session found', true, 404);
            }

            // Get applications count by programme curriculum for current session
            $applicationStats = \App\Models\Applicant::select(
                    'programme_curriculum_id',
                    DB::raw('COUNT(*) as total_applications')
                )
                ->where('session_id', $currentSession->id)
                ->whereNotNull('programme_curriculum_id')
                ->groupBy('programme_curriculum_id')
                ->with(['programmeCurriculum.programme'])
                ->get();

            // Calculate total applications
            $totalApplications = $applicationStats->sum('total_applications');

            // Format the data with percentages
            $formattedStats = $applicationStats->map(function ($stat) use ($totalApplications) {
                $percentage = $totalApplications > 0 ? round(($stat->total_applications / $totalApplications) * 100, 1) : 0;

                return [
                    'programme_curriculum_id' => $stat->programme_curriculum_id,
                    'programme_name' => $stat->programmeCurriculum->programme->name ?? 'Unknown Programme',
                    'programme_code' => $stat->programmeCurriculum->programme->code ?? null,
                    'total_applications' => $stat->total_applications,
                    'percentage' => $percentage,
                    'curriculum_name' => $stat->programmeCurriculum->curriculum->name ?? null
                ];
            })->sortByDesc('total_applications')->values();

            // Get top 5 programmes and group others
            $topProgrammes = $formattedStats->take(5);
            $otherProgrammes = $formattedStats->skip(5);

            $result = $topProgrammes->toArray();

            // Add "Others" category if there are more than 5 programmes
            if ($otherProgrammes->count() > 0) {
                $othersTotal = $otherProgrammes->sum('total_applications');
                $othersPercentage = $totalApplications > 0 ? round(($othersTotal / $totalApplications) * 100, 1) : 0;

                $result[] = [
                    'programme_curriculum_id' => null,
                    'programme_name' => 'Others',
                    'programme_code' => null,
                    'total_applications' => $othersTotal,
                    'percentage' => $othersPercentage,
                    'curriculum_name' => null,
                    'programmes_count' => $otherProgrammes->count()
                ];
            }

            return new APIResource([
                'session' => $currentSession,
                'total_applications' => $totalApplications,
                'programmes' => $result,
                'last_updated' => now()->toDateTimeString()
            ], false, 200);

        } catch (Exception $e) {
            Log::error('Error fetching applications by programme: ' . $e->getMessage());
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
