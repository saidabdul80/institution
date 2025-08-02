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
            "can_import_applicants" => 'Import applicants from Excel',
            "can_import_applicants" => 'Import applicants from Excel',
            "can_view_applicant" => 'Import applicants from Excel',
            'can_view_report_dashboard' => 'View Report Dashboard',
        ],
        
        'admission_management' => [
            'can_give_admission' => 'Give admission to applicants',
            'can_view_admission' => 'View admission information',
            'can_bulk_admission' => 'Perform bulk admission operations',
            'can_activate_student' => 'Activate admitted students',
            'can_view_payment' => 'View payment information',
            'can_manage_admission_batches' => 'Manage admission batches',
        ],
        
        'student_management' => [
            'can_view_student' => 'View student information',
            'can_create_student' => 'Create new students',
            'can_edit_student' => 'Edit student information',
            'can_delete_student' => 'Delete students',
            'can_promote_student' => 'Promote students to next level',
            'can_demote_student' => 'Demote students',
            'can_change_student_status' => 'Change student status',
            'can_view_student_results' => 'View student results',
            'can_generate_student_transcript' => 'Generate student transcripts',
        ],
        
        'academic_setup' => [
            'can_manage_faculties' => 'Manage faculties',
            'can_manage_departments' => 'Manage departments',
            'can_manage_programmes' => 'Manage programmes',
            'can_manage_courses' => 'Manage courses',
            'can_manage_levels' => 'Manage academic levels',
            'can_manage_sessions' => 'Manage academic sessions',
            'can_manage_course_categories' => 'Manage course categories',
            'can_manage_programme_courses' => 'Manage programme courses',
            'can_manage_grade_settings' => 'Manage grade settings',

            "can_edit_programme" => "Edit programme",
            "can_create_programme" => "Edit programme",
            "can_create_programme" => "Edit programme",
            "can_delete_programme" => "Edit programme",
            "can_delete_programme" => "Edit programme",
            "can_view_programme" => "Edit programme",
            "can_edit_faculty" => "Edit faculty",
            "can_create_faculty" => "Edit faculty",
            "can_create_faculty" => "Edit faculty",
            "can_delete_faculty" => "Edit faculty",
            "can_delete_faculty" => "Edit faculty",
            "can_edit_department" => "Edit department",
            "can_create_department" => "Edit department",
            "can_create_department" => "Edit department",
            "can_delete_department" => "Edit department",
            "can_delete_department" => "Edit department",
            "configuration" => "Edit system configuration",
            "can_edit_system_configuration" => "Edit system configuration",
            "can_edit_system_configuration" => "Edit system configuration",
        ],
        
        'result_management' => [
            'can_input_results' => 'Input student results',
            'can_edit_results' => 'Edit student results',
            'can_approve_results' => 'Approve student results',
            'can_compute_results' => 'Compute student results',
            'can_view_results' => 'View student results',
            'can_generate_transcripts' => 'Generate transcripts',
            'can_manage_result_templates' => 'Manage result templates',
        ],
        
        'staff_management' => [
            'can_view_staff' => 'View staff information',
            'can_create_staff' => 'Create new staff',
            'can_edit_staff' => 'Edit staff information',
            'can_delete_staff' => 'Delete staff',
            'can_assign_courses_to_staff' => 'Assign courses to staff',
            'can_manage_staff_permissions' => 'Manage staff permissions',
            'can_view_staff_performance' => 'View staff performance',
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
        ],
        
        'payment_management' => [
            'can_view_payments' => 'View payment information',
            'can_process_payments' => 'Process payments',
            'can_refund_payments' => 'Process payment refunds',
            'can_generate_payment_reports' => 'Generate payment reports',
            'can_manage_payment_methods' => 'Manage payment methods',
        ],
        
        'reporting' => [
            'can_generate_reports' => 'Generate system reports',
            'can_view_analytics' => 'View system analytics',
            'can_export_data' => 'Export system data',
            'can_view_statistics' => 'View system statistics',
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
                
                // Student Management
                'can_view_student',
                'can_create_student',
                'can_edit_student',
                'can_promote_student',
                'can_change_student_status',
                'can_view_student_results',
                
                // Academic Setup
                'can_manage_faculties',
                'can_manage_departments',
                'can_manage_programmes',
                'can_manage_courses',
                'can_manage_levels',
                'can_manage_sessions',
                
                // Result Management
                'can_input_results',
                'can_edit_results',
                'can_approve_results',
                'can_compute_results',
                'can_view_results',
                
                // Staff Management
                'can_view_staff',
                'can_create_staff',
                'can_edit_staff',
                'can_assign_courses_to_staff',
                
                // Reporting
                'can_generate_reports',
                'can_view_analytics',
                'can_export_data',
            ]
        ],
        
        'Registrar' => [
            'description' => 'Student records and academic management',
            'permissions' => [
                // Student Management
                'can_view_student',
                'can_create_student',
                'can_edit_student',
                'can_promote_student',
                'can_change_student_status',
                'can_generate_student_transcript',
                
                // Admission Management
                'can_give_admission',
                'can_view_admission',
                'can_activate_student',
                'can_view_payment',
                
                // Academic Setup
                'can_manage_sessions',
                'can_manage_levels',
                
                // Reporting
                'can_generate_reports',
                'can_view_analytics',
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
            ]
        ],
        
        'Academic Officer' => [
            'description' => 'Academic setup and course management',
            'permissions' => [
                // Academic Setup
                'can_manage_faculties',
                'can_manage_departments',
                'can_manage_programmes',
                'can_manage_courses',
                'can_manage_course_categories',
                'can_manage_programme_courses',
                
                // Staff Management
                'can_view_staff',
                'can_assign_courses_to_staff',
                
                // Student Management
                'can_view_student',
                'can_view_student_results',
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
                'can_view_student_results',
            ]
        ],
        
        'Accountant' => [
            'description' => 'Financial and payment management',
            'permissions' => [
                // Payment Management
                'can_view_payments',
                'can_process_payments',
                'can_generate_payment_reports',
                
                // Applicant Management
                'can_view_applicant',
                'can_view_payment',
                
                // Student Management
                'can_view_student',
                
                // Reporting
                'can_generate_reports',
                'can_view_analytics',
            ]
        ]
    ]
];
