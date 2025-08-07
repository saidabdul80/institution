<?php
namespace Modules\Student\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\APIResource;
use App\Models\Student;
use Exception;

class StudentController extends Controller
{
    /**
     * Student login
     * @param Request $request
     * @return APIResource
     */
    public function login(Request $request)
    {
        try {
            // Validate credentials
            $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            // Query the database to check username (matric_number or email)
            $student = Student::where('email', $request->username)
                             ->orWhere('matric_number', $request->username)
                             ->first();

            // Compare input password with hashed password from database
            if (!$student || !Hash::check($request->password, $student->password)) {
                throw new Exception("Incorrect credentials", 404);
            }

            // Check if student is active
            if ($student->status !== 'active') {
                throw new Exception("Your account is not active. Please contact the administration.", 403);
            }

            $student->logged_in_time = now();
            $student->logged_in_count = ($student->logged_in_count ?? 0) + 1;
            $student->save();

            // Generate access token for logged in user
            $accessToken = $student->createToken("AuthToken")->accessToken;

            // Response structure
            return new APIResource([
                "student" => $student,
                "accessToken" => $accessToken
            ], false, 200);

        } catch (ValidationException $e) {
            // Catch validation errors and return in response format
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Logout validated users
     * @return APIResource
     */
    public function logout()
    {
        try {
            // Delete generated token
            Auth::guard('api-students')->user()->tokens()->delete();
            // Return response
            return new APIResource("You logged out successfully", false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get authenticated student details
     * @return APIResource
     */
    public function getStudentById()
    {
        try {
            $student = Auth::guard('api-students')->user();
            return new APIResource($student, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Update student profile
     * @param Request $request
     * @return APIResource
     */
    public function updateStudent(Request $request)
    {
        try {
            $student = Auth::guard('api-students')->user();

            // Get updateable fields (exclude sensitive fields)
            $updateableFields = [
                'first_name', 'middle_name', 'last_name', 'email', 'phone_number',
                'date_of_birth', 'gender', 'religion', 'address', 'state_id', 'lga_id',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'
            ];

            $updateData = $request->only($updateableFields);

            $student->update($updateData);

            return new APIResource($student->fresh(), false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Upload student picture
     * @param Request $request
     * @return APIResource
     */
    public function uploadPicture(Request $request)
    {
        try {
            $request->validate([
                'picture' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $student = Auth::guard('api-students')->user();

            if ($request->hasFile('picture')) {
                $file = $request->file('picture');
                $filename = time() . '_' . $student->id . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('student_pictures', $filename, 'public');

                $student->update(['picture' => $path]);
            }

            return new APIResource($student->fresh(), false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('student::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('student::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('student::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('student::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Check if student has valid registration payment for current session
     */
    public function checkRegistrationPayment()
    {
        try {
            $student = auth('api-student')->user();
            if (!$student) {
                return new APIResource('Student not found', true, 404);
            }

            // Get current active session
            $currentSession = \App\Models\Session::where('is_active', true)->first();
            if (!$currentSession) {
                return new APIResource(['has_valid_payment' => false, 'message' => 'No active session'], false, 200);
            }

            // Check for registration fee payment in current session
            $hasValidPayment = \App\Models\Invoice::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->whereHas('invoiceType', function($query) {
                    $query->whereHas('paymentCategory', function($q) {
                        $q->where('name', 'registration fee');
                    });
                })
                ->whereIn('status', ['paid', 'part paid'])
                ->exists();

            // Check configuration for partial payment allowance
            $allowPartialPayment = \App\Services\Util::getConfigValue('allow_partial_payment', false);
            $partialPaymentPercentage = \App\Services\Util::getConfigValue('allowed_partial_payment_amount_percentage', 0);

            // If partial payment is not allowed, only accept fully paid invoices
            if (!$allowPartialPayment) {
                $hasValidPayment = \App\Models\Invoice::where('student_id', $student->id)
                    ->where('session_id', $currentSession->id)
                    ->whereHas('invoiceType', function($query) {
                        $query->whereHas('paymentCategory', function($q) {
                            $q->where('name', 'registration fee');
                        });
                    })
                    ->where('status', 'paid')
                    ->exists();
            }

            // Check for previous session unpaid invoices if current session is part paid
            $hasPreviousUnpaidInvoices = false;
            if ($hasValidPayment && $allowPartialPayment) {
                $hasPreviousUnpaidInvoices = \App\Models\Invoice::where('student_id', $student->id)
                    ->where('session_id', '<', $currentSession->id)
                    ->whereHas('invoiceType', function($query) {
                        $query->whereHas('paymentCategory', function($q) {
                            $q->where('name', 'registration fee');
                        });
                    })
                    ->where('status', 'part paid')
                    ->exists();
            }

            // If there are previous unpaid invoices, disable access
            if ($hasPreviousUnpaidInvoices) {
                $hasValidPayment = false;
            }

            return new APIResource([
                'has_valid_payment' => $hasValidPayment,
                'current_session' => $currentSession,
                'allow_partial_payment' => $allowPartialPayment,
                'partial_payment_percentage' => $partialPaymentPercentage,
                'has_previous_unpaid' => $hasPreviousUnpaidInvoices
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get current active session
     */
    public function getCurrentSession()
    {
        try {
            $currentSession = \App\Models\Session::where('is_active', true)->first();

            if (!$currentSession) {
                return new APIResource('No active session found', true, 404);
            }

            return new APIResource($currentSession, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get all sessions
     */
    public function getAllSessions()
    {
        try {
            $sessions = \App\Models\Session::orderBy('created_at', 'desc')->get();
            return new APIResource($sessions, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get available courses for student
     */
    public function getStudentCourses()
    {
        try {
            $student = auth('api-student')->user();
            if (!$student) {
                return new APIResource('Student not found', true, 404);
            }

            // Get current active session
            $currentSession = \App\Models\Session::where('is_active', true)->first();
            if (!$currentSession) {
                return new APIResource('No active session found', true, 404);
            }

            // Get courses for student's programme and level
            $courses = \App\Models\Course::whereHas('programmes', function($query) use ($student) {
                $query->where('programme_id', $student->programme_id);
            })
            ->where('level_id', $student->level_id)
            ->with(['level', 'semester'])
            ->get();

            return new APIResource($courses, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get registered courses for student
     */
    public function getRegisteredCourses()
    {
        try {
            $student = auth('api-student')->user();
            if (!$student) {
                return new APIResource('Student not found', true, 404);
            }

            // Get current active session
            $currentSession = \App\Models\Session::where('is_active', true)->first();
            if (!$currentSession) {
                return new APIResource('No active session found', true, 404);
            }

            // Get registered courses for current session
            $registeredCourses = \App\Models\StudentCourse::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->with(['course.level', 'course.semester'])
                ->get()
                ->pluck('course');

            return new APIResource($registeredCourses, false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Register courses for student
     */
    public function registerCourses(Request $request)
    {
        try {
            $student = auth('api-student')->user();
            if (!$student) {
                return new APIResource('Student not found', true, 404);
            }

            $request->validate([
                'course_ids' => 'required|array',
                'course_ids.*' => 'exists:courses,id',
                'session_id' => 'required|exists:sessions,id'
            ]);

            // Check if student has valid registration payment
            $hasValidPayment = \App\Models\Invoice::where('student_id', $student->id)
                ->where('session_id', $request->session_id)
                ->whereHas('invoiceType', function($query) {
                    $query->whereHas('paymentCategory', function($q) {
                        $q->where('name', 'registration fee');
                    });
                })
                ->whereIn('status', ['paid', 'part paid'])
                ->exists();

            if (!$hasValidPayment) {
                return new APIResource('Registration fee payment required before course registration', true, 400);
            }

            // Remove existing registrations for this session
            \App\Models\StudentCourse::where('student_id', $student->id)
                ->where('session_id', $request->session_id)
                ->delete();

            // Register new courses
            $registrations = [];
            foreach ($request->course_ids as $courseId) {
                $registrations[] = [
                    'student_id' => $student->id,
                    'course_id' => $courseId,
                    'session_id' => $request->session_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            \App\Models\StudentCourse::insert($registrations);

            return new APIResource([
                'message' => 'Courses registered successfully',
                'registered_count' => count($request->course_ids)
            ], false, 200);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
