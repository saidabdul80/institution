<?php
namespace Modules\Staff\Repositories;

use App\Jobs\PromoteStudent;
use App\Models\Invoice;
use App\Models\InvoiceType;
use Illuminate\Support\Facades\Http;
use App\Models\Staff;
use Modules\Staff\Entities\StaffRoles;
use Database\Seeders\Courses;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use Spatie\Permission\Models\Role;
use App\Models\Department;
use App\Models\Permission;
use App\Models\StaffCourse;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\Bus;
use Modules\Staff\Repositories\AdmissionRepository;

class StaffRepositories{

    private $staff;
    private $student;
    private $invoice;
    private $invoiceType;
    private $admissionRepository;
    private $staffCourse;
    private $studentEnrollment;
    public function __construct( Staff $staff, Student $student, Invoice $invoice, InvoiceType $invoiceType, AdmissionRepository $admissionRepository, StaffCourse $staffCourse, StudentEnrollment $studentEnrollment)
    {
        $this->staff = $staff;
        $this->student = $student;
        $this->invoice = $invoice;
        $this->invoiceType = $invoiceType;
        $this->admissionRepository = $admissionRepository;
        $this->staffCourse = $staffCourse;
        $this->studentEnrollment = $studentEnrollment;
    }

    public function getStaffCredentials($username)
    {
        $staff =  $this->staff::where('email', $username)->orWhere('staff_number', $username)->first();        
        if(empty($staff)){
            throw new \Exception("Incorrect credentials");
        }   
       $staff->setRelation('permissions', $staff->getAllPermissions());      
        return $staff;
    }

    public function checkEmailExist($email)
    {
        return $this->staff::where('email', $email)->exists();
    }

    public function checkStudentEmailExist($email)
    {
        return $this->student::where('email', $email)->exists();
    }

    public function find($id)
    {
        return $this->staff::find($id);
    }

    public function findStudent($id)
    {
        return $this->student::find($id);
    }

    public function update($request)
    {

        $id = $request->get('id');
        $data = $request->all();

        
        $staff = $this->staff::find($id);
                
        foreach($this->staff->appends_props as  $appends){
            unset($data[$appends]);
        }
        if(array_key_exists('password',$data)){
            unset($data['password']);
        }        
        foreach($data as $key => $value){
            if($key != "id"){
                $staff->$key = $value;
            }
        }

        $staff->save();        

        $this->staff->where('id',$id)->update($data);
        return 'success';
    }

    public function updatePassword($request)
    {
        $email = $request->get('email');
        $password = Hash::make($request->get('password'));
        $id = $request->get('id');

        if($this->staff->where('email',$email)->where('id','!=', $id)->exists()){
            throw new \Exception('Email already exists');
        }

        $this->staff->where('id',$id)->update([
            "email"=>$email,
            "password"=>$password,
            "first_login"=> 'false'
        ]);
        return 'success';
    }

    public function resetPassword($id)
    {
        $staff = $this->staff::find($id);
        if(!is_null($staff)){
            $staff->password = Hash::make('0000');
            $staff->first_login = 'true';
            $staff->save();
            return 'Password reset successfully';
        }        
        throw new \Exception('Staff not found',404);
    }

    public function getStaffs($search,$paginateBy,$session_id=null)
    {
        if(is_null($session_id)){
            $session_id = DB::table('configurations')->where('name','current_session')->first()->value;
        }

        $staffs = $this->staff->search($search)->with(['courses'=>function($query) use($session_id){
            $query->where('session_id', $session_id);
        },'permissions'])->latest()->paginate($paginateBy);
        return $staffs;
    }

    public function getPayments($request){

        $columns = ['payment_category_id','semester_id','type','gender','level_id','programme_id','programme_type_id','department_id','faculty_id','entry_mode_id','campus_id'];
        $requests = $request->all();

        $query = array();
        foreach( $columns  as $column){
            if(key_exists($column,$requests) ){
                $query['invoice_types.'.$column] =  $request[$column];
            }
        }

        $query['p.session_id'] = $request['session_id'];

        return DB::table('payments', 'p')
            ->join('invoices', 'p.invoice_id','=','invoices.id')
            ->join('invoice_types', 'invoices.invoice_type_id','=','invoice_types.id')
            ->select(`invoices.id`, `invoices.owner_id`, `invoices.invoice_type_id`, `invoices.invoice_number`, 'p.*','invoice_types.payment_category_id','invoice_types.semester_id','invoice_types.type','invoice_types.gender','invoice_types.level_id','invoice_types.programme_id','invoice_types.programme_type_id','invoice_types.department_id','invoice_types.faculty_id','invoice_types.entry_mode_id','invoice_types.campus_id' )
            ->where($query)->get();
    }

    public function generateNumber($getLast)
    {

        $num  = $getLast+1;
        return str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function getLastNumberFromString($tablename, $column_name){
        $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(SUBSTRING_INDEX(tablename.$column_name,'/',-1), UNSIGNED)) as lastNumber")->first();

        if(is_null($num)){
            return 0;
        }else{
            $number = (int) $num->lastNumber;
            return $number;
        }
     }

    public function emails(){
       return Staff::pluck('email')->toArray();
    }

    public function getAcronym($host){        
            return tenant('short_name') == ''? 'NUM': tenant('short_name');
    }

    public function create($request,$num){

        $acronym =$this->getAcronym($request->header('xtenant')) ??"App";
        $user = new Staff();
        $user->staff_number = $request->get('staff_number') ?? $acronym."/STAFF/".date("y")."/".$num;
        $user->email = $request->get("email");
        $user->first_name = $request->get("first_name");
        $user->middle_name = $request->get("middle_name");
        $user->surname = $request->get("surname");
        $user->gender = $request->get("gender");
        $user->phone_number = $request->get("phone_number");
        $user->address = $request->get("address");
        $user->password = Hash::make("0000");
        //$user->staff_role_id = $request->get("staff_role_id");
        $user->faculty_id = $request->get("faculty_id");
        $user->department_id = $request->get("department_id");
        $user->type = $request->get("type");

        try{
            $user->save();
            return true;
        }catch(\Exception $e){
            throw new \Exception($e->getMessage(), 409);
        }
    }

    public function uploadBulkStaff($staffs){
        return Staff::insert($staffs);
    }

    public function delete($id){
        $staff = Staff::find($id);

        if($staff){
            try{
                return $staff->delete();
            }catch(\Exception $e){
                throw new \Exception("Staff could not be Deactivated");
            }
        }else{
            throw new \Exception('Staff Not Found', 404);  ;
        }

    }

    public function unDelete($id){
        $save = Staff::onlyTrashed()->where('id',$id)->restore();
        return $save;
    }

    public function unAssignRole($ids,$rolename){
        foreach($ids as $id){
            try{
                Staff::find($id)->assignRole($rolename);
            }catch(\Exception $e){
                throw new \Exception($e->getMessage(),$e->getCode());
            }
        }
        return 'success';
    }

    public function assignRole($id, $rolename){
        //foreach($ids as $id){
            try{
                Staff::find($id)->assignRole($rolename);
            }catch(\Exception $e){
                throw new \Exception($e->getMessage(),$e->getCode());
            }
        //}
        return 'success';
    }

    public function getStaffByRole($rolename){
       $data = DB::table('staffs', 's')
       ->join('model_has_roles', 'model_has_roles.model_id','=','s.id')
        ->join('roles','model_has_roles.role_id','=','roles.id')
        ->where('roles.name', $rolename)->get();
        return $data;
    }

    public function confirmPaymentForStudent($transaction_id){
        DB::table('invoices')->where('invoice_number',$transaction_id)->update(['status'=>'paid']);
        DB::table('payments')->where('transaction_id',$transaction_id)->update(['status'=>'successful']);
        return 'success';
    }

    public function updateStudent($request, $id)
    {
        $data = $request->all();
        $excludes = [
            "application_id",
            "entry_session_id",
            "applied_level_id",
            "applied_programme_id",
            "programme_type_id",
            "programme_id",
            "level_id",
            "mode_of_entry_id",
            "department_id",
            "faculty_id",
            "batch"
        ];
        $excludeFound  = '';
        foreach($excludes as $exclude){
            if(in_array($exclude,$data)){
                unset($data[$exclude]);
                $excludeFound .=" ".$exclude. ", ";
            }
        }

        $this->student->where('id',$id)->update($data);
        if(!empty($excludeFound)){
            return "successfully updated with rejected fields ". rtrim($excludeFound, ',');
        }
        return 'success';
    }

    public function assignCourse($course_ids,$staff_id,$semester_id,$user_id, $programme_id, $session_id, $faculty_id, $department_id){
        $newData = [];
        $assigned_course_ids = $this->staffCourse::where([
            "staff_id"=>$staff_id,
            "semester_id"=>$semester_id,
            "programme_id"=>$programme_id,
            "session_id"=>$session_id,
            "faculty_id"=>$faculty_id,
            "department_id"=>$department_id,
        ])
        ->whereIn('course_id', $course_ids)
            ->pluck('course_id')->toArray();
        $unassigned_course_ids = array_diff($course_ids, $assigned_course_ids);
            if($unassigned_course_ids){
                foreach ($unassigned_course_ids as $unassigned_course_id) {
                $newData[] = [
                    'course_id'=> $unassigned_course_id,
                    "staff_id"=>$staff_id,
                    "semester_id"=>$semester_id,
                    "created_by"=>$user_id,
                    "programme_id"=>$programme_id,
                    "session_id"=>$session_id,
                    "faculty_id"=>$faculty_id,
                    "department_id"=>$department_id,
                    "created_by"=>$user_id
                ];
            }
            $this->staffCourse::insert($newData);

            }


        /* if(sizeof($updateData)>0){
            $this->staffCourse::onlyTrashed()->whereIn('id', $updateData)->restore();
        } */

        // if(sizeof($newData)>0){
        //     $this->staffCourse::insert($newData);
        // }

        return "Success";
    }

    public function unAssignCourse($course_ids, $staff_id, $session_id){
        $this->staffCourse::where(['staff_id' => $staff_id, 'session_id' => $session_id])->whereIn('course_id', $course_ids)->forceDelete();
        return "Success";
    }

    public function getStaffCoursesByStaffID($staff_id, $session_id){
        return $this->staffCourse::where(['staff_id'=>$staff_id,'session_id'=>$session_id])->get();
    }

    public function dynamicCreate($model,$data){
        return $model::create($data);
    }

    public function dynamicUpdate($model,$id, $data){
        return $model::where('id', $id)->update($data);
    }

    public function getStudentForPromotion($from, $session_id, $excluded_students_ids){
        $ids = implode(',',$excluded_students_ids);


        $Level100_id = DB::table('levels')->where('order','1')->first()->id;
        if($from == $Level100_id){
            //: reason is to always use the student course grade table for 100L
            return $this->studentIds($ids,$from, $session_id);
        }else{
            //here it should you student_enrollment table instead of student course grade
            //:reason! for optimization
            //if no student found then it should use the stduent course grade always use the student course grade table
            $student_ids = array_column(DB::table('student_enrollments')->where(['owner_type'=>'student','level_id_to'=>$from])->get()->toArray(),'owner_id');
            if(empty($student_ids)){
                return $this->studentIds($ids,$from, $session_id);
            }
            return $student_ids;
        }
    }

    private function studentIds($ids, $from, $session_id){
        if(!empty($ids)){
            $student_ids = array_column(DB::select(DB::raw("SELECT DISTINCT st.student_id from student_courses_grades st WHERE  st.session_id != $session_id and st.level_id = $from")),'student_id');
            $student_ids = implode(',',$student_ids);
            return array_column(DB::select(DB::raw("SELECT id from students s WHERE id IN ($student_ids) and level_id = $from and 'status' != 'active' and id NOT IN ($ids) ")),'id');
        }else{
            $student_ids = array_column(DB::select(DB::raw("SELECT DISTINCT st.student_id from student_courses_grades st WHERE  st.session_id != $session_id and st.level_id = $from")),'student_id');
            $student_ids = implode(',',$student_ids);
            return array_column(DB::select(DB::raw("SELECT id from students s WHERE id IN ($student_ids) and level_id = $from and status = 'active'")),'id');
        }
    }

    private function fetchExludedIdsForPromotion($level_id_from, $session_id){
        $excluded_students_new = $this->student::whereIn('application_id',array_column(DB::table('student_enrollments')->where(['session_id'=>$session_id, 'owner_type'=>'applicant'])->get()->toArray(),'owner_id'))->get();
        $excluded_students_ids_new = array_column($excluded_students_new->toArray(),'id');

        $excluded_students_ids_old = array_column(DB::table('student_enrollments')->where(['session_id'=>$session_id, 'owner_type'=>'student', 'level_id_to'=>$level_id_from])->get()->toArray(),'owner_id');

        return array_merge($excluded_students_ids_new, $excluded_students_ids_old);
    }

    private function promoteStudents($student_id_to_promote, $students_enrolment_record, $level_id_to)
    {
        DB::transaction(function() use($student_id_to_promote,$students_enrolment_record, $level_id_to){
            DB::table('students')->whereIn('id', $student_id_to_promote)->update(['level_id'=>$level_id_to]);
            DB::table('student_enrollments')->upsert($students_enrolment_record,'token',['level_id_from', 'level_id_to','updated_at']);
        });
    }

    public function updateStudentsLevel($from, $to, $session_id){

        $exclude_ids = $this->fetchExludedIdsForPromotion($from, $session_id);

        $student_id_to_promote = $this->getStudentForPromotion($from, $session_id, $exclude_ids);
        if(sizeof($student_id_to_promote)< 1){
            return "No student found for promotion";
        }

        $students_enrolment_record = array();
        $batch = Bus::batch([])->allowFailures()->finally(fn() => 1 )->dispatch();
        foreach($student_id_to_promote as $id){
            //get student graduation
            $student = DB::table('students','s')->join('programmes', 's.programme_id','programmes.id')->where('s.id',$id)->first();
            $students_enrolment_record = [
                "owner_id"=> $id,
                "session_id"=>$session_id,
                "level_id_from"=> $from,
                "level_id_to"=> $to,
                "token"=> 'student'.$id.$session_id,
                "created_at"=> date('Y-m-d h:i:s'),
                "updated_at"=> date('Y-m-d h:i:s'),
            ];

            $batch->add(new PromoteStudent($students_enrolment_record, $student));
        }
        return $batch;
    }

    public function reverseStudentsPromotion($from, $to, $session_id){
        $ids = array_column(DB::table('student_enrollments')->where(['level_id_from'=>$from, 'level_id_to'=>$to,'session_id'=>$session_id])->get()->toArray(),'owner_id');
        $check = $this->studentEnrollment->where(['session_id'=>$session_id,'owner_type'=>'student', 'level_id_from'=>0])->exists();
        if($check){
            return 'reversal had already been done';
        }

        DB::transaction(function() use($ids, $from){
            $ids = implode(',',$ids);
            DB::select(DB::raw("UPDATE students s SET level_id = $from, s.promote_count = s.promote_count-1 WHERE s.id IN ($ids)"));
            DB::table('student_enrollments')->whereIn('owner_id', $ids)->update(['level_id_to'=>null,'level_id_from'=>null]);
        });
        return 'promotion reversed successfully';
    }

    public function promotionLogs($session_id)
    {
        return $this->studentEnrollment->where(['session_id'=>$session_id,'owner_type'=>'student'])->where('level_id_from','!=',0)->get()->unique('level_id_from')->toArray();
    }

    public function updateSchoolInfo($request){
        return DB::table('tenants')->where('id', 1)->update($request->all());
    }

    public function staffCourses($paginate,$search=null){
        $paginate = $paginate??30;
        return StaffCourse::search($search)->latest()->paginate($paginate);
    }

}




?>
