<?php
namespace Modules\Application\Services;

use App\Services\PaymentService as AppPaymentService;
use Exception;
use Modules\Application\Repositories\PaymentRepository;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Illuminate\Support\Facades\Redis;
use Modules\Application\Repositories\ApplicantRepository;

class PaymentsService{

    private $paymentRepository;
    private $remita_host = "https://remitademo.net/remita/exapp/api/v1/send/api/";
    public $appPaymentService;
    private $applicantRepository;

    public function __construct(PaymentRepository $paymentRepository, AppPaymentService $appPaymentService, ApplicantRepository $applicantRepository)
    {
        $this->paymentRepository = $paymentRepository;
        $this->appPaymentService = $appPaymentService;
        $this->applicantRepository = $applicantRepository;
    }

    public function processPaymentStoreDetails($request){
        //{check payment status if available}//

        $this->paymentRepository->storeDetails($request);
        $applicant = auth('api:applicantsportal')->user();
        $applicant_progress =  $this->applicantRepository->getApplicationProgress($applicant, false);
                               $this->applicantRepository->updateApplicantProgress($applicant->id, $applicant_progress);
        return 'success';
    }

    public function distinctPayments($request){

       return $this->paymentRepository->getDistinctPaymentsByApplicants($request);
    }

    public function applicantInvoice($request){
        $applicant_id = $request->get('applicant_id');
        $session_id = $request->get('session_id');
        $payment_category_short_name = $request->get('payment_category_short_name');
        $applicant = auth('api:applicantsportal')->user();
       // $faculty_id = $this->paymentRepository->getFacultyByDepartmentId($applicant->department_id);
        $query = [
            "gender" => $applicant->gender,
            "programme_id" => $applicant->applied_programme_id,
            "programme_type_id" => $applicant->programme_type_id,
            "department_id" => $applicant->department_id,
            "faculty_id" => $applicant->faculty_id,
            "entry_mode_id" => $applicant->mode_of_entry_id,
            "state_id" => $applicant->state_id,
            "lga_id" => $applicant->lga_id,
            "country_id" => $applicant->country_id,
            "session_id" => $request->get('session_id'),
           ];

    //    $invoice = $this->paymentRepository->invoiceExist($applicant_id,$session_id,$payment_category_short_name,$query);
    //    if($invoice){
    //        return $invoice;
    //    }

    $invoice_number = generateInvoiceNumber();

    $invoice = $this->paymentRepository->getInvoice($applicant_id,$invoice_number,$session_id,$payment_category_short_name,$query);
    return $invoice;

    }

    public function getAllInvoiceTypes($request){

        $query = [
            "session_id" => $request->get('session_id'),
            "owner_type"=> $request->get('owner_type'),
           ];

        $invoiceTypes = $this->paymentRepository->getAllInvoiceTypes($query);
       return $invoiceTypes;


    }

    public function getApplicantInvoices($request)
    {
        return $this->paymentRepository->applicantInvoicesById($request->user()->id);
    }

    public function getApplicantPayments($request)
    {
        return $this->paymentRepository->applicantPayments($request->get('session_id'), $request->get('owner_id'));
    }

    public function proceedToPay($invoice)
    {
        $makePayment = new ReflectionMethod(AppPaymentService::class, 'makePayment');

        $invoice['full_name'] = $invoice['first_name'] . " " . $invoice['middle_name'] . " " . $invoice['surname'];

        if (isset($invoice['matric_number'])) {
            $invoice['matric_number'] = $invoice['matric_number'];
        } else {
            $invoice['application_number'] = $invoice['application_number'];
        }

        // $invoice['matric_number'] = $invoice['application_number'];
        $invoice['invoice_id'] = $invoice['id'];
        // $invoice['session_id'] = 3;
        $invoice['status'] = 'pending';
        // unset($invoice['id']);
        $response = $makePayment->invoke($this->appPaymentService, $invoice, "https://api.jspnigeria.com/payments/webhook");
        Redis::hset($response->payment_reference, 'tenant_id', tenant('id'));
        return $response;
    }

    public function requery($rrr)
    {
        return $this->appPaymentService->requeryRemita($rrr);
    }

    public function generateRRR($invoice)
    {
        $generateRRR = new ReflectionMethod(AppPaymentService::class, 'generateRRR');
        $generateJtr = new ReflectionMethod(AppPaymentService::class, 'generateJtr');

        $generateRRR->setAccessible(true);
        $generateJtr->setAccessible(true);
        $invoice['jtr'] = $generateJtr->invoke($this->appPaymentService);
        $invoice['full_name'] = $invoice['first_name']." ". $invoice['middle_name'] . " " .$invoice['surname'];
        if (isset($invoice['matric_number'])){
            $invoice['matric_number'] = $invoice['matric_number'];
        } else {
            $invoice['application_number'] = $invoice['application_number'];
        }
        $invoice['invoice_id'] = $invoice['invoice_number'];

        $response = $generateRRR->invoke($this->appPaymentService, $invoice, $this->remita_host, "https://api.jspnigeria.com/payments/webhook");
        $response['jtr'] = $invoice['jtr'];
        Redis::hset($response['RRR'],'tenant_id', tenant('id'));
        return $response;
    }

    public function paymentWebhook($payload)
    {
        $tenant_id = Redis::hget($payload[0]['rrr'] ?? 1, 'tenant_id');
        $domain = Redis::get('domain_' . $tenant_id);
        tenancy()->initialize($tenant_id);
        $webhookRemita = new ReflectionMethod(AppPaymentService::class, 'webhookRemita');
        return $webhookRemita->invoke($this->appPaymentService, $payload, $domain);
    }

}
