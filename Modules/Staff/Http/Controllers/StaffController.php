<?php

namespace Modules\Staff\Http\Controllers;
use Spatie\Activitylog\Models\Activity;
use App\Events\InvoicePaid;
use App\Events\PaymentMade;
use App\Http\Resources\APIResource;
use App\Models\Agent;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Staff\Services\StaffService;
use Modules\Staff\Services\Utilities;
use Modules\Staff\Transformers\UtilResource;

class StaffController extends Controller
{
    private $staffService;
    private $paymentsService;
    private $utilities;
    public function __construct(StaffService $staffService, Utilities $utilities)
    {
        $this->staffService = $staffService;
        $this->utilities =  $utilities;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */


    public function login (Request $request) {
        try {
            //validate credentials
            $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            //query the database to check username
            $staff = $this->staffService->attempt($request);

            //compare input password with hashed password from database and return error not matching
            if (!$staff || !Hash::check($request->password, $staff->password)) {
                throw new \Exception("Incorrect credentials", 404);
            }
            
            $staff->logged_in_time = now();
            $staff->save();
            //generate access token for logged in user
            $accessToken = $staff->createToken("AuthToken")->accessToken;

            //response structure
            return new APIResource(["staff" => $staff,"accessToken" => $accessToken ], false, 200);

        } catch (ValidationException $e) {

            //catch validation errors and return in response format
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }


    /**
     * logout validated users
     * @return JsonResponse
     */

    public function logout() {
        //delete generated token
        Auth::guard('api-staff')->user()->tokens()->delete();
        //return response
        return new APIResource("you logged out", false, 200);
    }


    public function update(Request $request)
    {

        try{

            $request->validate([
                "id"=>"required"
             ]);

            $response = $this->staffService->updateStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){

            return new APIResource(array_values($e->errors())[0], true, 400 );

        }catch(Exception $e){

            return new APIResource($e->getMessage(), true, 400 );

        }

    }

    public function create(Request $request){

        try{
            $request->validate([
                "email" => "required",
                "first_name" => "required",
                "surname" => "required",
                "gender" => "required",
                "phone_number" => "required",                
                "type" => "required",
                "staff_number" => "sometimes|nullable|unique:staffs,staff_number",
             ]);

            $response = $this->staffService->newStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){

            return new APIResource(array_values($e->errors())[0], true, 400 );

        }catch(Exception $e){

            return new APIResource($e->getMessage(), true, 400 );

        }

    }

    public function getStaffs(Request $request)
    {

        try{

            $request->validate([
               //'paginateBy' => 'required',
               // 'mode' => 'required',// 1 or -1
              //  'search' => 'required',// {} objects
            ]);

            $response = $this->staffService->staffs($request);
            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            Log::error($e);
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function bulkUpload(Request $request){
        try{

            $request->validate([
                'file' => ['required', "mimes:text,csv"],
                "faculty_id" => "required",
                "department_id" => "required",
            ]);

            $response = $this->staffService->bulkStaffUpload($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function deactivate(Request $request){
        try{

            $request->validate([
                "id" => "required",
            ]);

            $response = $this->staffService->deactivateStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function activate(Request $request){
        try{

            $request->validate([
                "id" => "required",
            ]);

            $response = $this->staffService->activateStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getStaffById(Request $request){
        try{

            $id = $request->id;
            if($id == "" || !is_numeric($id)){
                throw new Exception("id is Required");
            }

            $response = $this->staffService->byID($id);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function deAssignRole(Request $request){
        try{

            $request->validate([
                "id" => "required",  // staff_ids  [1,2,3]
            ]);

            $response = $this->staffService->deAssignRoleFromStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function assignRole(Request $request){
        try{

            $request->validate([
                "id" => "required", //staff_ids [1,3,2]
                "role_name"=>"required",
            ]);

            $response = $this->staffService->assignRoleToStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function staffInRole(Request $request){

        try{

            $request->validate([
                "role_name" => "required",
            ]);

            $response = $this->staffService->getStaffByRole($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function uploadPicture(Request $request){

    }

    public function feesController(Request $request){
        try{


            $request->validate([

                'payment_category_id'   =>'required',
                'session_id' => 'required',
                /* 'semester_id'   =>'required',
                'type'  =>'required',
                'gender'    =>'required',
                'level_id'  =>'required',
                'programme_id'  =>'required',
                'programme_type_id' =>'required',
                'department_id' =>'required',
                'faculty_id'    =>'required',
                'entry_mode_id' =>'required',
                'campus_id' =>'required', */

             ]);

            $response = $this->staffService->getFees($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }



    public function confirmPayment(Request $request){
        try{


            $request->validate([
                'transaction_id' => 'required',
             ]);

            $response = $this->staffService->confirmPaymentForStudent($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateStudent(Request $request){
        try{

            $request->validate([
                'id'   =>'required',
            ]);

            $response = $this->staffService->updateStudentData($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updateSchoolInfo(Request $request){
        try{


            $response = $this->staffService->updateSchoolInfo($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function assignCourses(Request $request){

        try{

            $request->validate([
                "course_ids" => "required",    //[1,2,3,4]
                "staff_id" => "required",
                //"semester_id" => "required",
                //"programme_id" => "required",
                "session_id" => "required",
                //"faculty_id" => "required",
                //"department_id" => "required",
            ]);

            $response = $this->staffService->assignCoursesToStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function unAssignCourses(Request $request){

        try{

            $request->validate([
                "course_ids" => "required",//[1,2,3]
                "staff_id" => "required",
                "session_id" => "required",
            ]);

            $response = $this->staffService->unAssignCoursesFromStaff($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getStaffCoursesByStaffID(Request $request){
        try{

            $response = $this->staffService->getStaffCoursesByStaffID($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getTemplate(){
        return  $this->utilities->getFile('staffUploadTemplate.csv');
    }

    public function promoteStudents(Request $request){
        try{
            $request->validate([
                "level_id_from" => "required",
                "level_id_to" => "required",
             ]);

            $response = $this->staffService->promoteStudents($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function reverseStudentsPromotion(Request $request){
        try{
            $request->validate([
                "level_id_from" => "required",
                "level_id_to" => "required",
             ]);

            $response = $this->staffService->reverseStudentsPromotion($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function promotionLogs(Request $request){
        try{

            $response = $this->staffService->promotionLogs($request);

            return new APIResource($response, false, 200 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function managementOffice(Request $request){
        try{

            $response = DB::table('roles')->where('office','true')->get();

            return new APIResource($response, false, 200 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function getStaffCourses(Request $request){

        try{

            $response = $this->staffService->staffCourses($request);
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function getAllStaffWithCourses(Request $request){
        try{
            $response = $this->staffService->getAllStaffWithCourses($request);
            return new APIResource($response, false, 200 );

        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function updatePassword(Request $request){
        try{
            $request->validate([
                "password" => ["required", "confirmed"],
                "email" => ["required","email"],
                "id" => "required"
             ]);

            $response = $this->staffService->updatePassword($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function resetPassword(Request $request){
        try{
            $request->validate([                
                "id" => "required"               
             ]);

            $response = $this->staffService->resetPassword($request);

            return new APIResource($response, false, 200 );
        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }
    
}
