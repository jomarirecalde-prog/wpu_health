<?php
require_once __DIR__ . '/../../includes/wpu_security.php';
wpu_bootstrap_admin_api();
// Database connection - Using unified database
require_once '../../config/database.php';
require_once __DIR__ . '/../../includes/pdo_activity_log.php';
require_once __DIR__ . '/patient_record_quick_view_lib.php';
require_once __DIR__.'/../../includes/wpu_healthhub.php';
require_once __DIR__.'/wpu_record_consent_bridge.php';
$pdo = getDBConnection();

// Handle POST request - Update record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_record') {
    $record_id = $_POST['record_id'] ?? '';
    $patient_type_id = $_POST['patient_type'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $is_minor = $_POST['is_minor'] ?? 'No';
    $guardian_name = ($is_minor == 'Yes') ? ($_POST['guardian_name'] ?? '') : '';
    $phone_number = $_POST['phone_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $department_id = $_POST['department'] ?? '';
    $visit_date = $_POST['visit_date'] ?? '';
    $case_type_id = $_POST['case_type'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment = $_POST['treatment'] ?? '';
    $subjective = $_POST['subjective'] ?? '';
    $objectives = $_POST['objectives'] ?? '';
    $diagnostics = $_POST['diagnostics'] ?? '';
    $assessment = $_POST['assessment'] ?? '';
    $plan = $_POST['plan'] ?? '';
    $doctor = $_POST['doctor'] ?? '';
    $transaction_type = trim((string) ($_POST['transaction_type'] ?? ''));

    $response = ['success' => false, 'message' => ''];

    // Validate required fields
    if (!$record_id || !$patient_type_id || !$student_id || !$full_name || !$gender || 
        !$age || !$marital_status || !$department_id || !$visit_date || !$case_type_id || 
        !$diagnosis || !$treatment || !$doctor) {
        
        $missing_fields = [];
        if (!$record_id) $missing_fields[] = "Record ID";
        if (!$patient_type_id) $missing_fields[] = "Patient Type";
        if (!$student_id) $missing_fields[] = "ID Number";
        if (!$full_name) $missing_fields[] = "Full Name";
        if (!$gender) $missing_fields[] = "Gender";
        if (!$age) $missing_fields[] = "Age";
        if (!$marital_status) $missing_fields[] = "Marital Status";
        if (!$department_id) $missing_fields[] = "Department";
        if (!$visit_date) $missing_fields[] = "Visit Date";
        if (!$case_type_id) $missing_fields[] = "Case Type";
        if (!$diagnosis) $missing_fields[] = "Diagnosis";
        if (!$treatment) $missing_fields[] = "Treatment";
        if (!$doctor) $missing_fields[] = "Doctor";
        
        $response['message'] = "Please fill in all required fields. Missing: " . implode(', ', $missing_fields);
    } else {
        try {
            // Get module_type from existing record to preserve it
            $checkStmt = $pdo->prepare("SELECT * FROM patient_records WHERE id = ?");
            $checkStmt->execute([$record_id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                $response['message'] = "Record not found.";
            } else {
                $role = wpu_healthhub_admin_role($pdo);
                $moduleType = (string) ($existing['module_type'] ?? 'health');
                $gate = wpu_record_consent_gate_from_post($pdo, $moduleType, $_POST, $role, $existing);
                if (! $gate['ok']) {
                    header('Content-Type: application/json');
                    echo json_encode($gate['response']);
                    exit;
                }
                $linkedConsentId = (int) $gate['consent_id'];
                $beforeRow = fetch_patient_record_for_quick_view($pdo, (int) $record_id);
                $snapshotJson = $beforeRow ? patient_record_quick_view_encode_snapshot($beforeRow) : null;
                $editor = (string) ($_SESSION['admin_username'] ?? '');
                if ($editor === '' && defined('WPU_LARAVEL_ADMIN_USER')) {
                    $editor = (string) WPU_LARAVEL_ADMIN_USER;
                }

                patient_record_edit_history_ensure_table($pdo);
                $pdo->beginTransaction();
                try {
                    if ($snapshotJson !== null) {
                        patient_record_edit_history_append($pdo, (int) $record_id, $snapshotJson, $editor);
                    }

                    $stmt = $pdo->prepare("UPDATE patient_records SET 
                    patient_type_id = ?,
                    student_id = ?,
                    full_name = ?,
                    gender = ?,
                    age = ?,
                    marital_status = ?,
                    religion = ?,
                    is_minor = ?,
                    guardian_name = ?,
                    phone_number = ?,
                    address = ?,
                    department_id = ?,
                    visit_date = ?,
                    case_type_id = ?,
                    diagnosis = ?,
                    treatment = ?,
                    subjective = ?,
                    objectives = ?,
                    diagnostics = ?,
                    assessment = ?,
                    plan = ?,
                    doctor = ?,
                    visit_quick_snapshot = ?
                    WHERE id = ?");
                
                    $stmt->execute([
                        $patient_type_id, $student_id, $full_name, $gender, $age, $marital_status,
                        $religion, $is_minor, $guardian_name, $phone_number, $address, $department_id,
                        $visit_date, $case_type_id, $diagnosis, $treatment, $subjective, $objectives,
                        $diagnostics, $assessment, $plan, $doctor, $snapshotJson, $record_id
                    ]);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }

                wpu_record_apply_consent_metadata($pdo, (int) $record_id, $linkedConsentId, $transaction_type);

                if ($editor !== '' && function_exists('wpu_insert_activity_log')) {
                    $modLabel = ucfirst((string) ($existing['module_type'] ?? 'record'));
                    wpu_insert_activity_log($pdo, $editor, $modLabel.' record updated', 'Record ID: '.$record_id);
                }
                
                $response['success'] = true;
                $response['message'] = "Patient record updated successfully!";
            }
            
        } catch (Throwable $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle GET request - Fetch record data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $record_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM patient_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            header('Content-Type: application/json');
            echo json_encode($record);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Record not found']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>

