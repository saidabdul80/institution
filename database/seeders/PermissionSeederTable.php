<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeederTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $arrayOfPermissionNames = [            
            "can_create_course",
            "can_edit_course",
            "can_delete_course",
            "can_view_course",
            "can_create_faculty",
            "can_edit_faculty",
            "can_delete_faculty",
            "can_view_faculty",
            "can_create_programme",
            "can_edit_programme",
            "can_delete_programme",
            "can_view_programme",
            "can_create_staff",
            "can_edit_staff",
            "can_delete_staff",
            "can_view_staff", 
            "can_assign_role_to_staff",
            "can_deassign_role_to_staff",
            "can_create_department",
            "can_edit_department",
            "can_delete_department",
            "can_view_department",
            "can_assign_course",
            "can_unassign_course",
            "can_view_programme_courses",
            "can_give_admission",
            "can_view_payment",
            "can_set_payment",
            "can_activate_student",
            "can_view_applicants",
            "can_create_campus",
            "can_edit_campus",
            "can_delete_campus",
            "can_view_campus",
            "can_create_block",
            "can_edit_block",
            "can_delete_block",
            "can_view_block",
            "can_create_room",
            "can_edit_room",
            "can_delete_room",
            "can_view_room",
            "can_revoke_allocation",
            "can_allocate_room",            
            "can_view_reports",
            "can_create_settings",
            "can_create_rules",
            "can_deallocate_course",
            "can_upload_result",
            "can_compute_result",
            "can_print_result",
            "can_view_top_list",
            "can_view_graduation_list",
            "can_view_id_card",
            "can_edit_system_configuration",
            "can_give_permission",
            "can_revoke_permission",
            "can_view_permission",
            "can_register_courses",
            "can_publish_results",            
            "can_view_admission_dashboard",            
            "can_set_applicant_qualification_status" ,
            "can_view_students",                                                                
            "can_view_admission_batch",
            "can_create_admission_batch",            
            "can_promote_students",    
            "can_edit_school_info", 
            "can_update_student_info",
            "can_change_student_programme",
                        
            "can_view_qualified",
            "can_view_admitted",
            "can_view_not_qualified",            

            "can_create_edit_programme_type",
            "can_delete_programme_type",
            "can_view_programme_type",
            "can_create_invoice_type",
            "can_edit_invoice_type",
            "can_delete_invoice_type",
            "can_update_invoice_type_status",
            "can_view_invoice_type",
            "can_confirm_payment",
            "can_view_management_offices",       
            "can_create_session",
            "can_update_session",
            "can_delete_session",
            "can_reverse_students_promotion",

            "can_view_report_dashboard",
            "can_view_finance_dashboard",
            "can_view_dashboard",

            "can_set_current_session",
            "can_set_current_admission_batch",
            "can_set_admission_letter_template",            
            "can_set_admission_verification_slip_template",
            "can_set_current_application_session",
            "can_set_entrance_exam_schedule",
            "can_set_grad_min_cgpa",
            "can_set_grad_level_id",
            "can_set_grad_min_credit_units",
            "can_set_admission_notification_letter_template",
            "can_set_admission_acknowledgement_letter_template",
            "can_set_application_screening_schedule",
            "can_set_application_notice",
            "can_set_students_notice",
            "can_set_show_photo_on_receipt",
            "can_set_show_photo_on_invoice",
            "can_set_show_photo_on_transaction_slip",
            "can_set_show_photo_on_course_reg",
            "can_set_show_photo_on_exam_card",
            "can_set_show_photo_on_biodata_slip",
            "can_set_application_number_format",
            "can_set_matric_number_format",
            "can_set_allow_course_registration",
            "can_set_school_state_of_origin",
            "can_set_allow_payments",
            "can_set_current_semester",
            "can_set_tp_course_setting",

            'can_view_application_dashboard_payment',
            'can_view_application_dashboard_applicants',
            'can_view_application_dashboard_payment_invoices',
            'can_view_student_dashboard_payment',
            'can_view_student_dashboard_applicants',
            'can_view_student_dashboard_payment_invoices',
            'can_view_settings',
            'can_get_applicant_admission_status',
            'can_get_invoice_verification',
            'can_view_payment_dashboard',            
            'can_view_student_info_report',
            'can_view_financial_report',   
            'can_view_invoice_details',                     
            'can_view_wallet_dashboard',
            'can_view_staff_courses',
            'can_export_general_scores',
            'can_reset_staff_password'
        ];

        /* 
            Role::insert(
                [
                    ['name' => 'v_c', 'guard_name' =>  "api-staff"],
                    ['name' => 'faculty', 'guard_name' =>  "api-staff"],
                    ['name' => 'dean', 'guard_name' =>  "api-staff"],
                    ['name' => 'hod', 'guard_name' =>  "api-staff"],
                    ['name' => 'exam', 'guard_name' =>  "api-staff"],
                    ['name' => 'library', 'guard_name' =>  "api-staff"],
                    ['name' => 'bursar', 'guard_name' =>  "api-staff"],
                    ['name' => 'security', 'guard_name' =>  "api-staff"]
            ]); 
        */

        $permissions = collect($arrayOfPermissionNames)->map(function ($permission) {
            return ['name' => $permission, 'guard_name' =>  "api-staff"];
        });
        Permission::insert($permissions->toArray());

        $admission_manager_permissions = Permission::whereIn('name',['can_give_admission', 'can_activate_student', 'can_view_applicants'])->get();        
        $role = Role::create(['name' => 'admission_manager', 'guard_name' =>  "api-staff"])
            ->givePermissionTo($admission_manager_permissions );
        
        
        $role = Role::create(['name' => 'super-admin', 'guard_name' =>  "api-staff"]);
        $role->givePermissionTo(Permission::all());
        $staff = Staff::find(1);
        if($staff){
            $staff->givePermissionTo(Permission::all());
            Staff::find(1)->givePermissionTo(['can_give_admission', 'can_activate_student', 'can_view_applicants']);

        }

    }
}
