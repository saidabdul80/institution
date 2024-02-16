<?php

namespace App\Services;

use App\Repositories\ApplicantRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceTypeRepository;
use App\Repositories\PaymentRepository;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use App\Repositories\StudentRepository;

class InvoiceService
{
    protected $invoiceRepository, $paymentRepository, $applicantRepository, $studentRepository, $invoiceTypeRepository;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        PaymentRepository $paymentRepository,
        ApplicantRepository $applicantRepository,
        StudentRepository $studentRepository,
        InvoiceTypeRepository $invoiceTypeRepository
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->paymentRepository = $paymentRepository;
        $this->applicantRepository = $applicantRepository;
        $this->studentRepository = $studentRepository;
        $this->invoiceTypeRepository = $invoiceTypeRepository;
    }

    public function getByInvoiceNumber($invoice_id, $host)
    {
        $invoice = $this->invoiceRepository->getInvoiceById($invoice_id);
        $subdomain = explode('.', $host)[0];
        $invoice['barcode'] = DNS2D::getBarcodeHTML('https://' . $subdomain .'.jspnigeria.com' . '/' . 'addons/' .'invoice/'. $invoice['invoice_number'], 'QRCODE', 3, 3);
        return $invoice;
    }

    public function getReceipt($invoice_id, $host)
    {
        $invoice = $this->invoiceRepository->getInvoiceById($invoice_id);
        $subdomain = explode('.',$host)[0];
        $invoice['barcode'] = DNS2D::getBarcodeHTML('https://' . $subdomain .'.jspnigeria.com'. '/' . 'addons/' . 'invoice/'. $invoice['invoice_number'], 'QRCODE', 3, 3);
        return $invoice;
    }

    public function getPaymentByReference($ref)
    {
        return $this->paymentRepository->getByRef($ref);
    }

    public function getInvoiceByNumber($invoice_number)
    {
        return $this->invoiceRepository->getInvoiceByNumber($invoice_number);
    }

    public function generateInvoice($request, $user)
    {      
        $session_id = $request->get('session_id');
        $payment_category_slug = $request->get('payment_category_slug');        
        $invoice_type_id = $request->get('invoice_type_id');
        $semester_id = $request->get('semester_id');
        $meta_data = $request->get('meta_data') ?? [];
        $userPaymentDetails = $this->invoiceTypeRepository->getPaymentDetails($user,$session_id);
        $paymentCategory = $this->invoiceTypeRepository->getPaymentCategoryOfInvoiceTypeId($invoice_type_id);        
        
        $payment_category_id  = $paymentCategory->id;
        if($user->user_type ==='applicant'){
            $application_fee = array_filter($userPaymentDetails, function ($item){                            
                return $item['payment_category']['short_name'] === 'application_fee';
            }, false);
            $acceptance_fee = array_filter($userPaymentDetails, function ($item){
                return $item['payment_category']['short_name'] === 'acceptance_fee';
            }, false);
            
            
            $is_application_fee = array_reduce($userPaymentDetails, function ($carry, $item) use ($payment_category_id) {
                //check if invoive to be generated is application fee                
                return $carry || ($item['payment_category']['id'] === $payment_category_id && $item['payment_category']['short_name'] === 'application_fee' && $item['status'] === 'unpaid');
            }, false);
            
            $is_acceptance_fee = array_reduce($userPaymentDetails, function ($carry, $item) use ($payment_category_id) {
                //check if invoive to be generated is acceptance fee
                return $carry || ($item['payment_category']['id'] === $payment_category_id && $item['payment_category']['short_name'] === 'acceptance_fee' && $item['status'] === 'unpaid');
            }, false);
            
            //$item->payment_category->id === $payment_category_id && 
            if($is_acceptance_fee || !$is_application_fee){
                if (count($application_fee)>0) {                    
                    //application fee exists and not paid
                    if(!$is_application_fee){
                    //and current invoice to be generated is not acceptance fee
                    //check if acceptance fee is unpaid and throw error
                        ($application_fee[0]->status ?? 'unpaid') == 'unpaid'?
                        throw new \Exception('Application fee not paid'):'';                    
                    }
                } else if (count($acceptance_fee)>0) {
                    //acceptance fee exists                     
                    if(!$is_acceptance_fee){
                    //and current invoice to be generated is not acceptance fee
                    //check if acceptance fee is unpaid and throw error
                        $acceptance_fee[0]->status??'unpaid' == 'unpaid' ?
                        throw new \Exception('Acceptance fee not paid')
                        :'';
                    }
                }
            }
                      
        }

        $invoice_type = $this->invoiceTypeRepository->getByIdOrCategory(id: $invoice_type_id, category_id: $payment_category_id, category: $payment_category_slug);

        if (empty($invoice_type)) {
            throw new \Exception("This payment has not been setup", 404);
        }

        // $student = $this->studentRepository->getById($owner_id);
        $invoiceDetails = [
            'invoice_number' => generateInvoiceNumber(),
            'owner_id' => $user->id,
            'owner_type' => $user->user_type,
            'session_id' => $session_id,
            'semester_id' => $invoice_type->semester_id ?? $semester_id,
            'invoice_type_id' => $invoice_type->id,
            'amount' => $invoice_type->amount,
            'meta_data' => json_encode($meta_data),
            'status' => 'unpaid'
        ];
        // $applicant = $this->applicantRepository->getById($owner_id);

        return $this->invoiceRepository->createInvoice($invoiceDetails);
    }
}
