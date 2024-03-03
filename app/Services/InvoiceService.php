<?php

namespace App\Services;

use App\Models\TenantPaymentCharge;
use App\Repositories\ApplicantRepository;
use App\Repositories\ConfigurationRepository;
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

        $userPaymentDetails = $this->invoiceTypeRepository->getPaymentDetails($session_id, $user);
        $paymentCategory = $this->invoiceTypeRepository->getPaymentCategoryOfInvoiceTypeId($invoice_type_id);
        
        $payment_category_id = $paymentCategory->id;

        if ($user->user_type === 'applicant') {
            $acceptance_fee_is_enabled = ConfigurationRepository::check('enable_acceptance_fee', 'true');

            if ($acceptance_fee_is_enabled) {
                $application_fee = array_filter($userPaymentDetails, function ($item) {
                    return $item['payment_category']['short_name'] === 'application_fee';
                });

                $acceptance_fee = array_filter($userPaymentDetails, function ($item) {
                    return $item['payment_category']['short_name'] === 'acceptance_fee';
                });

                $registration_fee = array_filter($userPaymentDetails, function ($item) {
                    return $item['payment_category']['short_name'] === 'registration_fee';
                });

                $is_application_fee = $this->isPaymentCategory($userPaymentDetails, $payment_category_id, 'application_fee');
                $is_acceptance_fee = $this->isPaymentCategory($userPaymentDetails, $payment_category_id, 'acceptance_fee');
                $is_registration_fee = $this->isPaymentCategory($userPaymentDetails, $payment_category_id, 'registration_fee');

                if ($is_application_fee) {
                    if (!empty($application_fee) && $application_fee[0]["status"] == 'paid') {
                        throw new \Exception('Application fee has been paid already');
                    }
                }

                if ($is_acceptance_fee) {
                    if (empty($application_fee) || $application_fee[0]["status"] == 'unpaid') {
                        throw new \Exception('Application fee not paid');
                    }

                    if (!empty($acceptance_fee) && $acceptance_fee[0]["status"] == 'paid') {
                        throw new \Exception('Acceptance fee has been paid already');
                    }
                }

                if ($is_registration_fee) {
                    if (empty($application_fee) || $application_fee[0]["status"] == 'unpaid') {
                        throw new \Exception('Application fee not paid');
                    }

                    if ($acceptance_fee_is_enabled && (empty($acceptance_fee) || $acceptance_fee[0]["status"] == 'unpaid')) {
                        throw new \Exception('Acceptance fee not paid');
                    }
                }
            }
        }

        $invoice_type = $this->invoiceTypeRepository->getByIdOrCategory($invoice_type_id);

        if (empty($invoice_type)) {
            throw new \Exception("This payment has not been setup", 400);
        }

        $tenantCharge = TenantPaymentCharge::where(["payment_category_id" => $payment_category_id, "tenant_id" => tenant('id')])->first();
        $charges = $tenantCharge->resolveCharges($invoice_type->amount);        
        $invoiceDetails = [
            'invoice_number' => generateInvoiceNumber(),
            'owner_id' => $user->id,
            'owner_type' => $user->user_type,
            'session_id' => $session_id,
            'semester_id' => $invoice_type->semester_id ?? $semester_id,
            'invoice_type_id' => $invoice_type->id,
            'amount' => $invoice_type->amount,
            'charges'=>$charges,
            'meta_data' => json_encode($meta_data),
            'status' => 'unpaid'
        ];

        return $this->invoiceRepository->createInvoice($invoiceDetails);
    }

    private function isPaymentCategory($userPaymentDetails, $payment_category_id, $payment_category_short_name)
    {
        return array_reduce($userPaymentDetails, function ($carry, $item) use ($payment_category_id, $payment_category_short_name) {
            return $carry || ($item['payment_category']['id'] === $payment_category_id && $item['payment_category']['short_name'] === $payment_category_short_name);
        }, false);
    }
}