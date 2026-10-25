<?php

return [

    'name' => env('HEALTHHUB_NAME', 'WPU HealthHub'),

    'tagline' => env('HEALTHHUB_TAGLINE', 'Your unified gateway to campus wellness.'),

    'legacy_name' => 'HSU-EMR',

    /*
    | Explicitly authorized staff/admin email addresses (not open @wpu.edu.ph domain).
    */
    'authorized_staff_emails' => array_values(array_filter(array_map(
        'strtolower',
        array_map('trim', explode(',', env('HEALTHHUB_AUTHORIZED_EMAILS', 'hso@wpu.edu.ph')))
    ))),

    'clinical_roles' => [
        'super_admin',
        'admin',
        'physician',
        'dentist',
        'nurse',
        'staff',
    ],

    /*
    | Role-based workspace pages, record tabs, calendar routes, and post-login landing.
    */
    /*
    | Consent & Refusal module: hub page consent_refusal_module; sub-pages secure_consent,
    | view_consent, consent_refusal, view_refusal share the same clinical role access.
    */
    'workspace' => [
        'pages' => [
            'dashboard' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse', 'staff'],
            'certificates_referrals' => ['super_admin', 'admin', 'physician'],
            'health_dental_records' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse', 'staff'],
            'view_record' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse', 'staff'],
            'consent_refusal_module' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse'],
            'secure_consent' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse'],
            'view_consent' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse'],
            'consent_refusal' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse'],
            'view_refusal' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse'],
            'referrals_hub' => ['super_admin', 'admin', 'physician'],
            'dispensary' => ['super_admin', 'admin', 'physician', 'nurse', 'staff'],
            'reports' => ['super_admin', 'admin', 'physician', 'nurse'],
            'settings' => ['super_admin', 'admin'],
            'admin_management' => ['super_admin', 'admin'],
            'user_logs' => ['super_admin', 'admin'],
            'backup' => ['super_admin', 'admin'],
        ],
        'records_tabs' => [
            'health' => ['super_admin', 'admin', 'physician', 'nurse', 'staff'],
            'dental' => ['super_admin', 'admin', 'physician', 'dentist'],
        ],
        'calendar_routes' => [
            'index' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse'],
            'appointments' => ['super_admin', 'admin', 'physician', 'dentist', 'nurse', 'staff'],
            'appointments.store' => ['super_admin', 'admin'],
            'physicians' => ['super_admin', 'admin'],
            'physicians.store' => ['super_admin', 'admin'],
            'portal-users' => ['super_admin', 'admin'],
            'portal-users.show' => ['super_admin', 'admin'],
            'portal-users.edit' => ['super_admin', 'admin'],
            'portal-users.update' => ['super_admin', 'admin'],
            'portal-users.photo' => ['super_admin', 'admin'],
            'portal-users.password' => ['super_admin', 'admin'],
            'reports' => ['super_admin', 'admin'],
            'settings' => ['super_admin', 'admin'],
            'settings.update' => ['super_admin', 'admin'],
        ],
        'landing' => [
            'physician' => ['page' => 'dashboard', 'query' => []],
            'dentist' => ['page' => 'health_dental_records', 'query' => ['records_tab' => 'dental']],
            'nurse' => ['page' => 'dashboard', 'query' => []],
            'staff' => ['page' => 'dashboard', 'query' => []],
            'admin' => ['page' => 'dashboard', 'query' => []],
            'super_admin' => ['page' => 'dashboard', 'query' => []],
        ],
    ],

    'transactions' => [
        'APE',
        'Leave of Absence (LOA)',
        'Pre-employment',
        'Pre-enrollment',
        'Medical Clearance',
        'Medical Consultation',
        'Return to School',
        'Referral',
    ],

    'medical_case_types' => [
        'COMMUNICABLE' => [
            'Respiratory',
            'Skin',
            'Vector-borne',
            'Sexually transmitted infection',
        ],
        'NON-COMMUNICABLE' => [
            'Neurologic',
            'Psychiatric',
            'Respiratory',
            'Cardiovascular',
            'Hematologic',
            'Oncologic',
            'Urologic',
            'Obstetric',
            'Gynecologic',
            'Dermatologic',
            'Gastrointestinal',
            'Surgical',
            'Work/School related',
            'Non-work/school related',
            'Others: specify',
        ],
    ],

    'dental_case_types' => [
        'Dental',
        'Others: specify',
    ],

    'certificate_purposes' => [
        'Pre-employment',
        'APE',
        'Medical Clearance',
        'Return to School',
        'LOA',
        'Pre-enrollment',
        'Sickness',
    ],

    'interagency_units' => [
        'Guidance Unit',
        'Admission',
        'HRMO',
        'ASILS',
        'BELS',
    ],

    'external_agencies' => [
        'Roots of Health',
        'Narra Mental Health Clinic',
        'City Health Office',
        'Aborlan Medicare Hospital',
    ],

];
