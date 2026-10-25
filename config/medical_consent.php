<?php

return [

    'form_reference' => 'WPU-QSF-OSAS-HSO-23 Rev. 00 (08.15.25)',

    'template_version' => 'WPU-QSF-OSAS-HSO-23 Rev. 00 (08.15.25)',

    'advising_physician' => 'MICAELA T. BAGALANON-LABUTOY, MD, OHP, SHC',

    'office_title' => 'HEALTH SERVICES OFFICE',

    'form_title' => 'MEDICAL CONSENT FORM',

    'university' => [
        'republic' => 'Republic of the Philippines',
        'name' => 'Western Philippines University',
        'tagline' => 'A STRONG PARTNER FOR SUSTAINABLE DEVELOPMENT',
    ],

    /** Official Word template used for DOCX/PDF output (copy of docs/CONSENT.docx). */
    'docx_template' => 'assets/templates/MEDICAL_CONSENT_OFFICIAL.docx',

    /*
    | Clinical transactions that require a completed medical consent before saving a visit.
    */
    'transactions_requiring_consent' => [
        'APE',
        'Leave of Absence (LOA)',
        'Pre-employment',
        'Pre-enrollment',
        'Medical Clearance',
        'Medical Consultation',
        'Return to School',
        'Referral',
        'Dental',
    ],

    'transactions_exempt_from_consent' => [
        // HSU may add policy exceptions here (e.g. emergency triage-only visits).
    ],

    'dental_always_requires_consent' => true,

    'require_consent_when_treatment_present' => true,

    'consent_bypass_roles' => ['super_admin', 'physician'],

];
