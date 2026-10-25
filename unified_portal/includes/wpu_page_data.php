<?php
/**
 * Page-aware data loader — loads only the queries each admin page needs.
 * Preserves all existing variable names used by admin.php templates.
 */

require_once __DIR__.'/wpu_reference_data.php';
require_once __DIR__.'/wpu_security.php';

$enabled = 0;
$timeout = 300000;
$certificate_code = '';
$referral_code = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'certificates';
if (! in_array($active_tab, ['certificates', 'referrals'], true)) {
    $active_tab = 'certificates';
}
$records_tab = isset($_GET['records_tab']) ? (string) $_GET['records_tab'] : '';
if (! in_array($records_tab, ['dental', 'health'], true)) {
    $records_tab = '';
}
$cert_page = 1;
$ref_page = 1;
$cert_search = '';
$ref_search = '';
$search = '';
$cert_total = 0;
$ref_total = 0;
$cert_total_pages = 0;
$ref_total_pages = 0;
$dental_page = 1;
$dental_search = '';
$search_records = '';
$dental_total = 0;
$dental_total_pages = 0;
$health_page = 1;
$health_search = '';
$health_total = 0;
$health_total_pages = 0;
$certificates_result = null;
$referrals_result = null;
$dental_result = null;
$health_result = null;
$patient_types_result = null;
$departments_result = null;
$case_types_result = null;
$wpu_patient_types = [];
$wpu_departments = [];
$wpu_case_types = [];
$wpu_current_staff = null;

if (! $logged_in) {
    return;
}

require_once __DIR__.'/../config/connect.php';

if (! ($conn instanceof mysqli)) {
    return;
}

require_once __DIR__.'/wpu_healthhub.php';
$records_role = wpu_healthhub_admin_role($pdo);
if ($records_tab === '') {
    $records_tab = wpu_healthhub_default_records_tab($records_role);
}
if (! wpu_healthhub_can_access_records_tab($records_role, $records_tab)) {
    $records_tab = wpu_healthhub_default_records_tab($records_role);
    if (! wpu_healthhub_can_access_records_tab($records_role, $records_tab)) {
        $records_tab = wpu_healthhub_can_access_records_tab($records_role, 'dental') ? 'dental' : 'health';
    }
}

$autoLock = wpu_get_auto_lock_settings($pdo);
$enabled = $autoLock['enabled'];
$timeout = $autoLock['timeout'];

$codes = wpu_get_certificate_codes($pdo);
$certificate_code = $codes['certificate_code'];
$referral_code = $codes['referral_code'];

$wpu_patient_types = wpu_get_patient_types($pdo);
$wpu_departments = wpu_get_departments($pdo);
$wpu_case_types = wpu_get_case_types($pdo);

if ($page === 'settings') {
    $wpu_current_staff = wpu_get_staff_signature($pdo);
}

$certPages = ['dashboard', 'certificates', 'referrals', 'certificates_referrals'];
$recordPages = ['dashboard', 'dental_records', 'health_records', 'health_dental_records', 'view_record'];
$needsCertData = in_array($page, $certPages, true);
$needsRecordData = in_array($page, $recordPages, true);

if ($needsCertData) {
    $cert_page = max(1, isset($_GET['cert_page']) ? (int) $_GET['cert_page'] : 1);
    $ref_page = max(1, isset($_GET['ref_page']) ? (int) $_GET['ref_page'] : 1);
    $cert_search = isset($_GET['cert_search']) ? wpu_sanitize_search_term((string) $_GET['cert_search']) : '';
    $ref_search = isset($_GET['ref_search']) ? wpu_sanitize_search_term((string) $_GET['ref_search']) : '';
    $search = isset($_GET['search']) ? wpu_sanitize_search_term((string) $_GET['search']) : '';
    if ($active_tab === 'certificates' && $cert_search === '' && $search !== '') {
        $cert_search = $search;
    }
    if ($active_tab === 'referrals' && $ref_search === '' && $search !== '') {
        $ref_search = $search;
    }

    if ($page === 'dashboard') {
        $certCountResult = $conn->query('SELECT COUNT(*) AS total FROM medical_certificates');
        $cert_total = $certCountResult ? (int) $certCountResult->fetch_assoc()['total'] : 0;

        $refCountResult = $conn->query('SELECT COUNT(*) AS total FROM referrals');
        $ref_total = $refCountResult ? (int) $refCountResult->fetch_assoc()['total'] : 0;

        $certificates_result = $conn->query(
            'SELECT id, name, mc_no, receipt_no, created_at, examination_date
             FROM medical_certificates
             ORDER BY created_at DESC
             LIMIT 5'
        );
        $referrals_result = $conn->query(
            'SELECT id, patient_name, hospital_clinic, created_at
             FROM referrals
             ORDER BY created_at DESC
             LIMIT 5'
        );
    } else {
        $certLike = '%'.$cert_search.'%';
        $certCountStmt = $conn->prepare('SELECT COUNT(*) AS total FROM medical_certificates WHERE name LIKE ?');
        $certCountStmt->bind_param('s', $certLike);
        $certCountStmt->execute();
        $cert_count_result = $certCountStmt->get_result();
        $cert_total = $cert_count_result ? (int) $cert_count_result->fetch_assoc()['total'] : 0;
        $cert_total_pages = $cert_total > 0 ? (int) ceil($cert_total / $cert_ref_items_per_page) : 0;
        if ($cert_total_pages > 0) {
            $cert_page = min($cert_page, $cert_total_pages);
        }
        $cert_offset = ($cert_page - 1) * $cert_ref_items_per_page;

        $certListStmt = $conn->prepare('SELECT * FROM medical_certificates WHERE name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $certListStmt->bind_param('sii', $certLike, $cert_ref_items_per_page, $cert_offset);
        $certListStmt->execute();
        $certificates_result = $certListStmt->get_result();

        $refLike = '%'.$ref_search.'%';
        $refCountStmt = $conn->prepare('SELECT COUNT(*) AS total FROM referrals WHERE patient_name LIKE ?');
        $refCountStmt->bind_param('s', $refLike);
        $refCountStmt->execute();
        $ref_count_result = $refCountStmt->get_result();
        $ref_total = $ref_count_result ? (int) $ref_count_result->fetch_assoc()['total'] : 0;
        $ref_total_pages = $ref_total > 0 ? (int) ceil($ref_total / $cert_ref_items_per_page) : 0;
        if ($ref_total_pages > 0) {
            $ref_page = min($ref_page, $ref_total_pages);
        }
        $ref_offset = ($ref_page - 1) * $cert_ref_items_per_page;

        $refListStmt = $conn->prepare('SELECT * FROM referrals WHERE patient_name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $refListStmt->bind_param('sii', $refLike, $cert_ref_items_per_page, $ref_offset);
        $refListStmt->execute();
        $referrals_result = $refListStmt->get_result();
    }
}

if ($needsRecordData) {
    try {
    $dental_page = max(1, isset($_GET['dental_page']) ? (int) $_GET['dental_page'] : 1);
    $dental_search = isset($_GET['dental_search']) ? wpu_sanitize_search_term((string) $_GET['dental_search']) : '';
    $search_records = isset($_GET['search']) ? wpu_sanitize_search_term((string) $_GET['search']) : '';
    if ($records_tab === 'dental' && $dental_search === '' && $search_records !== '') {
        $dental_search = $search_records;
    }

    $health_page = max(1, isset($_GET['health_page']) ? (int) $_GET['health_page'] : 1);
    $health_search = isset($_GET['health_search']) ? wpu_sanitize_search_term((string) $_GET['health_search']) : '';
    if ($records_tab === 'health' && $health_search === '' && $search_records !== '') {
        $health_search = $search_records;
    }

    $recordSelect = 'SELECT pr.*, pt.type_name, pt.color_code, d.name AS department_name, ct.case_name
                     FROM patient_records pr
                     LEFT JOIN patient_types pt ON pr.patient_type_id = pt.id
                     LEFT JOIN departments d ON pr.department_id = d.id
                     LEFT JOIN case_types ct ON pr.case_type_id = ct.id';

    if ($page === 'dashboard') {
        $dentalCountResult = $conn->query("SELECT COUNT(*) AS total FROM patient_records WHERE module_type = 'dental'");
        $dental_total = $dentalCountResult ? (int) $dentalCountResult->fetch_assoc()['total'] : 0;

        $healthCountResult = $conn->query("SELECT COUNT(*) AS total FROM patient_records WHERE module_type = 'health'");
        $health_total = $healthCountResult ? (int) $healthCountResult->fetch_assoc()['total'] : 0;

        $dental_result = $conn->query(
            $recordSelect." WHERE pr.module_type = 'dental' ORDER BY pr.created_at DESC LIMIT 3"
        );
        $health_result = $conn->query(
            $recordSelect." WHERE pr.module_type = 'health' ORDER BY pr.created_at DESC LIMIT 2"
        );
    } else {
        $dentalLike = '%'.$dental_search.'%';
        $dentalCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM patient_records WHERE module_type = 'dental' AND full_name LIKE ?");
        $dentalCountStmt->bind_param('s', $dentalLike);
        $dentalCountStmt->execute();
        $dental_count_result = $dentalCountStmt->get_result();
        $dental_total = $dental_count_result ? (int) $dental_count_result->fetch_assoc()['total'] : 0;
        $dental_total_pages = $dental_total > 0 ? (int) ceil($dental_total / $health_dental_items_per_page) : 0;
        if ($dental_total_pages > 0) {
            $dental_page = min($dental_page, $dental_total_pages);
        }
        $dental_offset = ($dental_page - 1) * $health_dental_items_per_page;

        $dentalListStmt = $conn->prepare($recordSelect." WHERE pr.module_type = 'dental' AND pr.full_name LIKE ? ORDER BY pr.created_at DESC LIMIT ? OFFSET ?");
        $dentalListStmt->bind_param('sii', $dentalLike, $health_dental_items_per_page, $dental_offset);
        $dentalListStmt->execute();
        $dental_result = $dentalListStmt->get_result();

        $healthLike = '%'.$health_search.'%';
        $healthCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM patient_records WHERE module_type = 'health' AND full_name LIKE ?");
        $healthCountStmt->bind_param('s', $healthLike);
        $healthCountStmt->execute();
        $health_count_result = $healthCountStmt->get_result();
        $health_total = $health_count_result ? (int) $health_count_result->fetch_assoc()['total'] : 0;
        $health_total_pages = $health_total > 0 ? (int) ceil($health_total / $health_dental_items_per_page) : 0;
        if ($health_total_pages > 0) {
            $health_page = min($health_page, $health_total_pages);
        }
        $health_offset = ($health_page - 1) * $health_dental_items_per_page;

        $healthListStmt = $conn->prepare($recordSelect." WHERE pr.module_type = 'health' AND pr.full_name LIKE ? ORDER BY pr.created_at DESC LIMIT ? OFFSET ?");
        $healthListStmt->bind_param('sii', $healthLike, $health_dental_items_per_page, $health_offset);
        $healthListStmt->execute();
        $health_result = $healthListStmt->get_result();
    }
    } catch (mysqli_sql_exception $e) {
        error_log('[WPU page data] patient records: '.$e->getMessage());
        $GLOBALS['error_message'] = 'Unable to load patient records. If this persists, run database migrations or contact the administrator.';
    }
}
