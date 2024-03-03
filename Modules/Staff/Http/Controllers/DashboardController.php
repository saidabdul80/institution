<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Modules\Staff\Services\DashboardService;
use Modules\Staff\Transformers\UtilResource;


class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    private $dashboardService;
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function admission(Request $request){

        
        $request->validate([
            "session_id" => "required",
        ]);
        
        $response = $this->dashboardService->admissionDashboard($request);
        try{
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }


    public function report(Request $request){

        try{

            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->reportDashboard($request);
            //return dd($response);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function info(Request $request){

        try{

            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->info($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }

    public function finance(Request $request){
        try{

            $request->validate([
                "session_id" => "required",
              /*   "type" => "required",
                "payment_name" => "required", */
            ]);

            $response = $this->dashboardService->financeReport($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function mainDashboard(Request $request){
        try{
            $request->validate([
                "session_id" =>"required",
            ]);

            $response = $this->dashboardService->mainDashboard($request);
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }
    }

    public function feeReport(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->feeReport($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }


    public function invoicesByPaymentName(Request $request)
    {
        try {
            $request->validate([
                "payment_short_name" => "required",
                "session_id" => "required"
            ]);

            $response = $this->dashboardService->invoicesByPaymentName($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function sessionsFeeReport(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->sessionsFeeReport($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function programmeTypesReport(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->programmeTypesReport($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function recentLogins(Request $request)
    {
        try {
            $request->validate([
                "model_name" => "required",
            ]);

            $response = $this->dashboardService->recentLogins($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function admissionReportAdmittedByProgramme(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->admissionReportAdmittedByProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function admissionReportAdmittedByProgrammeType(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->admissionReportAdmittedByProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function qualificationReportQualifiedByProgrammeType(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->qualificationReportQualifiedByProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function qualificationReportQualifiedByProgramme(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
            ]);

            $response = $this->dashboardService->qualificationReportQualifiedByProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function totalPaidAndUnpaid(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaid($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function totalPaidAndUnpaidByProgrammeType(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidByProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function totalPaidAndUnpaidByProgramme(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidByProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function studentByPhysicalChallenge(Request $request)
    {
        try {
            $response = $this->dashboardService->studentByPhysicalChallenge($request);
            return new APIResource($response, false, 200);        
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function admittedByEntryMode(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",                                            
            ]);

            $response = $this->dashboardService->admittedByEntryMode($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function activeAndNonActiveStudent(Request $request)
    {
        try {
           
            $response = $this->dashboardService->activeAndNonActiveStudent();
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function studentRegisteredAndUnregisteredCount(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",                                              
                "programme_type_id"=>"required"
            ]);

            $response = $this->dashboardService->studentRegisteredAndUnregisteredCount($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function schoolFeeTotalPaidAndUnpaid(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",                                                              
            ]);

            $response = $this->dashboardService->schoolFeeTotalPaidAndUnpaid($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function allFeeReport(Request $request)
    {
        try {
            $request->validate([
                "session_id" => "required",                
            ]);

            $response = $this->dashboardService->allFeeReport($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    } 
    
    public function allFeeReportByRange(Request $request)
    {
        try {
            $request->validate([
                "start_date" => "required",                
                "end_date" => "required",                
            ]);
            $response = $this->dashboardService->allFeeReportByRange($request);

            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    } 

    
    public function applicantByPhysicalChallenge(Request $request)
    {
        try {
            $response = $this->dashboardService->applicantByPhysicalChallenge($request);
            return new APIResource($response, false, 200);        
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function studentsBySponsorship(Request $request)
    {
        try {
            $response = $this->dashboardService->studentsBySponsorship($request);
            return new APIResource($response, false, 200);        
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function totalStudentsByLevels(Request $request)
    {
        try {
            $request->validate([
                "session_id"=>"required"
            ]);
            $response = $this->dashboardService->totalStudentsByLevels($request);
            return new APIResource($response, false, 200);        
        }catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        }catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
    
    public function totalStudentsByProgrammes(Request $request)
    {
        try {
            
            $response = $this->dashboardService->totalStudentsByProgrammes($request);
            return new APIResource($response, false, 200);        
        }catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
    
  /*   public function totalPaidAndUnpaidAmountByProgrammeType(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidAmountByProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
 */
    public function totalPaidAndUnpaidAmountByProgrammeType(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidAmountByProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

  /*   public function totalPaidAndUnpaidAmountBylevelProgrammeType(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidAmountBylevelProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    } */

    public function totalPaidAndUnpaidAmountBylevelProgrammeType(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidAmountBylevelProgrammeType($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function totalPaidAndUnpaidAmountBylevelProgramme(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
                "owner_type"=>"required"
            ]);
            
            $response = $this->dashboardService->totalPaidAndUnpaidAmountBylevelProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }


/*     public function totalPaidAndUnpaidAmountBylevelProgramme(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);
            
            $response = $this->dashboardService->totalPaidAndUnpaidAmountBylevelProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
 */

   /*  public function totalPaidAndUnpaidAmountByProgramme(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidAmountByProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
 */
    public function totalPaidAndUnpaidAmountByProgramme(Request $request)
    {        
        try {
            $request->validate([
                "session_id" => "required",
                "payment_short_name" => "required",
            ]);

            $response = $this->dashboardService->totalPaidAndUnpaidAmountByProgramme($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function walletReport(){
        try {
       
            $response = $this->dashboardService->walletReport();
            return new APIResource($response, false, 200);
         } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function walletFundingLog(Request $request){
        try {
       
            $response = $this->dashboardService->walletFundingLog($request);
            return new APIResource($response, false, 200);
         } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function walletSettlementLog(Request $request){
        try {
       
            $response = $this->dashboardService->walletSettlementLog($request);
            return new APIResource($response, false, 200);
         } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
    
    public function walletFunding(Request $request)
    {        
        try {
            $request->validate([                
                "from" => "required",
                "to" => "required",
            ]);

            $response = $this->dashboardService->walletFunding($request);
            return new APIResource($response, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    
    public function paymentReport(Request $request){

        
        $request->validate([
            "session_id" => "required",
        ]);
        
        $response = $this->dashboardService->paymentReport($request);
        try{
            return new APIResource($response, false, 200 );

        }catch(ValidationException $e){
            return new APIResource(array_values($e->errors())[0], true, 400 );
        }catch(Exception $e){
            return new APIResource($e->getMessage(), true, 400 );
        }

    }
    

    
}

