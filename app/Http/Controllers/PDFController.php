<?php

namespace App\Http\Controllers;

use App\Models\OlevelResult;
use App\Models\Semester;
use App\Models\Session;
use App\Models\StudentCoursesGrades;
use App\Models\StudentEnrollment;
use App\Services\ApplicantService;
use App\Services\InvoiceService;
use App\Services\StudentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PDFController extends Controller
{
    protected $invoiceService, $applicantService, $studentService;

    public function __construct(InvoiceService $invoiceService, ApplicantService $applicantService, StudentService $studentService)
    {
        $this->invoiceService = $invoiceService;
        $this->applicantService = $applicantService;
        $this->studentService = $studentService;
    }

    protected function schoolInfo($host)
    {
        $school_info = Http::withHeaders(["xtenant" => $host])->get('https://api.jspnigeria.com/api/school-info')->json();
        $school_info['url'] = $host;
        return $school_info;
    }

    public function downloadInvoice(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $invoice = $this->invoiceService->getByInvoiceNumber($request->invoice_id, $request->host);
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_invoice')->first();
        $dompdf = Pdf::setOption(['isRemoteEnabled' => true]);
        $pdf = $dompdf->loadView('printouts.invoice_pdf', ["invoice" => $invoice, "school_info" => $school_info, 'config' => $config]);
        return $pdf->download('invoice.pdf');
    }

    public function downloadPaymentReceipt(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $payment = $this->invoiceService->getReceipt($request->invoice_id, $xtenant);
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_receipt')->first();
        $dompdf = Pdf::setOption(['isRemoteEnabled' => true]);
        $pdf = $dompdf->loadView('printouts.payment_receipt', ["payment" => $payment, "school_info" => $school_info, 'config' => $config]);
        return $pdf->download('receipt.pdf');
    }

    public function downloadSlip(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $payment = $this->invoiceService->getPaymentByReference($request->ref);
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_transaction_slip')->first();
        $pdf = Pdf::loadView('printouts.payment_slip', ["payment" => $payment, "school_info" => $school_info, 'config' => $config]);
        return $pdf->download('slip.pdf');
    }

    public function biodataSlip(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $applicant = $this->applicantService->getApplicant($request->applicant_id);
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_biodata_slip')->first();
        $pdf = Pdf::loadView('printouts.biodata_slip', ["applicant" => $applicant, "school_info" => $school_info, 'config' => $config]);
        return $pdf->download('biodata.pdf');
    }

    public function acknowledgementSlip(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $applicant = $this->applicantService->getApplicant($request->applicant_id);
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_biodata_slip')->first();
        $pdf = Pdf::loadView('printouts.acknowledgement_slip', ["applicant" => $applicant, "school_info" => $school_info, 'config' => $config]);
        return $pdf->download('biodata.pdf');
    }

    public function examCard(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $student = $this->studentService->getStudentById($request->student_id);
        $student_courses = StudentCoursesGrades::where('student_id', $request->student_id)->where('session_id', $request->session_id)->get();
        $settings = DB::table('configurations')->wherein('name', ['show_photo_on_exam_card', 'print_course_form_by', 'exam_rules'])->get();
        $config = [];
        foreach ($settings as $setting)
        {
            $config[$setting->name] = $setting->value;
        }
        $semester = [];
        if ($config['print_course_form_by'] == 'semester')
        {
            $semester = Semester::where('id', $request->semester_id)->first();
        }

        $school_info = $this->schoolInfo($xtenant);
        $exam_rules = $config['exam_rules'];
        $pdf = Pdf::loadView('printouts.exam_card', ["student" => $student,'courses' => $student_courses, "school_info" => $school_info, 'config' => $config, 'exam_rules' => $exam_rules, 'semester' => $semester]);
        return $pdf->download('exam_card.pdf');
    }

    public function courseForm(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $school_info = $this->schoolInfo($xtenant);
        $settings = DB::table('configurations')->where('name', 'show_photo_on_course_reg')->orWhere('name', 'print_course_form_by')->get();
        $config = [];
        foreach ($settings as $setting)
        {
            $config[$setting->name] = $setting->value;
        }
        $student = $this->studentService->getStudentById($request->student_id);
        if ($config['print_course_form_by'] == 'session')
        {
            $student_courses = StudentCoursesGrades::where('student_id', $request->student_id)->where('session_id', $request->session_id)->get();
            $pdf = Pdf::loadView('printouts.course_form', ["student" => $student,'courses' => $student_courses, "school_info" => $school_info, 'config' => $config]);
        }else
        {
            $semester = Semester::where('id', $request->semester_id)->first();
            $student_courses = StudentCoursesGrades::where('student_id', $request->student_id)->where('session_id', $request->session_id)->where('semester_id', $request->semester_id)->get();
            $pdf = Pdf::loadView('printouts.course_form_semester', ["student" => $student,'courses' => $student_courses, "school_info" => $school_info, 'config' => $config, 'semester' => $semester]);
        }

        return $pdf->download('course_form.pdf');
    }

    public function resultSlip(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $data = $this->studentService->getStudentWithResult($request);
        $student_enrollment = StudentEnrollment::where('session_id', $request->session_id)->where('owner_id', $request->student_id)->where('owner_type', 'student')->first();
        $level = 'NA';
        if ($student_enrollment)
        {
            $level = $student_enrollment->level_to;
        }
        $session = Session::find($request->session_id);
        $meta = [
            "session" => $session->title,
            "level" => $level
        ];
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_result_slip')->first();
        $pdf = Pdf::loadView('printouts.result_slip', ["data" => $data, "school_info" => $school_info, 'config' => $config, 'meta' => $meta]);
        return $pdf->download('result_slip.pdf');
    }

    public function olevelSlip(Request $request)
    {
        $xtenant = $request->header('xtenant');
        $data = OlevelResult::where('applicant_id', $request->applicant_id)->where('session_id', $request->session_id)->with('applicant')->get();
        $session = Session::find($request->session_id);
        $meta = [
            "session" => $session->title,
        ];
        $school_info = $this->schoolInfo($xtenant);
        $config = DB::table('configurations')->where('name', 'show_photo_on_olevel_slip')->first();
        $pdf = Pdf::loadView('printouts.olevel_slip', ["data" => $data, "school_info" => $school_info, 'config' => $config, 'meta' => $meta]);
        return $pdf->download('olevel_slip.pdf');
    }
}
