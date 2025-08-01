<?php

namespace Modules\Staff\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Http\Resources\APIResource;
use Modules\Staff\Services\TranscriptService;

class TranscriptController extends Controller
{
    protected $transcriptService;

    public function __construct(TranscriptService $transcriptService)
    {
        $this->transcriptService = $transcriptService;
    }

    /**
     * Generate transcript for a student
     */
    public function generateTranscript(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required',
                'type' => 'required|in:official,unofficial,interim',
                'format' => 'required|in:pdf,excel,word',
                'include_grades' => 'boolean',
                'include_gpa' => 'boolean',
                'include_class_rank' => 'boolean',
                'include_degree_class' => 'boolean',
                'from_session' => 'nullable',
                'to_session' => 'nullable'
            ]);

            $response = $this->transcriptService->generateTranscript($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Email transcript to student
     */
    public function emailTranscript(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required',
                'email' => 'required|email',
                'type' => 'required|in:official,unofficial,interim',
                'format' => 'required|in:pdf,excel,word',
                'include_grades' => 'boolean',
                'include_gpa' => 'boolean',
                'include_class_rank' => 'boolean',
                'include_degree_class' => 'boolean',
                'from_session' => 'nullable',
                'to_session' => 'nullable'
            ]);

            $response = $this->transcriptService->emailTranscript($request);
            return new APIResource($response, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
}
