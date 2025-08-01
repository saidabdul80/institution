<?php
namespace Modules\Staff\Services;

use App\Models\Applicant;
use App\Models\Course;
use App\Models\Staff;
use Modules\Staff\Repositories\DashboardRepository;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Staff\Services\Utilities;

class DashboardService extends Utilities{

    private $dashboardRepository;
    private $utility;
    private $carbon;
    public function __construct( DashboardRepository $dashboardRepository, Carbon $carbon, Utilities $utilities)
    {

        $this->dashboardRepository = $dashboardRepository;
        $this->utility = $utilities;
        $this->carbon = $carbon;
    }


    public function admissionDashboard($request){        

        
        $session_id = $request->get('session_id');        
        $applicants    = $this->dashboardRepository->getApplicantsBySessionId($session_id);        
        $admittedStateCount = $this->dashboardRepository->getAdmittedStateCount($session_id);
     //   $totalStudents      = $this->dashboardRepository->getTotalStudents($session_id);
        $religions    = $this->dashboardRepository->getApplicantReligionCount($session_id);
    
        $data = [];                       
        $data["total_application_fee_paid_count"] = $applicants->where('application_fee','Paid')->count();
        $data['religions'] = $religions;
        $data['applicants'] = $applicants->count();
        $data['admitted'] =  $applicants->where("admission_status","admitted")->count();
        $data['not_admitted'] = $applicants->where("admission_status","not admitted")->count();
        $data['rejected'] = $applicants->where("admission_status","rejected")->count();
        $data['state'] = $admittedStateCount;
        $data['qualified'] = $applicants->where("qualified_status","qualified")->count();
        $data['not_qualified'] = $applicants->where("qualified_status","not qualified")->count();
        return  $data;

    }


    public function reportDashboard($request){

        $session_id = $request->get('session_id');
        $totalStudents      = $this->dashboardRepository->getTotalStudents($session_id);
        $totalCourse      = $this->dashboardRepository->getTotalCourse();
        $totalDepartment    = $this->dashboardRepository->getTotalDepartment();
        $applicantReport = $this->dashboardRepository->applicantReport($session_id);
        $religions    = $this->dashboardRepository->getApplicantReligionCount($session_id);

        $data = $applicantReport;
        $data['religions'] = $religions;
        $data['applicants'] = $data['total'];
        $data['admitted'] = $totalStudents;
        $data['not_admitted'] = $data['applicants'] - $totalStudents;
        $data['courses'] = $totalCourse;
        $data['department'] = $totalDepartment;
        unset($data['total']);

        return  $data;

    }

    public function info($request){

        
        $session_id = $request->get('session_id');
        $totalActiveStaff    = $this->dashboardRepository->getTotalActiveStaff();
        $totalNonActiveStaff    = $this->dashboardRepository->totalNonActiveStaff();

        $totalNonActiveStudent    = $this->dashboardRepository->getTotalNonActiveStudent();
        $totalActiveStudent    = $this->dashboardRepository->getTotalActiveStudent();

        $totalNonActiveApplicant    = $this->dashboardRepository->getTotalNonActiveApplicant($session_id);
        $totalActiveApplicant    = $this->dashboardRepository->getTotalActiveApplicant($session_id);

        $applicantReport    = $this->dashboardRepository->applicantReport($session_id);

        $studentReport    = $this->dashboardRepository->studentReport($session_id);

        $data["applicant"] = $applicantReport;
        $data["student"] = $studentReport;
        $data['active_staff'] = $totalActiveStaff;
        $data['non_active_staff'] = $totalNonActiveStaff;

        $data['active_student'] = $totalActiveStudent;
        $data['non_active_student'] = $totalNonActiveStudent;

        $data['non_active_applicant'] = $totalNonActiveApplicant;
        $data['active_applicant'] = $totalActiveApplicant;
        $data['total_courses'] = Course::count();
        $data['total_staff'] = Staff::count();
        return $data;
    }

    public function financeReport($request){
        /* $type = $request->get('type');
        $payment_name = $request->get('payment_short_name'); */
        $session_id = $request->get('session_id');
        //return $this->dashboardRepository->getFinanceReport($type, $payment_name, $session_id);
        return $this->dashboardRepository->getFinanceReport($session_id);
    }

    public function mainDashboard($request){
        $id = $request->get('id');
        $session_id = $request->get('session_id');
        $paginateBy = $request->get('paginateBy')?? 500;
        $byDate = $request->get('date_name'); //now, month and year, refers to current and null refers to specifying date range
        $use_session = $request->get('use_session')??true;
        $byDate_from = '';
        $byDate_to = '';
        $now = $this->carbon::now();

        if(!is_null($byDate)){
            if($byDate != 'now' AND $byDate != 'year' AND $byDate != 'month'){
                throw new \Exception('invalid date name value');
            }

            if($byDate == 'now'){
                $byDate_from = $byDate_to = $now->format('Y-m-d');
            }
            if($byDate == 'month'){
                $byDate_from = $now->startOfMonth()->format('Y-m-d');
                $byDate_to = $now->endOfMonth()->format('Y-m-d');
            }

            if($byDate == 'year'){
                $byDate_from = $now->startOfYear()->format('Y-m-d');
                $byDate_to = $now->endOfYear()->format('Y-m-d');
            }

        }else{
            $now = $now->format('Y-m-d');
            $byDate_from = $request->get('from')??$now;
            $byDate_to = $request->get('to')??$now;
        }

        $paymentReport = $this->dashboardRepository->paymentReport($session_id, $byDate_from, $byDate_to,$use_session);
        $hostelReport = $this->dashboardRepository->hostelReport($session_id,$byDate_from, $byDate_to,$use_session);
        $applicantReport = $this->dashboardRepository->applicantReport($session_id, $byDate_from, $byDate_to,$use_session);
        $admissionReport = $this->dashboardRepository->admissionReport($session_id,$byDate_from, $byDate_to,$use_session);
        $results = [
            'payments_report' => $paymentReport,
            'hostel_report' =>$hostelReport,
            'applicantReport' =>$applicantReport,
            'admissionReport' =>$admissionReport,
        ];
        return $results;

    }

    public function feeReport($request){
            $report = $this->dashboardRepository->programmeTypeFeeReport($request->get("payment_short_name"), $request->get("session_id"));
            $prev_report = $this->dashboardRepository->programmeTypeFeeReport($request->get("payment_short_name"), $request->get("session_id")-1);
            $reports = [
                "report" =>$report,
                "prev_report"=>$prev_report
            ];
            return $reports;
    }

    public function invoicesByPaymentName($request){
        return $this->dashboardRepository->invoicesByPaymentName($request->get("payment_short_name"), $request->get("filters"), $request->get("session_id"),$request->get("paginateBy"));
    }

    public function sessionsFeeReport($request){

        $session_id = $request->get("session_id");
        /* $session_id_4 = $session_id - 4;
        $session_id_3 = $session_id - 3;
        $session_id_2 = $session_id - 2;
        $session_id_1 = $session_id - 1;
        $session_ids = [$session_id_4,$session_id_3,$session_id_2,$session_id_1, $session_id]; */
        $session_ids = [];
        $session_ids[] = $session_id;
        for ($i=1; $i < 5 ; $i++) {
            $session_ids[] = $session_id - $i;
        }

        return $this->dashboardRepository->sessionsFeeReport($session_ids,$request->get("payment_short_name"));
    }

    public function programmeTypesReport($request){
        return $this->dashboardRepository->programmeTypesReport($request->get("session_id"));
    }

    public function recentLogins($request){
        return $this->dashboardRepository->recentLogins($request->get('model_name'), $request->get("take"));
    }

    public function admissionReportAdmittedByProgramme($request){
        return $this->dashboardRepository->admissionReportAdmittedByProgramme($request->get('session_id'));
    }
    public function admissionReportAdmittedByProgrammeType($request){
            return $this->dashboardRepository->admissionReportAdmittedByProgrammeType($request->get('session_id'));
    }

    public function qualificationReportQualifiedByProgrammeType($request){
        return $this->dashboardRepository->qualificationReportQualifiedByProgrammeType($request->get('session_id'));
    }

    public function qualificationReportQualifiedByProgramme($request){
            return $this->dashboardRepository->qualificationReportQualifiedByProgramme($request->get('session_id'));
    }

    public function totalPaidAndUnpaidByProgrammeType($request){
            return $this->dashboardRepository->totalPaidAndUnpaidByProgrammeType($request->get('payment_short_name'), $request->get('session_id'));
    }

    public function totalPaidAndUnpaidByProgramme($request){
        return $this->dashboardRepository->totalPaidAndUnpaidByProgramme($request->get('payment_short_name'), $request->get('session_id'));
    }
    public function totalPaidAndUnpaid($request){
        return $this->dashboardRepository->totalPaidAndUnpaid($request->get('payment_short_name'), $request->get('session_id'));
    }    

    public function studentByPhysicalChallenge(){
        return $this->dashboardRepository->studentByPhysicalChallenge();
    }
    
    public function admittedByEntryMode($request){
        return $this->dashboardRepository->admittedByEntryMode($request->get('session_id'),$request->get('programme_type_id'));
    }
    
    public function activeAndNonActiveStudent(){
        return $this->dashboardRepository->activeAndNonActiveStudent();
    }
    
    public function studentRegisteredAndUnregisteredCount($request){
        return $this->dashboardRepository->studentRegisteredAndUnregisteredCount($request->get('session_id'),$request->get('programme_type_id'));
    }
    
    public function schoolFeeTotalPaidAndUnpaid($request){
        return $this->dashboardRepository->schoolFeeTotalPaidAndUnpaid($request->get('session_id'));
    }


    public function allFeeReport($request){
        $session_id = $request->get("session_id");              
        $session_id = $request->get("session_id");              
        $reports = [];
        $reports[] =$this->dashboardRepository->allFeeReport($session_id);
        for ($i=1; $i < 5 ; $i++) {
            $reports[]= $this->dashboardRepository->allFeeReport($session_id - $i);
        }
        return $reports;
    }

    public function allFeeReportByRange($request){
        $start_date = Carbon::parse($request->get('start_date'))->format("Y-m-d");
        $end_date = Carbon::parse($request->get('end_date'))->format("Y-m-d");
        return $this->dashboardRepository->paymentReport(null,$start_date,$end_date,false);        
    }

    public function applicantByPhysicalChallenge(){
        return $this->dashboardRepository->applicantByPhysicalChallenge();
    }

    public function studentsBySponsorship($request){
        return $this->dashboardRepository->studentsBySponsorship($request->get('session_id'));
    }

    public function totalStudentsByLevels($request){
        return $this->dashboardRepository->totalStudentsByLevels($request->get('session_id'));
    }

    public function totalStudentsByProgrammes($request){
        return $this->dashboardRepository->totalStudentsByProgrammes($request->get('session_id'));
    }
    

  /*   public function totalPaidAndUnpaidAmountByProgrammeType($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountByProgrammeType($request->get('payment_short_name'),$request->get('session_id'),$request->get('owner_type'));
    }
 */
    public function totalPaidAndUnpaidAmountByProgrammeType($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountByProgrammeType($request->get('payment_short_name'),$request->get('session_id'),$request->get('owner_type'));
    }

  /*   public function totalPaidAndUnpaidAmountBylevelProgrammeType($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountBylevelProgrammeType($request->get('payment_short_name'),$request->get('session_id'),$request->get('owner_type'));
    } */

    public function totalPaidAndUnpaidAmountBylevelProgrammeType($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountBylevelProgrammeType($request->get('payment_short_name'),$request->get('session_id'),$request->get('owner_type'));
    }

 /*    public function totalPaidAndUnpaidAmountBylevelProgramme($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountBylevelProgramme($request->get('payment_short_name'),$request->get('session_id'),$request->get('owner_type'));
    } */

    public function totalPaidAndUnpaidAmountBylevelProgramme($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountBylevelProgramme($request->get('payment_short_name'),$request->get('session_id'),$request->get('owner_type'));
    }
    
    /* public function totalPaidAndUnpaidAmountByProgramme($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountByProgramme($request->get('payment_short_name'),$request->get('session_id'), $request->get('owner_type'));
    } */
    
    public function totalPaidAndUnpaidAmountByProgramme($request){
        return $this->dashboardRepository->totalPaidAndUnpaidAmountByProgramme($request->get('payment_short_name'),$request->get('session_id'), $request->get('owner_type'));
    }

    public function walletReport(){
        return $this->dashboardRepository->walletReport();
    }

    public function walletFundingLog($request){
        return $this->dashboardRepository->walletFundingLog($request->get('paginateBy'),$request->get('search'));
    }
    
    public function walletSettlementLog($request){
        return $this->dashboardRepository->walletSettlementLog($request->get('paginateBy'));
    }

    public function walletFunding($request)
    {
        return $this->dashboardRepository->walletFunding($request->get('type'),$request->get('from'), $request->get('to'));
    }

    public function paymentReport($request)
    {
         return  $this->dashboardRepository->paymentReport($request->get('session_id'),'','',true);
    }

}
