<?php

require_once __DIR__.'/../../includes/wpu_security.php';
wpu_bootstrap_admin_api();
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/wpu_healthhub.php';
require_once __DIR__.'/../../includes/wpu_medical_consent.php';

header('Content-Type: application/json; charset=UTF-8');

$pdo = getDBConnection();
wpu_healthhub_require_module_access($pdo, 'health');

$studentId = trim((string) ($_GET['student_id'] ?? ''));
if ($studentId === '') {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);

    exit;
}

if (! wpu_schema_has_column($pdo, 'healthhub_consents', 'consent_status')) {
    echo json_encode(['success' => false, 'message' => 'Consent module not migrated']);

    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, patient_name, purpose, consent_status, signed_at, physician_certified_at
     FROM healthhub_consents
     WHERE patient_identifier = ?
     ORDER BY id DESC
     LIMIT 15'
);
$stmt->execute([$studentId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$completed = [];
foreach ($rows as $row) {
    if (wpu_medical_consent_is_completed($row)) {
        $completed[] = [
            'id' => (int) $row['id'],
            'patient_name' => $row['patient_name'],
            'purpose' => $row['purpose'],
            'signed_at' => $row['signed_at'],
        ];
    }
}

echo json_encode(['success' => true, 'consents' => $completed]);
