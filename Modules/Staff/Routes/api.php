<?php

use App\Events\InvoicePaid;
use Illuminate\Http\Request;
use Modules\Staff\Http\Controllers\StaffController;
use Modules\Staff\Http\Controllers\FacultyController;
use Modules\Staff\Http\Controllers\DepartmentController;
use Modules\Staff\Http\Controllers\CourseController;
use Modules\Staff\Http\Controllers\ProgrammeController;
use Modules\Staff\Http\Controllers\DashboardController;
use Modules\Staff\Http\Controllers\AdmissionController;
use Modules\Staff\Http\Controllers\ApplicantImportController;
use Modules\Staff\Http\Controllers\PermissionController;

use Modules\Staff\Http\Controllers\StaffCourseController;
use Modules\Staff\Http\Controllers\TranscriptController;
use App\Http\Controllers\CentralController;

use Illuminate\Support\Facades\Route;
use Modules\Staff\Http\Controllers\ConfigurationController;
use Modules\Staff\Http\Controllers\InvoiceTypeController;
use Modules\Staff\Http\Controllers\MenuController;
use Modules\Staff\Http\Controllers\SessionController;
use Modules\Staff\Http\Controllers\ProgrammeTypeController;
use Modules\Student\Http\Controllers\PaymentController;
use Modules\Student\Http\Controllers\StudentController;

use App\Http\Controllers\PaymentController as CentralPaymentController;
use App\Http\Controllers\InvoiceController as CentralInvoiceController;
use App\Http\Controllers\PDFController;
use App\Jobs\CreateInvoice;

use App\Jobs\CreateInvoiceApplicant;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Modules\Staff\Http\Controllers\ApplicantController;
use Modules\Staff\Http\Controllers\InvoiceController;
use Modules\Staff\Http\Controllers\StudentController as ControllersStudentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('staff')->group(function () {
    Route::get('/login', function () {
        return ["message" => "You must be logged in to do that!"];
    });
    Route::post('/login', [StaffController::class, 'login']);

    Route::group(["middleware" => ['auth:api-staff']], function () {

        Route::post('/logout', [StaffController::class, 'logout']);
        Route::group(["prefix" => "staff"], function () {

            Route::post('/change_applicant_programme', [AdmissionController::class, 'changeApplicantProgramme'])->middleware('permission:can_change_student_programme');
            Route::post('/change_password', [StaffController::class, 'updatePassword']);
            Route::post('/reset_password', [StaffController::class, 'resetPassword']);
            Route::post('/update', [StaffController::class, 'update'])->middleware(['permission:can_edit_staff']);
            Route::post('/create', [StaffController::class, 'create'])->middleware(['permission:can_create_staff']);

            Route::post('/bulk_upload', [StaffController::class, 'bulkUpload'])->middleware(['permission:can_create_staff']);
            Route::post('/deactivate', [StaffController::class, 'deactivate'])->middleware(['permission:can_delete_staff']);
            Route::post('/activate', [StaffController::class, 'activate'])->middleware(['permission:can_delete_staff']);
            Route::post('/deassign_role', [StaffController::class, 'deAssignRole'])->middleware(['permission:can_assign_role_to_staff']);
            Route::post('/assign_role', [StaffController::class, 'assignRole'])->middleware(['permission:can_deassign_role_to_staff']);
            Route::post('/staff_by_role', [StaffController::class, 'staffInRole'])->middleware(['permission:can_assign_role_to_staff']);
            Route::post('/staffs', [StaffController::class, 'getStaffs'])->middleware(['permission:can_view_staff']);
            Route::post('/uploadPicture', [StaffController::class, 'uploadPicture']);
            Route::post('/application_fees', [StaffController::class, 'feesController'])->middleware(['permission:can_view_payment']);
            Route::get('/{id?}', [StaffController::class, 'getStaffById'])->middleware(['permission:can_view_staff']);
            Route::get('/staff_courses/{staff_id}', [StaffController::class, 'getStaffCoursesByStaffID'])->middleware(['permission:can_view_staff'])->where('staff_id', '[0-9]+');
            Route::get('/staff_courses/all', [StaffController::class, 'getAllStaffWithCourses'])->middleware(['permission:can_view_staff']);
            Route::post('/assign_course', [StaffController::class, 'assignCourses'])->middleware(['permission:can_assign_course']);
            Route::post('/unassign_course', [StaffController::class, 'unAssignCourses'])->middleware(['permission:can_unassign_course']);
            Route::get('/template', [StaffController::class, 'getTemplate'])->withoutMiddleware('tenancy');
            Route::get('/courses', [StaffController::class, 'getStaffCourses'])->withoutMiddleware('tenancy');
        });

        Route::group(["prefix" => "faculty"], function () {

            Route::post('/update', [FacultyController::class, 'update'])->middleware(['permission:can_edit_faculty']);
            Route::post('/create', [FacultyController::class, 'create'])->middleware(['permission:can_create_faculty']);

            Route::post('/bulk_upload', [FacultyController::class, 'bulkUpload'])->middleware(['permission:can_create_faculty']);
            Route::post('/deactivate', [FacultyController::class, 'deactivate'])->middleware(['permission:can_delete_faculty']);
            Route::post('/activate', [FacultyController::class, 'activate'])->middleware(['permission:can_delete_faculty']);
            Route::get('/template', [FacultyController::class, 'getTemplate'])->withoutMiddleware('tenancy');
            Route::get('/faculties', [FacultyController::class, 'getFaculties']);
        });

        Route::group(["prefix" => "department"], function () {
            Route::post('/update', [DepartmentController::class, 'update'])->middleware(['permission:can_edit_department']);
            Route::post('/create', [DepartmentController::class, 'create'])->middleware(['permission:can_create_department']);
            Route::post('/bulk_upload', [DepartmentController::class, 'bulkUpload'])->middleware(['permission:can_create_department']);
            Route::post('/deactivate', [DepartmentController::class, 'deactivate'])->middleware(['permission:can_delete_department']);
            Route::post('/activate', [DepartmentController::class, 'activate'])->middleware(['permission:can_delete_department']);
            Route::get('/template', [DepartmentController::class, 'getTemplate'])->withoutMiddleware('tenancy');
            Route::get('/departments', [DepartmentController::class, 'getDepartments']);
            Route::post('/departments', [DepartmentController::class, 'getDepartments']);
        });

        Route::group(["prefix" => "course"], function () {
            Route::post('/update', [CourseController::class, 'update'])->middleware(['permission:can_create_course']);
            Route::post('/create', [CourseController::class, 'create'])->middleware(['permission:can_create_course']);

            Route::post('/bulk_upload', [CourseController::class, 'bulkUpload'])->middleware(['permission:can_create_course']);
            Route::post('/deactivate', [CourseController::class, 'deactivate'])->middleware(['permission:can_create_course']);
            Route::post('/activate', [CourseController::class, 'activate'])->middleware(['permission:can_create_course']);
            Route::get('/courses', [CourseController::class, 'getCourses'])->middleware(['permission:can_view_course']);
            Route::get('/template', [CourseController::class, 'getTemplate'])->withoutMiddleware('tenancy');
            //Route::get('/courses', [CourseController::class, 'getCourses']);
        });

        Route::group(["prefix" => "course_category"], function () {
            Route::post('/update', [CourseController::class, 'updateCourseCategory'])->middleware(['permission:can_create_course']);
            Route::post('/create', [CourseController::class, 'createCourseCategory'])->middleware(['permission:can_create_course']);
            Route::post('/deactivate', [CourseController::class, 'deactivateCourseCategory'])->middleware(['permission:can_create_course']);
            Route::post('/activate', [CourseController::class, 'activateCourseCategory'])->middleware(['permission:can_create_course']);
            Route::get('/course_categories', [CourseController::class, 'getCourseCategories'])->middleware(['permission:can_view_course']);
            Route::get('/course_categories_with_inactive', [CourseController::class, 'getCourseCategoriesWithInactive'])->middleware(['permission:can_view_course']);
            Route::get('/id/{id}', [CourseController::class, 'getCourseCategoryById'])->withoutMiddleware('tenancy');
        });

        Route::group(["prefix" => "programme"], function () {
            Route::post('/update', [ProgrammeController::class, 'update'])->middleware(['permission:can_edit_programme']);
            Route::post('/create', [ProgrammeController::class, 'create'])->middleware(['permission:can_create_programme']);
            Route::post('/bulk_upload', [ProgrammeController::class, 'bulkUpload'])->middleware(['permission:can_create_programme']);
            Route::post('/deactivate', [ProgrammeController::class, 'deactivate'])->middleware(['permission:can_delete_programme']);
            Route::post('/activate', [ProgrammeController::class, 'activate'])->middleware(['permission:can_delete_programme']);
            Route::get('/programmes', [ProgrammeController::class, 'getProgrammes'])->middleware(['permission:can_view_programme']);
            Route::get('/template', [ProgrammeController::class, 'getTemplate'])->withoutMiddleware('tenancy');
        });

        Route::group(["prefix" => "programme_course"], function () {
            Route::post('/assign_course', [ProgrammeController::class, 'assignCourse'])->middleware(['permission:can_assign_course']);
            Route::post('/update_programme_course', [ProgrammeController::class, 'updateProgrammeCourse'])->middleware(['permission:can_assign_course']);
            Route::post('/unassign_course', [ProgrammeController::class, 'unAssignCourses'])->middleware(['permission:can_unassign_course']);
            Route::get('/programme_courses/{search?}', [ProgrammeController::class, 'getProgrammeCourses'])->middleware(['permission:can_view_programme_courses']);
        });

        Route::group(["prefix" => "admission_batch"], function () {
            Route::post('/create', [AdmissionController::class, 'createBatch'])->middleware(['permission:can_create_admission_batch']);
            Route::post('/update', [AdmissionController::class, 'updateBatch'])->middleware(['permission:can_create_admission_batch']);
            Route::post('/delete', [AdmissionController::class, 'deleteBatch'])->middleware(['permission:can_create_admission_batch']);
            Route::get('/all', [AdmissionController::class, 'getAllBatches'])->middleware(['permission:can_view_admission_batch']);
        });

        Route::group(["prefix" => "sessions"], function () {
            Route::post('/create', [SessionController::class, 'createSession'])->middleware(['permission:can_assign_course']);
            Route::post('/update', [SessionController::class, 'updateSession'])->middleware(['permission:can_assign_course']);
            Route::post('/delete', [SessionController::class, 'deleteSession'])->middleware(['permission:can_unassign_course']);
            Route::get('/all', [SessionController::class, 'getSession'])->middleware(['permission:can_view_programme_courses']);
        });

        Route::group(["prefix" => "dashboard"], function () {
            Route::post('/admission', [DashboardController::class, 'admission'])->middleware("permission:can_view_admission_dashboard");
            Route::post('/report', [DashboardController::class, 'report'])->middleware("permission:can_view_report_dashboard");
            Route::post('/info', [DashboardController::class, 'info'])->middleware("permission:can_view_report_dashboard");
            Route::post('/financial', [DashboardController::class, 'finance']); //->middleware("permission:can_view_finance_dashboard");
            // Route::post('/main_dashboard', [DashboardController::class, 'mainDashboard'])->middleware("permission:can_view_dashboard");
            Route::post('/paid_applicant', [InvoiceTypeController::class, 'getPaidApplicant']);
            Route::post('/paid_students', [InvoiceTypeController::class, 'getPaidStudent']);
            Route::post('/by_payment_category', [InvoiceTypeController::class, 'getInvoiceTypeByCategory']);
            Route::post('/total_paid_by_invoice_type_id', [InvoiceTypeController::class, 'getTotalPaidByInvoiceType']);
            Route::post('/total_paid_by_payment_name', [InvoiceTypeController::class, 'getTotalPayByPaymentName']);
            Route::post('/fees_report', [DashboardController::class, 'feeReport']);
            Route::post('/invoices_by_payment_name', [DashboardController::class, 'invoicesByPaymentName']);

            Route::post('/sessions_fee_report', [DashboardController::class, 'sessionsFeeReport']);
            Route::post('/programme_types_report', [DashboardController::class, 'programmeTypesReport']);
            Route::post('/recent_logins', [DashboardController::class, 'recentLogins']);
            Route::post('/admission_report_admitted_by_programme', [DashboardController::class, 'admissionReportAdmittedByProgramme']);
            Route::post('/admission_report_admitted_by_programme_type', [DashboardController::class, 'admissionReportAdmittedByProgrammeType']);
            Route::post('/admission_report_qualified_by_programme', [DashboardController::class, 'qualificationReportqualifiedByProgrammeType']);
            Route::post('/admission_report_qualified_by_programme_type', [DashboardController::class, 'qualificationReportqualifiedByProgramme']);
            Route::post('/total_paid_and_unpaid', [DashboardController::class, 'totalPaidAndUnpaid']);
            Route::post('/total_paid_and_unpaid_by_progamme_type', [DashboardController::class, 'totalPaidAndUnpaidByProgrammeType']);
            Route::post('/total_paid_and_unpaid_by_progamme', [DashboardController::class, 'totalPaidAndUnpaidByProgramme']);

            Route::post('/student_by_physical_challenge', [DashboardController::class, 'studentByPhysicalChallenge']);
            Route::post('/applicant_by_physical_challenge', [DashboardController::class, 'applicantByPhysicalChallenge']);
            Route::post('/admitted_by_entry_mode', [DashboardController::class, 'admittedByEntryMode']);
            Route::post('/active_and_non_active_student', [DashboardController::class, 'activeAndNonActiveStudent']);
            Route::post('/student_registered_and_unregistered_count', [DashboardController::class, 'studentRegisteredAndUnregisteredCount']);
            Route::post('/school_fee_total_paid_and_unpaid', [DashboardController::class, 'schoolFeeTotalPaidAndUnpaid']);
            Route::post('/all_fee_report', [DashboardController::class, 'allFeeReport']);
            Route::post('/all_fee_by_date_range', [DashboardController::class, 'allFeeReportByRange']);
            Route::post('/candidates_count_by_sponsorship', [DashboardController::class, 'studentsBySponsorship']);
            Route::post('/total_students_by_level', [DashboardController::class, 'totalStudentsByLevels']);
            Route::post('/total_students_by_programmes', [DashboardController::class, 'totalStudentsByProgrammes']);

            Route::post('/total_paid_and_unpaid_amount_by_programme_type', [DashboardController::class, 'totalPaidAndUnpaidAmountByProgrammeType']);
            Route::post('/total_paid_and_unpaid_amount_by_level_programme_type', [DashboardController::class, 'totalPaidAndUnpaidAmountBylevelProgrammeType']);
            Route::post('/total_paid_and_unpaid_amount_by_level_programme', [DashboardController::class, 'totalPaidAndUnpaidAmountBylevelProgramme']);
            Route::post('/total_paid_and_unpaid_amount_by_progamme', [DashboardController::class, 'totalPaidAndUnpaidAmountByProgramme']);
            Route::post('/wallet_report', [DashboardController::class, 'walletReport']);
            Route::post('/wallet_funding_log', [DashboardController::class, 'walletFundingLog']);
            Route::post('/wallet_settlement_log', [DashboardController::class, 'walletSettlementLog']);
            Route::post('/wallet_funding', [DashboardController::class, 'walletFunding']);
            Route::post('/payment_reports', [DashboardController::class, 'paymentReport']);
            /*Route::post('/student_registered_and_unregistered_count', [DashboardController::class, 'studentRegisteredAndUnregisteredCount']);                                  */
        });

        Route::group(["prefix" => "admission"], function () {
            Route::post('/paid_applicants', [AdmissionController::class, 'allApplicants'])->middleware('permission:can_view_payment');
            Route::post('/admit', [AdmissionController::class, 'applicantAdmission'])->middleware('permission:can_give_admission');
            Route::post('/admit_csv', [AdmissionController::class, 'bulkApplicantAdmission'])->middleware('permission:can_give_admission');
            Route::post('/reject_applicants', [AdmissionController::class, 'unAdmitApplicant'])->middleware('permission:can_give_admission');
            Route::post('/activate', [AdmissionController::class, 'activateStudent'])->middleware('permission:can_activate_student');
            Route::post('/update_qualified_status', [AdmissionController::class, 'updateQualifiedStatus'])->middleware('permission:can_set_applicant_qualification_status');
            Route::post('/update_admission_status', [AdmissionController::class, 'updateAdmissionStatus'])->middleware('permission:can_give_admission');
            Route::post('/applicants', [AdmissionController::class, 'getApplicant'])->middleware('applicantAdmission');
            Route::post('/get_batches', [AdmissionController::class, 'getBatches']); //->middleware('permission:can_view_applicant');
            Route::get('/template', [AdmissionController::class, 'getTemplate'])->withoutMiddleware('tenancy');

            /* Route::get('/change_department', [AdmissionController::class, 'changeDepartment']);
            Route::get('/change_faculty', [AdmissionController::class, 'changeFaculty']);
            Route::get('/change_level', [AdmissionController::class, 'changeLevel']); */
        });


        Route::group(["prefix" => "applicants"], function () {
            Route::post('/update', [ApplicantController::class, 'updateApplicant']);
            Route::post('/export', [ApplicantController::class, 'exportApplicants']);
            Route::post('/all', [ApplicantController::class, 'getAllApplicants']); //->middleware('permission:can_view_applicant');
            Route::get('/stats', [ApplicantController::class, 'getApplicantStats']); //s->middleware('permission:can_view_applicant');
            Route::post('/update_status', [ApplicantController::class, 'updateApplicantStatus'])->middleware('permission:can_give_admission');
            Route::post('/bulk_update_status', [ApplicantController::class, 'bulkUpdateApplicantStatus'])->middleware('permission:can_give_admission');
            Route::post('/process', [ApplicantController::class, 'processApplication'])->middleware('permission:can_set_applicant_qualification_status');
        });

        // Applicant Import Routes
        Route::group(["prefix" => "applicant-import"], function () {
            Route::post('/upload', [ApplicantImportController::class, 'uploadFile'])->middleware('permission:can_import_applicants');
            Route::post('/process', [ApplicantImportController::class, 'processImport'])->middleware('permission:can_import_applicants');
            Route::get('/history', [ApplicantImportController::class, 'getImportHistory'])->middleware('permission:can_view_applicant');
            Route::get('/template', [ApplicantImportController::class, 'downloadTemplate']);
        });

        // Permission Management Routes
        Route::group(["prefix" => "permissions"], function () {
            Route::get('/list', [PermissionController::class, 'getPermissions'])->middleware('permission:can_manage_permissions');
            Route::get('/roles', [PermissionController::class, 'getRoles'])->middleware('permission:can_manage_roles');
            Route::post('/update', [PermissionController::class, 'updatePermissions'])->middleware('permission:can_manage_permissions');
            Route::post('/assign-role', [PermissionController::class, 'assignRole'])->middleware('permission:can_manage_roles');
            Route::post('/remove-role', [PermissionController::class, 'removeRole'])->middleware('permission:can_manage_roles');
            Route::get('/users', [PermissionController::class, 'getUsersWithRoles'])->middleware('permission:can_manage_roles');
            Route::post('/check', [PermissionController::class, 'checkPermission'])->middleware('permission:can_manage_permissions');
        });

        Route::group(["prefix" => "student"], function () {

            Route::post('/update', [StudentController::class, 'updateStudent']);
            Route::post('/students', [AdmissionController::class, 'getStudents'])->middleware('permission:can_view_students');
            Route::post('/status', [StaffController::class, 'updateStudentStatus'])->middleware('permission:can_view_students');
            Route::post('/requery/{payment_reference?}', [CentralPaymentController::class, 'requery']);
            Route::post('/promote_students', [StaffController::class, 'promoteStudents'])->middleware('permission:can_promote_students');
            Route::post('/reverse_student_promotion', [StaffController::class, 'reverseStudentsPromotion'])->middleware('permission:can_reverse_students_promotion');
            Route::get('/promotion_logs', [StaffController::class, 'promotionLogs'])->middleware('permission:can_promote_students');
            Route::post('/export', [StudentController::class, 'exportStudents']);
        });

        Route::group(["prefix" => "students"], function () {
            Route::get('/stats', [ControllersStudentController::class, 'getStudentStats'])->middleware('permission:can_view_students');
            Route::post('/create', [ControllersStudentController::class, 'createStudent'])->middleware('permission:can_create_student');
            Route::post('/bulk_upload', [ControllersStudentController::class, 'bulkUploadStudents'])->middleware('permission:can_create_student');
            Route::get('/{id}', [StudentController::class, 'getStudentById'])->middleware('permission:can_view_students');
            Route::get('/courses', [ControllersStudentController::class, 'getStudentCourses'])->middleware('permission:can_view_students');
            Route::get('/results', [ControllersStudentController::class, 'getStudentResults'])->middleware('permission:can_view_results');
            Route::post('/search', [ControllersStudentController::class, 'searchStudents'])->middleware('permission:can_view_students');
            Route::get('/academic_records/{id}', [ControllersStudentController::class, 'getStudentAcademicRecords'])->middleware('permission:can_view_results');
        });

        Route::group(["prefix" => "transcripts"], function () {
            Route::post('/generate', [TranscriptController::class, 'generateTranscript'])->middleware('permission:can_generate_transcript');
            Route::post('/email', [TranscriptController::class, 'emailTranscript'])->middleware('permission:can_generate_transcript');
        });



        // Staff and Course Management
        Route::get('/all-staff', [StaffCourseController::class, 'getAllStaff'])->middleware('permission:can_view_staff');
        Route::get('/all-courses', [StaffCourseController::class, 'getAllCourses'])->middleware('permission:can_view_courses');
        Route::get('/staff/{id}/allocated-courses', [StaffCourseController::class, 'getStaffAllocatedCourses'])->middleware('permission:can_view_staff_allocations');
        Route::get('/course/{id}/allocated-staff', [StaffCourseController::class, 'getCourseAllocatedStaff'])->middleware('permission:can_view_staff_allocations');
        Route::post('/bulk-allocations', [StaffCourseController::class, 'bulkCreateAllocations'])->middleware('permission:can_manage_staff_allocations');
        Route::get('/allocation-statistics', [StaffCourseController::class, 'getAllocationStatistics'])->middleware('permission:can_view_staff_allocations');
        Route::post('/copy-allocations', [StaffCourseController::class, 'copyAllocations'])->middleware('permission:can_manage_staff_allocations');

        Route::group(["prefix" => "session"], function () {
            Route::post('/update', [SessionController::class, 'update'])->middleware('permission:can_update_session');
            Route::post('/create', [SessionController::class, 'create'])->middleware('permission:can_create_session');
            Route::get('/sessions', [SessionController::class, 'getSessions']);
        });

        Route::group(["prefix" => "programme_type"], function () {
            Route::post('/create', [ProgrammeTypeController::class, 'create'])->middleware('permission:can_create_edit_programme_type');
            Route::post('/update', [ProgrammeTypeController::class, 'update'])->middleware('permission:can_create_edit_programme_type');
            Route::post('/delete', [ProgrammeTypeController::class, 'delete'])->middleware('permission:can_delete_programme_type');
            Route::get('/programme_types', [CentralController::class, 'programmeType']);
            Route::get('/all', [ProgrammeTypeController::class, 'getProgrammeTypes'])->middleware('permission:can_view_programme_type');
        });

        Route::group(["prefix" => "invoice_type"], function () {
            Route::post('/update', [InvoiceTypeController::class, 'update'])->middleware('permission:can_create_invoice_type');
            Route::post('/create', [InvoiceTypeController::class, 'create'])->middleware('permission:can_edit_invoice_type');
            Route::post('/delete', [InvoiceTypeController::class, 'delete'])->middleware('permission:can_delete_invoice_type');
            Route::post('/update_status', [InvoiceTypeController::class, 'udateStatus'])->middleware('permission:can_update_invoice_type_status');
            Route::get('/all/{session_id?}', [InvoiceTypeController::class, 'getInvoiceTypes'])->middleware('permission:can_view_invoice_type');
        });

        Route::group(["prefix" => "invoice"], function () {
            Route::post('/manual_confirmation', [InvoiceTypeController::class, 'manualInvoicePaymentConfirmation'])->middleware('permission:can_confirm_payment');;
            Route::post('/export', [InvoiceController::class, 'exportInvoice']);
        });

        Route::group(["prefix" => "configuration"], function () {
            Route::post('/save', [ConfigurationController::class, 'save'])->middleware('configuration');
            Route::get('/all', [ConfigurationController::class, 'getAllConfigs'])->middleware('permission:can_edit_system_configuration');
            Route::get('/{name}', [ConfigurationController::class, 'getConfig'])->middleware('permission:can_edit_system_configuration');
        });

        Route::group(["prefix" => "permission"], function () {
            Route::post('/give', [ConfigurationController::class, 'givePermission'])->middleware('permission:can_give_permission');
            Route::post('/revoke', [ConfigurationController::class, 'revokePermission'])->middleware('permission:can_revoke_permission');
            Route::get('/permissions', [ConfigurationController::class, 'allPermissions'])->middleware('permission:can_view_permission');
            Route::get('/staff_permissions/{staff_id}', [ConfigurationController::class, 'getStaffPermissions'])->middleware('permission:can_view_permission');
        });

        Route::group(["prefix" => "role"], function () {
            Route::post('/create', [ConfigurationController::class, 'createRole'])->middleware('permission:can_give_permission');
            Route::post('/delete', [ConfigurationController::class, 'deleteRole'])->middleware('permission:can_revoke_permission');
            Route::post('/assign_role_to_staff', [ConfigurationController::class, 'assignRole'])->middleware('permission:can_view_permission');
            Route::post('/unassign_role_from_staff', [ConfigurationController::class, 'removeRole'])->middleware('permission:can_view_permission');
            Route::post('/update', [ConfigurationController::class, 'updateRole'])->middleware('permission:can_view_permission');
            Route::get('/add_permission', [ConfigurationController::class, 'addPermission'])->middleware('permission:can_view_permission');
            Route::get('/remove_permission', [ConfigurationController::class, 'removePermission'])->middleware('permission:can_view_permission');
            Route::get('/get_role_permission', [ConfigurationController::class, 'getRolePermissions'])->middleware('permission:can_view_permission');
            Route::get('/roles', [ConfigurationController::class, 'allRoles'])->middleware('permission:can_view_permission');
            Route::get('/roles/{id?}', [ConfigurationController::class, 'getStaffRoles'])->middleware('permission:can_view_permission');
            Route::get('/office_roles', [ConfigurationController::class, 'rolesOfOffices'])->middleware('permission:can_view_permission');
        });


        Route::group(["prefix" => "level"], function () {
            Route::get('/', [CentralController::class, 'getLevels']);
            Route::post('/update', [CentralController::class, 'updateLevel']);
        });

        Route::get('/management/offices', [StaffController::class, 'managementOffice']);
        Route::post('/confirm_payment', [StaffController::class, 'confirmPayment'])->middleware('permission:can_confirm_payment');
        Route::post('/update_student_info', [StaffController::class, 'updateStudent'])->middleware('permission:"can_update_student_info"');
        Route::post('/update_school_info', [StaffController::class, 'updateSchoolInfo'])->middleware('permission:"can_edit_school_info"');

        //Route::post('/changing_student_programme', [AdmissionController::class, 'changeStudentProgramme'])->middleware('permission:can_change_student_programme');
    });
});


Route::prefix('open')->group(function () {
    Route::get('/students/{matric_number?}', [StudentController::class, 'getStudent']);

    Route::get('/programme_courses/{search?}', [ProgrammeController::class, 'programmeCoursesWithoutPaginate']);
    Route::get('/faculties/{search?}', [FacultyController::class, 'getFacultiesWithoutPaginate']);
    Route::get('/departments/{search?}', [DepartmentController::class, 'getDepartmentsWithoutPaginate']);
    Route::get('/courses/{search?}', [CourseController::class, 'getCoursesWithoutPaginate']);

    Route::get('/qualifications', [CentralController::class, 'getQualifications']);
    Route::get('/levels', [CentralController::class, 'getLevels']);
    Route::get('/subjects', [CentralController::class, 'getSubjects']);
    Route::get('/payment_categories', [CentralController::class, 'getPaymentCategory']);
    Route::get('/exam_types', [CentralController::class, 'getExamTypes']);
    Route::get('/certificate_types', [CentralController::class, 'getCertificateType']);
    Route::get('/admission_verification_status', [CentralController::class, 'getApplicantVerificationStatus']);
    Route::get('/settings/{name}', [ConfigurationController::class, 'getConfig']);

    Route::prefix('tenant')->group(function () {
        Route::patch('/save', [CentralController::class, 'saveTenant']);
        Route::get('/all', [CentralController::class, 'getAllTenants']);
        Route::get('/{id}', [CentralController::class, 'getTenantById']);
        Route::post('/payment_gateway/save', [CentralController::class, 'storeTenantPaymentGateways']);
        Route::patch('/payment_gateway/update', [CentralController::class, 'updateTenantPaymentGateways']);
        Route::get('/payment_gateways/all', [CentralController::class, 'getAllTenantPaymentGateways']);
        Route::get('/payment_gateway/{id}', [CentralController::class, 'saveTenantPaymentGatewayById']);
    });

    Route::get('/sessions', [CentralController::class, 'getSessions']);
    Route::get('/mode_of_entries', [CentralController::class, 'getModeOfEntries']);
    Route::get('/programmes/{id?}', [CentralController::class, 'programme']);
    Route::get('/invoice/{invoice_number}', [CentralController::class, 'getInvoice']);
    Route::get('/programme/{id} ', [ProgrammeController::class, 'getProgrammeById']);

    Route::post('/invoice-pdf', [PDFController::class, 'downloadInvoice']);
    Route::post('/invoice-pdf/download', [PDFController::class, 'downloadInvoice']);
    Route::post('/receipt-pdf', [PDFController::class, 'downloadPaymentReceipt']);
    Route::post('/slip-pdf', [PDFController::class, 'downloadSlip']);
    Route::post('/biodata-pdf', [PDFController::class, 'biodataSlip']);
    Route::post('/acknowledgement-pdf', [PDFController::class, 'acknowledgementSlip']);
    Route::post('/payments/pay', [CentralPaymentController::class, 'initiatePayment']);
    Route::post('/pdf/exam-card', [PDFController::class, 'examCard']);
    Route::post('/pdf/course-form', [PDFController::class, 'courseForm']);
    Route::post('/pdf/result-slip', [PDFController::class, 'resultSlip']);
    Route::post('/pdf/olevel-slip', [PDFController::class, 'olevelSlip']);
    Route::post('/programme_types/{search?}',  [CentralController::class, 'programmeTypeWithoutPaginate']);
    Route::get('/payment/{reference}', [CentralPaymentController::class, 'show']);
    Route::get('/configuration/{name?}', [CentralController::class, 'getConfiguration']);
});


//Route::post('applicant/create', [MenuController::class, 'createApplicantPortalMenu']);


Route::post('/total_paid_and_unpaid_amount_by_level_programme2', [DashboardController::class, 'totalPaidAndUnpaidAmountBylevelProgramme']);
Route::post('/total_paid_and_unpaid_amount_by_programme_type2', [DashboardController::class, 'totalPaidAndUnpaidAmountByProgrammeType']);
Route::post('/total_paid_and_unpaid_amount_by_level_programme_type2', [DashboardController::class, 'totalPaidAndUnpaidAmountBylevelProgrammeType']);
Route::post('/total_paid_and_unpaid_amount_by_progamme2', [DashboardController::class, 'totalPaidAndUnpaidAmountByProgramme']);
/* Route::post('/payment_reports', [DashboardController::class, 'paymentReport']);
Route::post('/admission', [DashboardController::class, 'admission']); */
