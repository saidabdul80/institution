<?php
namespace Modules\Staff\Services;

use App\Models\AdmissionBatch;
use App\Models\Session;
use Modules\Staff\Repositories\StaffRepositories;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class StaffService{
    private $staffRepository;
    private $utilities;
    public function __construct( StaffRepositories $staffRepository, Utilities $utilities)
    {
        
        $this->staffRepository = $staffRepository;   
        $this->utilities = $utilities;
    }

    public function attempt($request)
    {

        return $this->staffRepository->getStaffCredentials($request->username);
    }

    public function updateStaff($request){

        $existData = $this->staffRepository->find($request->get('id'));
        if(!$existData){
            throw new Exception("Secured Not Found",404);
        }
        return $this->staffRepository->update($request);    

    }

    public function newStaff($request){

        $email = $request->get('email');
        $lastNumber = $this->staffRepository->getLastNumberFromString('staffs','staff_number');

        $checkUser = $this->staffRepository->checkEmailExist($email);
        if($checkUser){
            throw new Exception("This email is already in use",409);
        }

        $num = $this->staffRepository->generateNumber($lastNumber );

        $this->staffRepository->create($request, $num);

        return 'success';

    }

    public function bulkStaffUpload($request){

        $file = $request->file;
        $staffs = $this->fileToArray($file);
        $emails = $this->staffRepository->emails();
        $lastNumber = $this->staffRepository->getLastNumberFromString('staffs','staff_number');
        $acronym = $this->staffRepository->getAcronym($request->header('xtenant')) ??"App";
        $newNumber = $lastNumber;
        $emailExist = [];

        //prepare all staff number
        for($x= 0; $x>sizeof($staffs); $x++){
            $lastNumbers[$x] = $this->staffRepository->generateNumber($newNumber);
            $newNumber++;
        }

        $x=0;
        foreach ($staffs as $index => &$staff) {

            if(in_array($staff['email'], $emails)){

                $emailExist["line_". $index] = $staff['email'];
                unset($staffs[$index]);

            }else{
                $num = $lastNumbers[$x];
                $x++;
                $staff["staff_number"] = $acronym."/STAFF/".date("y")."/".$num;
                $staff["department_id"] = $request->get('department_id');
                $staff["faculty_id"] = $request->get('faculty_id');
                if(array_key_exists('password', $staff)){
                    $staff["password"] = Hash::make($staff["password"]);
                }else{
                    $staff["password"] = Hash::make('password');
                }

            }

        }

        $response = $this->staffRepository->uploadBulkStaff($staffs);
        if($response){
            return ["status"=>'success', "Duplicate Emails"=> $emailExist];
        }

        throw new Exception("Upload Failed, Try Again",400);

    }

    private function fileToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return response()->json(['error' => "Error while reading file"], 400);

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header){
                    $header =  $row;
                    foreach($header as &$hd){
                        $hd = strtolower($hd);
                    }
                }
                else{
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    public function deactivateStaff($request){
        $id = $request->get('id');
        if(auth('api-staff')->id() == $id){
            throw new Exception("Sorry, You Cannot Deactivate Yourself",400);
        }else{
            $this->staffRepository->delete($request->get('id'));
            return "Deleted Successfully";
        }
    }

    public function activateStaff($request){
        $this->staffRepository->unDelete($request->get('id'));
        return "Successfully Restored";
    }

    public function deAssignRoleFromStaff($request){
        $this->staffRepository->unAssignRole($request->get('id'),$request->get('role_name'));
        return "Role Deassigned Successfully";
    }

    public function assignRoleToStaff($request){
        $this->staffRepository->assignRole($request->get('id'),$request->get('role_name'));
        return "Role Assigned Successfully";
    }

    public function getStaffByRole($request){
        $staffs = $this->staffRepository->getStaffByRole($request->get('role_name'));
        return $staffs;
    }

    public function staffs($request){
        $paginateBy = $request->get('paginateBy')??30;
        $search = $request->get('search')??"";

       return $this->staffRepository->getStaffs($search,$paginateBy, $request->session_id);
    }
    public function updatePassword($request){
        return $this->staffRepository->updatePassword($request);
    }
    public function resetPassword($request){
        return $this->staffRepository->resetPassword($request->get('id'));
    }
    public function byID($id){

        $staff = $this->staffRepository->find($id);
        if($staff){
            return $staff;
        }
        throw new Exception("Fail to get applicant",404);
    }

    public function getFees($request){
        $response = $this->staffRepository->getPayments($request);
        if($response){
            return $response;
        }
        throw new Exception("Fail to get applicant",404);
    }




    public function confirmPaymentForStudent($request){
        $response = $this->staffRepository->confirmPaymentForStudent($request->get('transaction_id'));
        return $response;
    }

    public function updateStudentData($request){

        $email = $request->get('email')??"";
        $checkUser = $this->staffRepository->checkStudentEmailExist($email);
        $existData = $this->staffRepository->findStudent($request->get('id'));
        if(!$existData){
            throw new Exception("Student Not Found",404);
        }
        if($checkUser){
            throw new Exception("This email is already in use",409);
        }

        $this->staffRepository->updateStudent($request, $request('id'));
        return 'success';
    }

    public function assignCoursesToStaff($request){
        return $this->staffRepository->assignCourse($request->get('course_ids'),$request->get('staff_id'),$request->get('semester_id'),auth('api-staff')->id(), $request->get('programme_id'), $request->get('session_id'), $request->get('faculty_id'), $request->get('department_id'));
    }

    public function unAssignCoursesFromStaff($request){

        return $this->staffRepository->unAssignCourse(course_ids: $request->get('course_ids'), staff_id: $request->get('staff_id'), session_id: $request->get('session_id'));

    }

    public function getStaffCoursesByStaffID($request){

        return $this->staffRepository->getStaffCoursesByStaffID(staff_id:$request->staff_id, session_id:$request->session_id);

    }

   public function promoteStudents($request){
        $current_session = $this->utilities::currentSession();        
        return $this->staffRepository->updateStudentsLevel($request->level_id_from, $request->level_id_to, $current_session);
    }

    public function reverseStudentsPromotion($request){
        $current_session = $this->utilities::currentSession();
        return $this->staffRepository->reverseStudentsPromotion($request->level_id_from, $request->level_id_to, $current_session);
    }

    public function promotionLogs(){
        $current_session = $this->utilities::currentSession();
        return $this->staffRepository->promotionLogs($current_session);
    }

    public function updateSchoolInfo($request){
        return $this->staffRepository->updateSchoolInfo($request);
    }

    public function staffCourses($request)
    {
        $response = $this->staffRepository->staffCourses($request->get('paginateBy'),$request->get('search'));
        return $response;
    }
    

}

?>
