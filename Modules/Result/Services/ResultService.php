<?php

namespace Modules\Result\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Result\Repositories\ResultRepository;

class ResultService
{
    private $resultRepository;

    public function __construct(ResultRepository $resultRepository)
    {
        $this->resultRepository = $resultRepository;
    }

    /**
     * Compute results for students
     */
    public function computeResults($request)
    {
        $sessionId = $request->get('session_id');
        $levelId = $request->get('level_id');
        $programmeId = $request->get('programme_id');
        $programmeCurriculumId = $request->get('programme_curriculum_id');
        $semester = $request->get('semester');

        return $this->resultRepository->computeResults($sessionId, $levelId, $programmeId, $programmeCurriculumId, $semester);
    }

    /**
     * Compute individual student result
     */
    public function computeIndividualResult($request)
    {
        $studentId = $request->get('student_id');
        $sessionId = $request->get('session_id');
        $semester = $request->get('semester');

        return $this->resultRepository->computeIndividualResult($studentId, $sessionId, $semester);
    }

    /**
     * Get students for result computation
     */
    public function getStudentsForComputation($request)
    {
        $sessionId = $request->get('session_id');
        $levelId = $request->get('level_id');
        $programmeId = $request->get('programme_id');
        $programmeCurriculumId = $request->get('programme_curriculum_id');
        $semester = $request->get('semester');

        return $this->resultRepository->getStudentsForComputation($sessionId, $levelId, $programmeId, $programmeCurriculumId, $semester);
    }

    /**
     * Get students with existing results/scores for a specific course
     */
    public function getStudentsWithResults($request)
    {
        $sessionId = $request->get('session_id');
        $levelId = $request->get('level_id');
        $programmeId = $request->get('programme_id');
        $programmeCurriculumId = $request->get('programme_curriculum_id');
        $courseId = $request->get('course_id');
        $semester = $request->get('semester');

        return $this->resultRepository->getStudentsWithResults($sessionId, $levelId, $programmeId, $programmeCurriculumId, $courseId, $semester);
    }

    /**
     * Save batch results
     */
    public function saveBatchResults($request)
    {
        $courseId = $request->get('course_id');
        $sessionId = $request->get('session_id');
        $semester = $request->get('semester');
        $results = $request->get('results');

        DB::beginTransaction();
        try {
            foreach ($results as $result) {
                $this->resultRepository->saveStudentResult([
                    'student_id' => $result['student_id'],
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                    'semester' => $semester,
                    'ca_score' => $result['ca_score'] ?? 0,
                    'exam_score' => $result['exam_score'] ?? 0,
                    'total_score' => $result['total_score'] ?? 0,
                    'grade' => $result['grade'] ?? 'F',
                    'grade_point' => $result['grade_point'] ?? 0.0,
                    'created_by' => auth('api-staff')->id()
                ]);
            }

            DB::commit();
            return 'Results saved successfully';
        } catch (Exception $e) {
            DB::rollback();
            throw new Exception('Failed to save results: ' . $e->getMessage());
        }
    }

    /**
     * Bulk upload results from file
     */
    public function bulkUploadResults($request)
    {
        $file = $request->file('file');
        $sessionId = $request->get('session_id');
        $levelId = $request->get('level_id');
        $programmeId = $request->get('programme_id');
        $programmeCurriculumId = $request->get('programme_curriculum_id');
        $courseId = $request->get('course_id');
        $semester = $request->get('semester');

        return $this->resultRepository->bulkUploadResults($file, $sessionId, $levelId, $programmeId, $programmeCurriculumId, $courseId, $semester);
    }
}
