<?php
namespace Modules\Staff\Services;

use App\Models\AdmissionBatch;
use Modules\Staff\Repositories\AdmissionRepository;
use Exception;
use Modules\Staff\Repositories\StaffRepositories;
use Modules\Staff\Services\Utilities;

class AdmissionService extends Utilities{

    private $admissionRepository;
    private $utility;
    private $staffRepository;
    private $admissionBatch;
    public function __construct( AdmissionRepository $admissionRepository, StaffRepositories $staffRepository, AdmissionBatch $admissionBatch, Utilities $utilities)
    {

        $this->admissionRepository = $admissionRepository;
        $this->utility = $utilities;
        $this->staffRepository = $staffRepository;
        $this->admissionBatch = $admissionBatch;
    }


    public function acceptApplicant($request){
        return $this->admissionRepository->admitApplicant(
            applicant_ids: $request->get('applicant_ids'),
            level_id: $request->get('level_id'),
            programme_id: $request->get('programme_id'),
            admission_options: $request->get('admission_options', []),
            session_id: $request->get('session_id')
        );
    }

    public function bulkAcceptApplicant($request){
        $file = $request->file;        
        $application_numbers = array_values($this->utility->fileToArray($file));
        return $this->admissionRepository->admitBulkApplicant($application_numbers,$request->get('programme_id'),$request->get('level_id'));        
    }

    public function rejectApplicant($request){
        return $this->admissionRepository->rejectThisApplicants($request->get('applicant_ids'), $request->get('session_id'));
    }


    public function getApplicant($request){
        $paginateBy = $request->get('paginateBy')??30;
        $keyword = $request->get('keyword')??"";
        $payment_name = $request->get('payment_name')??'';
        $session_id = $request->get('session_id')??1;
        //$type = $request->get("type");
        $filters = $request->get("filters");        
       return $this->admissionRepository->applicants($keyword,$paginateBy, $session_id,$filters);

    }

    public function getStudents($request){
        $paginateBy = $request->get('paginateBy')??30;
        $keyword = $request->get('keyword')??"";
        $payment_name = $request->get('payment_name')??'';
        $session_id = $request->get('session_id')??1;
        $filters = $request->get('filters')??[];
       return $this->admissionRepository->getStudents($keyword,$paginateBy,$session_id, $payment_name, $filters);

    }


    public function paidApplicants($request){
        $paginateBy = $request->get('paginateBy')??30;
        $searchObj = $request->get('search')??"";
        $payment_name = $request->get('payment_name');

        $searchParam = [];
        if($searchObj != ""){

            $email = $searchObj['email']??"";
            $gender = $searchObj['gender']??"";
            $state = $searchObj['state_id']??"";
            $country = $searchObj['country_id']??"";
            $department_id = $searchObj['department_id']??"";
            $applied_programme_curriculum_id = $searchObj['applied_programme_curriculum_id']??"";
            $mode_of_entry_id = $searchObj['mode_of_entry_id']??"";
            $health_status = $searchObj['health_status']??"";
            $payment_open = $searchObj['payment_open']??"";
            $application_number = $searchObj['application_number']??"";
            $searchParam = [
                "applicants.email"=>$email,
                "applicants.gender"=>$gender,
                "applicants.state_id"=>$state,
                "applicants.country_id"=>$country,
                "applicants.department_id"=>$department_id,
                "applicants.applied_programme_curriculum_id"=>$applied_programme_curriculum_id,
                "applicants.mode_of_entry_id"=>$mode_of_entry_id,
                "applicants.health_status"=>$health_status,
                "applicants.payment_open"=>$payment_open,
                "applicants.application_number"=>$application_number
            ];

        }
       foreach($searchParam as $key => $value){
           if($value == ""){
               unset($searchParam[$key]);
           }
       }
       unset($searchParam["session_id"]);
       //return $searchParam;
       return $this->admissionRepository->paidApplicants($request->get('session_id'),$searchParam,$paginateBy, $payment_name,$request->get('status') );

    }

    public function activateStudent($request){
        $this->admissionRepository->activateStudent($request->get('matric_number'),$request->get('session_id'));
        return 'success';
    }

    public function updateAdmissionStatus($request){
        $applicant_ids = $request->get('applicant_ids');
        $status = $request->get('status');
        $this->admissionRepository->updateApplicantAdmissionStatus($applicant_ids, $status);
        return 'success';
    }

    public function updateQualifiedStatus($request){
        $applicant_ids = $request->get('applicant_ids');
        $status = $request->get('status');
        $this->admissionRepository->updateApplicantQualifiedStatus($applicant_ids,$status);
        return 'success';
    }

    public function changeProgramme($request){
        $response = $this->admissionRepository->changeProgramme($request->get('applicant_id'), $request->get('programme_id'), $request->get('faculty_id'),$request->get('department_id'),$request->get('programme_type_id'),$request->get('level_id'));
        return $response;
    }

    public function changeAdmittedProgramme($request){    
        return $this->admissionRepository->changeAdmittedProgramme($request->get('applicant_id'), $request->get('programme_id'), $request->get('programme_curriculum_id'), $request->get('department_id'),$request->get('faculty_id'), $request->get('programme_type_id'), $request->get('level_id'));
    }

    public function getBatches($session_id){
        return  $this->admissionRepository->admissionBatches($session_id);
    }


    public function createBatch($request){
        return $this->staffRepository->dynamicCreate($this->admissionBatch, $request->all());
    }

    public function updateBatch($request){
        $data = $request->all();
        unset($data['id']);
        return $this->staffRepository->dynamicUpdate($this->admissionBatch, $request->get("id"), $data);
    }

    public function deleteBatch($request){
        return $this->admissionRepository->deleteBatch($this->admissionBatch, $request->get("id"));
    }

    public function fetchAdmissionBatches(){
        return $this->admissionRepository->allBatches();

    }

}
