<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the permissions and roles for the application.
    | You can add new permissions here and run the command to update them.
    |
    */

    'permissions' => [
        'applicant_management' => [
            'can_view_applicant' => 'View applicant information',
            'can_create_applicant' => 'Create new applicants',
            'can_edit_applicant' => 'Edit applicant information',
            'can_delete_applicant' => 'Delete applicants',
            'can_import_applicants' => 'Import applicants from Excel',
            'can_export_applicants' => 'Export applicant data',
            'can_set_applicant_qualification_status' => 'Set applicant qualification status',
            'can_view_applicant_documents' => 'View applicant documents',
            'can_approve_applicant_documents' => 'Approve applicant documents',
            'can_view_report_dashboard' => 'View Report Dashboard',
        ],

        'admission_management' => [
            'can_give_admission' => 'Give admission to applicants',
            'can_view_admission' => 'View admission information',
            'can_bulk_admission' => 'Perform bulk admission operations',
            'can_activate_student' => 'Activate admitted students',
            'can_view_payment' => 'View payment information',
            'can_manage_admission_batches' => 'Manage admission batches',
            'can_create_admission_batch' => 'Create admission batches',
            'can_view_admission_batch' => 'View admission batches',
            'can_view_admission_dashboard' => 'View admission dashboard',
            'can_change_student_programme' => 'Change student programme',
        ],

        'student_management' => [
            'can_view_student' => 'View student information',
            'can_view_students' => 'View students list',
            'can_create_student' => 'Create new students',
            'can_edit_student' => 'Edit student information',
            'can_delete_student' => 'Delete students',
            'can_promote_student' => 'Promote students to next level',
            'can_promote_students' => 'Promote multiple students to next level',
            'can_reverse_students_promotion' => 'Reverse student promotion',
            'can_demote_student' => 'Demote students',
            'can_change_student_status' => 'Change student status',
            'can_view_student_results' => 'View student results',
            'can_view_results' => 'View results',
            'can_generate_student_transcript' => 'Generate student transcripts',
            'can_generate_transcript' => 'Generate transcripts',
        ],

        'academic_setup' => [
            'can_manage_faculties' => 'Manage faculties',
            'can_create_faculty' => 'Create faculty',
            'can_edit_faculty' => 'Edit faculty',
            'can_delete_faculty' => 'Delete faculty',
            'can_view_faculty' => 'View faculty',

            'can_manage_departments' => 'Manage departments',
            'can_create_department' => 'Create department',
            'can_edit_department' => 'Edit department',
            'can_delete_department' => 'Delete department',
            'can_view_department' => 'View department',

            'can_manage_programmes' => 'Manage programmes',
            'can_create_programme' => 'Create programme',
            'can_edit_programme' => 'Edit programme',
            'can_delete_programme' => 'Delete programme',
            'can_view_programme' => 'View programme',

            'can_manage_courses' => 'Manage courses',
            'can_create_course' => 'Create course',
            'can_edit_course' => 'Edit course',
            'can_delete_course' => 'Delete course',
            'can_view_course' => 'View course',

            'can_manage_levels' => 'Manage academic levels',
            'can_create_level' => 'Create level',
            'can_edit_level' => 'Edit level',
            'can_delete_level' => 'Delete level',
            'can_view_level' => 'View level',

            'can_manage_sessions' => 'Manage academic sessions',
            'can_create_session' => 'Create session',
            'can_update_session' => 'Update session',
            'can_delete_session' => 'Delete session',
            'can_view_session' => 'View session',

            'can_manage_course_categories' => 'Manage course categories',
            'can_manage_programme_courses' => 'Manage programme courses',
            'can_view_programme_courses' => 'View programme courses',
            'can_manage_grade_settings' => 'Manage grade settings',

            'can_assign_course' => 'Assign courses',
            'can_unassign_course' => 'Unassign courses',

            'can_create_edit_programme_type' => 'Create/Edit programme type',
            'can_delete_programme_type' => 'Delete programme type',
            'can_view_programme_type' => 'View programme type',
        ],

        'result_management' => [
            'can_input_results' => 'Input student results',
            'can_edit_results' => 'Edit student results',
            'can_approve_results' => 'Approve student results',
            'can_compute_results' => 'Compute student results',
            'can_view_results' => 'View student results',
            'can_generate_transcripts' => 'Generate transcripts',
            'can_manage_result_templates' => 'Manage result templates',
            'can_view_results' => 'View Result',
        ],

        'staff_management' => [
            'can_view_staff' => 'View staff information',
            'can_create_staff' => 'Create new staff',
            'can_edit_staff' => 'Edit staff information',
            'can_delete_staff' => 'Delete staff',
            'can_assign_courses_to_staff' => 'Assign courses to staff',
            'can_manage_staff_permissions' => 'Manage staff permissions',
            'can_view_staff_performance' => 'View staff performance',
            'can_assign_role_to_staff' => 'Assign role to staff',
            'can_deassign_role_to_staff' => 'Deassign role from staff',
            'can_view_courses' => 'View courses',
            'can_view_staff_allocations' => 'View staff course allocations',
            'can_manage_staff_allocations' => 'Manage staff course allocations',
        ],

        'system_management' => [
            'can_manage_permissions' => 'Manage system permissions',
            'can_manage_roles' => 'Manage user roles',
            'can_manage_system_settings' => 'Manage system settings',
            'can_view_system_logs' => 'View system logs',
            'can_backup_system' => 'Backup system data',
            'can_manage_invoice_types' => 'Manage invoice types',
            'can_manage_signatories' => 'Manage signatories',
            'can_manage_controls' => 'Manage system controls',
            'can_edit_system_configuration' => 'Edit system configuration',
            'configuration' => 'System configuration access',
            'can_give_permission' => 'Give permissions to users',
            'can_revoke_permission' => 'Revoke permissions from users',
            'can_view_permission' => 'View permissions',
        ],

        'payment_management' => [
            'can_view_payments' => 'View payment information',
            'can_process_payments' => 'Process payments',
            'can_refund_payments' => 'Process payment refunds',
            'can_generate_payment_reports' => 'Generate payment reports',
            'can_manage_payment_methods' => 'Manage payment methods',
            'can_confirm_payment' => 'Confirm payments manually',
            'can_view_payment' => 'View payment details',
        ],

        'reporting' => [
            'can_generate_reports' => 'Generate system reports',
            'can_view_analytics' => 'View system analytics',
            'can_export_data' => 'Export system data',
            'can_view_statistics' => 'View system statistics',
            'can_view_report_dashboard' => 'View report dashboard',
            'can_view_finance_dashboard' => 'View finance dashboard',
        ],

        'invoice_management' => [
            'can_create_invoice_type' => 'Create invoice type',
            'can_edit_invoice_type' => 'Edit invoice type',
            'can_delete_invoice_type' => 'Delete invoice type',
            'can_update_invoice_type_status' => 'Update invoice type status',
            'can_view_invoice_type' => 'View invoice type',
        ],

        'school_information' => [
            'can_update_student_info' => 'Update student information',
            'can_edit_school_info' => 'Edit school information',
        ]
    ],

    'roles' => [
        'Super Admin' => [
            'description' => 'Full system access with all permissions',
            'permissions' => 'all' // Special value meaning all permissions
        ],

        'Admin' => [
            'description' => 'Administrative access with most permissions',
            'permissions' => [
                // Applicant Management
                'can_view_applicant',
                'can_create_applicant',
                'can_edit_applicant',
                'can_import_applicants',
                'can_export_applicants',
                'can_set_applicant_qualification_status',
                'can_view_applicant_documents',
                'can_approve_applicant_documents',

                // Admission Management
                'can_give_admission',
                'can_view_admission',
                'can_bulk_admission',
                'can_activate_student',
                'can_view_payment',
                'can_manage_admission_batches',
                'can_create_admission_batch',
                'can_view_admission_batch',
                'can_view_admission_dashboard',
                'can_change_student_programme',

                // Student Management
                'can_view_student',
                'can_view_students',
                'can_create_student',
                'can_edit_student',
                'can_promote_student',
                'can_promote_students',
                'can_reverse_students_promotion',
                'can_change_student_status',
                'can_view_student_results',
                'can_view_results',
                'can_generate_transcript',

                // Academic Setup
                'can_manage_faculties',
                'can_create_faculty',
                'can_edit_faculty',
                'can_delete_faculty',
                'can_manage_departments',
                'can_create_department',
                'can_edit_department',
                'can_delete_department',
                'can_manage_programmes',
                'can_create_programme',
                'can_edit_programme',
                'can_delete_programme',
                'can_view_programme',
                'can_manage_courses',
                'can_create_course',
                'can_view_course',
                'can_manage_levels',
                'can_manage_sessions',
                'can_create_session',
                'can_update_session',
                'can_assign_course',
                'can_unassign_course',
                'can_view_programme_courses',

                // Result Management
                'can_input_results',
                'can_edit_results',
                'can_approve_results',
                'can_compute_results',
                'can_view_results',
                'can_manage_grade_settings',

                // Staff Management
                'can_view_staff',
                'can_create_staff',
                'can_edit_staff',
                'can_delete_staff',
                'can_assign_courses_to_staff',
                'can_assign_role_to_staff',
                'can_deassign_role_to_staff',
                'can_view_courses',
                'can_view_staff_allocations',
                'can_manage_staff_allocations',

                // Payment Management
                'can_confirm_payment',
                'can_view_payment',

                // System Management
                'can_manage_system_settings',
                'can_edit_system_configuration',
                'can_give_permission',
                'can_revoke_permission',
                'can_view_permission',

                // Invoice Management
                'can_create_invoice_type',
                'can_edit_invoice_type',
                'can_delete_invoice_type',
                'can_update_invoice_type_status',
                'can_view_invoice_type',

                // School Information
                'can_update_student_info',
                'can_edit_school_info',

                // Reporting
                'can_generate_reports',
                'can_view_analytics',
                'can_export_data',
                'can_view_report_dashboard',
                'can_view_finance_dashboard',
            ]
        ],

        'Registrar' => [
            'description' => 'Student records and academic management',
            'permissions' => [
                // Student Management
                'can_view_student',
                'can_view_students',
                'can_create_student',
                'can_edit_student',
                'can_promote_student',
                'can_promote_students',
                'can_reverse_students_promotion',
                'can_change_student_status',
                'can_generate_student_transcript',
                'can_generate_transcript',
                'can_view_results',

                // Admission Management
                'can_give_admission',
                'can_view_admission',
                'can_activate_student',
                'can_view_payment',
                'can_view_admission_dashboard',
                'can_change_student_programme',

                // Academic Setup
                'can_manage_sessions',
                'can_create_session',
                'can_update_session',
                'can_manage_levels',
                'can_view_programme',

                // School Information
                'can_update_student_info',

                // Reporting
                'can_generate_reports',
                'can_view_analytics',
                'can_view_report_dashboard',
            ]
        ],

        'Admission Officer' => [
            'description' => 'Applicant and admission management',
            'permissions' => [
                // Applicant Management
                'can_view_applicant',
                'can_edit_applicant',
                'can_import_applicants',
                'can_export_applicants',
                'can_set_applicant_qualification_status',
                'can_view_applicant_documents',
                'can_approve_applicant_documents',

                // Admission Management
                'can_give_admission',
                'can_view_admission',
                'can_bulk_admission',
                'can_view_payment',
                'can_manage_admission_batches',
                'can_create_admission_batch',
                'can_view_admission_batch',
                'can_view_admission_dashboard',
                'can_activate_student',
                'can_change_student_programme',

                // Student Management
                'can_view_student',
                'can_view_students',

                // Reporting
                'can_view_report_dashboard',
            ]
        ],

        'Academic Officer' => [
            'description' => 'Academic setup and course management',
            'permissions' => [
                // Academic Setup
                'can_manage_faculties',
                'can_create_faculty',
                'can_edit_faculty',
                'can_delete_faculty',
                'can_manage_departments',
                'can_create_department',
                'can_edit_department',
                'can_delete_department',
                'can_manage_programmes',
                'can_create_programme',
                'can_edit_programme',
                'can_delete_programme',
                'can_view_programme',
                'can_manage_courses',
                'can_create_course',
                'can_view_course',
                'can_manage_course_categories',
                'can_manage_programme_courses',
                'can_view_programme_courses',
                'can_assign_course',
                'can_unassign_course',
                'can_manage_sessions',
                'can_create_session',
                'can_update_session',
                'can_manage_levels',
                'can_create_edit_programme_type',
                'can_view_programme_type',

                // Staff Management
                'can_view_staff',
                'can_assign_courses_to_staff',
                'can_view_courses',
                'can_view_staff_allocations',
                'can_manage_staff_allocations',

                // Student Management
                'can_view_student',
                'can_view_students',
                'can_view_student_results',
                'can_view_results',
            ]
        ],

        'Lecturer' => [
            'description' => 'Teaching staff with result input access',
            'permissions' => [
                // Result Management
                'can_input_results',
                'can_view_results',

                // Student Management
                'can_view_student',
                'can_view_students',
                'can_view_student_results',

                // Academic Setup (limited)
                'can_view_course',
                'can_view_programme',
            ]
        ],

        'Accountant' => [
            'description' => 'Financial and payment management',
            'permissions' => [
                // Payment Management
                'can_view_payments',
                'can_process_payments',
                'can_generate_payment_reports',
                'can_confirm_payment',
                'can_view_payment',

                // Invoice Management
                'can_create_invoice_type',
                'can_edit_invoice_type',
                'can_view_invoice_type',
                'can_update_invoice_type_status',

                // Applicant Management
                'can_view_applicant',

                // Student Management
                'can_view_student',
                'can_view_students',

                // System Management
                'can_manage_system_settings',

                // Applicant Management
                'can_view_applicant',
                'can_view_payment',

                // Student Management
                'can_view_student',

                // Reporting
                'can_generate_reports',
                'can_view_analytics',
                'can_view_finance_dashboard',
                'can_view_report_dashboard',
            ]
        ]
    ]
];
