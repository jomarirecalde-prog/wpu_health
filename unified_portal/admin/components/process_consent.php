<?php

require_once __DIR__.'/../../includes/wpu_security.php';
wpu_bootstrap_admin_api();
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/wpu_healthhub.php';
require_once __DIR__.'/../../includes/wpu_medical_consent.php';

/**
 * @param  array<string, mixed>  $payload
 */
function wpu_consent_json_send(array $payload): void
{
    if (! headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }
    echo json_encode($payload);
    if (! defined('WPU_LARAVEL_BRIDGE') || ! WPU_LARAVEL_BRIDGE) {
        exit;
    }
}

/**
 * @return array<string, mixed>|null
 */
function wpu_consent_fetch(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consents WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

$pdo = getDBConnection();
wpu_healthhub_require_module_access($pdo, 'health');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wpu_consent_json_send(['success' => false, 'message' => 'Invalid request']);

    return;
}

wpu_verify_csrf_request(true);

if (! wpu_schema_has_column($pdo, 'healthhub_consents', 'id')) {
    wpu_consent_json_send(['success' => false, 'message' => 'Consent storage is not migrated yet. Run php artisan migrate.']);

    return;
}

$action = (string) ($_POST['action'] ?? 'save_consent');
if ($action === 'save_consent') {
    $action = 'submit_patient_consent';
}

$role = wpu_healthhub_admin_role($pdo);
$actor = (string) ($_SESSION['admin_username'] ?? 'system');

if (! wpu_medical_consent_can_create($role) && $action !== 'physician_certify') {
    wpu_consent_json_send(['success' => false, 'message' => 'You do not have permission to manage consent forms.']);

    return;
}

if ($action === 'physician_certify' && ! wpu_medical_consent_can_certify($role)) {
    wpu_consent_json_send(['success' => false, 'message' => 'Only an authorized physician may complete physician certification.']);

    return;
}

$idempotencyKey = trim((string) ($_POST['idempotency_key'] ?? ''));
if ($idempotencyKey !== '' && wpu_schema_has_column($pdo, 'healthhub_consents', 'idempotency_key')) {
    $dup = $pdo->prepare('SELECT id FROM healthhub_consents WHERE idempotency_key = ? LIMIT 1');
    $dup->execute([$idempotencyKey]);
    $existingId = (int) $dup->fetchColumn();
    if ($existingId > 0 && ! in_array($action, ['physician_certify'], true)) {
        wpu_consent_json_send([
            'success' => true,
            'message' => 'Consent already saved.',
            'id' => $existingId,
            'redirect' => '?page=view_consent&id='.$existingId,
        ]);

        return;
    }
}

$consentId = (int) ($_POST['consent_id'] ?? 0);
$formRef = wpu_medical_consent_form_reference();
$templateVersion = (string) (wpu_medical_consent_config()['template_version'] ?? $formRef);

if ($action === 'save_draft') {
    $patientName = trim((string) ($_POST['patient_name'] ?? ''));
    $patientIdentifier = trim((string) ($_POST['patient_identifier'] ?? ''));
    $patientAge = (int) ($_POST['patient_age'] ?? 0);
    $dobRaw = trim((string) ($_POST['date_of_birth'] ?? ''));
    $patientTypeLabel = trim((string) ($_POST['patient_type_label'] ?? ''));
    $pathway = $_POST['consent_pathway'] ?? 'adult';
    if (! in_array($pathway, ['adult', 'minor_guardian'], true)) {
        $pathway = 'adult';
    }
    $guardian = trim((string) ($_POST['guardian_name'] ?? ''));
    $namedPatient = trim((string) ($_POST['named_patient'] ?? ''));
    $purpose = trim((string) ($_POST['treatment_procedure'] ?? $_POST['purpose'] ?? ''));
    $recordId = (int) ($_POST['patient_record_id'] ?? 0);

    if ($patientName === '' || $purpose === '') {
        wpu_consent_json_send(['success' => false, 'message' => 'Patient name and treatment/procedure are required for a draft.']);

        return;
    }

    $dob = ($dobRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobRaw)) ? $dobRaw : null;

    try {
        if ($consentId > 0) {
            $existing = wpu_consent_fetch($pdo, $consentId);
            if (! $existing || ! in_array($existing['consent_status'] ?? 'draft', ['draft', 'awaiting_patient_signature'], true)) {
                wpu_consent_json_send(['success' => false, 'message' => 'This consent can no longer be edited as a draft.']);

                return;
            }
            $stmt = $pdo->prepare(
                'UPDATE healthhub_consents SET
                    patient_name = ?, patient_identifier = ?, patient_age = ?, date_of_birth = ?,
                    patient_type_label = ?, consent_pathway = ?, guardian_name = ?, named_patient = ?,
                    purpose = ?, patient_record_id = ?, consent_status = ?, form_reference = ?,
                    consent_version = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                $patientName, $patientIdentifier !== '' ? $patientIdentifier : null,
                $patientAge > 0 ? $patientAge : null, $dob,
                $patientTypeLabel !== '' ? $patientTypeLabel : null,
                $pathway, $guardian !== '' ? $guardian : null,
                $namedPatient !== '' ? $namedPatient : null,
                $purpose, $recordId > 0 ? $recordId : null,
                'awaiting_patient_signature', $formRef, $templateVersion, $consentId,
            ]);
            wpu_medical_consent_audit($pdo, $consentId, 'draft_updated', $actor, $patientName);
            wpu_consent_json_send(['success' => true, 'message' => 'Draft saved.', 'id' => $consentId]);

            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO healthhub_consents
            (patient_record_id, patient_name, patient_identifier, patient_age, date_of_birth, patient_type_label,
             consent_pathway, guardian_name, named_patient, purpose, consent_status, consent_version, form_reference,
             idempotency_key, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $recordId > 0 ? $recordId : null,
            $patientName,
            $patientIdentifier !== '' ? $patientIdentifier : null,
            $patientAge > 0 ? $patientAge : null,
            $dob,
            $patientTypeLabel !== '' ? $patientTypeLabel : null,
            $pathway,
            $guardian !== '' ? $guardian : null,
            $namedPatient !== '' ? $namedPatient : null,
            $purpose,
            'awaiting_patient_signature',
            $templateVersion,
            $formRef,
            $idempotencyKey !== '' ? $idempotencyKey : null,
            $actor,
        ]);
        $newId = (int) $pdo->lastInsertId();
        if ($recordId > 0) {
            wpu_medical_consent_link_patient_record($pdo, $newId, $recordId);
        }
        wpu_medical_consent_audit($pdo, $newId, 'draft_created', $actor, $patientName);
        wpu_security_audit($pdo, 'consent_draft_created', 'Patient: '.$patientName);
        wpu_consent_json_send([
            'success' => true,
            'message' => 'Draft created. Capture patient signature to continue.',
            'id' => $newId,
            'redirect' => '?page=secure_consent&id='.$newId,
        ]);
    } catch (PDOException) {
        wpu_consent_json_send(['success' => false, 'message' => 'Could not save draft. Run database migrations if this is a new install.']);
    }

    return;
}

if ($action === 'submit_patient_consent') {
    $pathway = $_POST['consent_pathway'] ?? 'adult';
    if (! in_array($pathway, ['adult', 'minor_guardian'], true)) {
        $pathway = 'adult';
    }

    $patientName = trim((string) ($_POST['patient_name'] ?? ''));
    $patientIdentifier = trim((string) ($_POST['patient_identifier'] ?? ''));
    $age = (int) ($_POST['patient_age'] ?? 0);
    $dobRaw = trim((string) ($_POST['date_of_birth'] ?? ''));
    $patientTypeLabel = trim((string) ($_POST['patient_type_label'] ?? ''));
    $guardian = trim((string) ($_POST['guardian_name'] ?? ''));
    $namedPatient = trim((string) ($_POST['named_patient'] ?? ''));
    $purpose = trim((string) ($_POST['treatment_procedure'] ?? $_POST['purpose'] ?? ''));
    $signedBy = trim((string) ($_POST['signed_by_name'] ?? ''));
    $relationship = trim((string) ($_POST['representative_relationship'] ?? ''));
    $signature = (string) ($_POST['signature_data'] ?? '');
    $recordId = (int) ($_POST['patient_record_id'] ?? 0);

    $interpreterName = trim((string) ($_POST['interpreter_name'] ?? ''));
    $interpreterSig = (string) ($_POST['interpreter_signature_data'] ?? '');
    $witnessName = trim((string) ($_POST['witness_name'] ?? ''));
    $witnessSig = (string) ($_POST['witness_signature_data'] ?? '');

    if ($patientName === '' || $purpose === '' || $signedBy === '' || ! wpu_medical_consent_validate_signature_data_url($signature)) {
        wpu_consent_json_send(['success' => false, 'message' => 'Complete all required fields and sign the form.']);

        return;
    }

    if ($pathway === 'minor_guardian' && ($guardian === '' || $relationship === '')) {
        wpu_consent_json_send(['success' => false, 'message' => 'Authorized representative name and relationship are required for minor consent.']);

        return;
    }

    if ($pathway === 'adult' && $relationship !== '' && strtolower($relationship) !== 'self' && strtolower($relationship) !== 'patient') {
        // representative signing for adult — allowed with relationship
    }

    if ($interpreterName !== '' && ! wpu_medical_consent_validate_signature_data_url($interpreterSig)) {
        wpu_consent_json_send(['success' => false, 'message' => 'Interpreter signature is required when an interpreter is named.']);

        return;
    }

    if ($witnessName !== '' && ! wpu_medical_consent_validate_signature_data_url($witnessSig)) {
        wpu_consent_json_send(['success' => false, 'message' => 'Witness signature is required when a witness is named.']);

        return;
    }

    $dob = ($dobRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobRaw)) ? $dobRaw : null;
    $capacity = $pathway === 'minor_guardian' ? 'guardian' : ($relationship !== '' ? 'representative' : 'patient');
    $createdBy = $actor;

    try {
        if ($consentId > 0) {
            $existing = wpu_consent_fetch($pdo, $consentId);
            if (! $existing || in_array($existing['consent_status'] ?? '', ['completed', 'withdrawn', 'superseded'], true)) {
                wpu_consent_json_send(['success' => false, 'message' => 'This consent record cannot accept a patient signature.']);

                return;
            }
        }

        if ($consentId <= 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO healthhub_consents
                (patient_record_id, patient_name, patient_identifier, patient_age, date_of_birth, patient_type_label,
                 consent_pathway, guardian_name, named_patient, purpose, signed_by_name, signed_capacity,
                 representative_relationship, consent_status, consent_version, form_reference, idempotency_key,
                 created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $recordId > 0 ? $recordId : null,
                $patientName,
                $patientIdentifier !== '' ? $patientIdentifier : null,
                $age > 0 ? $age : null,
                $dob,
                $patientTypeLabel !== '' ? $patientTypeLabel : null,
                $pathway,
                $guardian !== '' ? $guardian : null,
                $namedPatient !== '' ? $namedPatient : null,
                $purpose,
                $signedBy,
                $capacity,
                $relationship !== '' ? $relationship : null,
                'awaiting_physician_certification',
                $templateVersion,
                $formRef,
                $idempotencyKey !== '' ? $idempotencyKey : null,
                $createdBy,
            ]);
            $consentId = (int) $pdo->lastInsertId();
        }

        $patientSigPath = wpu_medical_consent_store_signature($consentId, 'patient', $signature);
        if ($patientSigPath === null) {
            wpu_consent_json_send(['success' => false, 'message' => 'Could not store patient signature securely.']);

            return;
        }

        $interpreterPath = null;
        $witnessPath = null;
        if ($interpreterName !== '') {
            $interpreterPath = wpu_medical_consent_store_signature($consentId, 'interpreter', $interpreterSig);
        }
        if ($witnessName !== '') {
            $witnessPath = wpu_medical_consent_store_signature($consentId, 'witness', $witnessSig);
        }

        $updateSql = 'UPDATE healthhub_consents SET
            patient_name = ?, patient_identifier = ?, patient_age = ?, date_of_birth = ?, patient_type_label = ?,
            consent_pathway = ?, guardian_name = ?, named_patient = ?, purpose = ?,
            signed_by_name = ?, signed_capacity = ?, representative_relationship = ?,
            signature_data = NULL, signature_storage_path = ?, signed_at = NOW(),
            interpreter_name = ?, interpreter_signature_path = ?, interpreter_signed_at = ?,
            witness_name = ?, witness_signature_path = ?, witness_signed_at = ?,
            consent_status = ?, patient_record_id = COALESCE(?, patient_record_id),
            form_reference = ?, consent_version = ?, updated_at = NOW()
            WHERE id = ?';

        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            $patientName,
            $patientIdentifier !== '' ? $patientIdentifier : null,
            $age > 0 ? $age : null,
            $dob,
            $patientTypeLabel !== '' ? $patientTypeLabel : null,
            $pathway,
            $guardian !== '' ? $guardian : null,
            $namedPatient !== '' ? $namedPatient : null,
            $purpose,
            $signedBy,
            $capacity,
            $relationship !== '' ? $relationship : null,
            $patientSigPath,
            $interpreterName !== '' ? $interpreterName : null,
            $interpreterPath,
            $interpreterPath ? date('Y-m-d H:i:s') : null,
            $witnessName !== '' ? $witnessName : null,
            $witnessPath,
            $witnessPath ? date('Y-m-d H:i:s') : null,
            'awaiting_physician_certification',
            $recordId > 0 ? $recordId : null,
            $formRef,
            $templateVersion,
            $consentId,
        ]);

        if ($recordId > 0) {
            wpu_medical_consent_link_patient_record($pdo, $consentId, $recordId);
        }

        wpu_medical_consent_audit($pdo, $consentId, 'patient_signed', $actor, $signedBy);
        wpu_security_audit($pdo, 'consent_patient_signed', 'Patient: '.$patientName.' (# '.$consentId.')');

        wpu_consent_json_send([
            'success' => true,
            'message' => 'Patient consent captured. Awaiting physician certification.',
            'id' => $consentId,
            'redirect' => '?page=view_consent&id='.$consentId,
        ]);
    } catch (PDOException) {
        wpu_consent_json_send(['success' => false, 'message' => 'Could not save consent.']);
    }

    return;
}

if ($action === 'physician_certify') {
    $consentId = (int) ($_POST['consent_id'] ?? 0);
    $physicianPrinted = trim((string) ($_POST['physician_printed_name'] ?? ''));
    $physicianSig = (string) ($_POST['physician_signature_data'] ?? '');

    if ($consentId <= 0 || $physicianPrinted === '' || ! wpu_medical_consent_validate_signature_data_url($physicianSig)) {
        wpu_consent_json_send(['success' => false, 'message' => 'Physician printed name and signature are required.']);

        return;
    }

    $existing = wpu_consent_fetch($pdo, $consentId);
    if (! $existing) {
        wpu_consent_json_send(['success' => false, 'message' => 'Consent record not found.']);

        return;
    }

    if (($existing['consent_status'] ?? '') !== 'awaiting_physician_certification') {
        wpu_consent_json_send(['success' => false, 'message' => 'This consent is not awaiting physician certification.']);

        return;
    }

    if (empty($existing['signature_storage_path']) && empty($existing['signature_data'])) {
        wpu_consent_json_send(['success' => false, 'message' => 'Patient or representative signature is missing.']);

        return;
    }

    $physicianPath = wpu_medical_consent_store_signature($consentId, 'physician', $physicianSig);
    if ($physicianPath === null) {
        wpu_consent_json_send(['success' => false, 'message' => 'Could not store physician signature.']);

        return;
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE healthhub_consents SET
                physician_printed_name = ?, physician_signature_path = ?,
                physician_certified_at = NOW(), physician_certified_by = ?,
                consent_status = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $physicianPrinted,
            $physicianPath,
            $actor,
            'completed',
            $consentId,
        ]);

        wpu_medical_consent_audit($pdo, $consentId, 'physician_certified', $actor, $physicianPrinted);
        wpu_security_audit($pdo, 'consent_completed', 'Consent #'.$consentId.' certified by '.$actor);

        wpu_consent_json_send([
            'success' => true,
            'message' => 'Physician certification completed. Consent is fully executed.',
            'id' => $consentId,
            'redirect' => '?page=view_consent&id='.$consentId,
        ]);
    } catch (PDOException) {
        wpu_consent_json_send(['success' => false, 'message' => 'Could not save physician certification.']);
    }

    return;
}

if ($action === 'withdraw_consent') {
    $consentId = (int) ($_POST['consent_id'] ?? 0);
    $reason = trim((string) ($_POST['withdrawal_reason'] ?? ''));
    $result = wpu_medical_consent_withdraw($pdo, $consentId, $actor, $reason, $role);
    wpu_consent_json_send(array_merge($result, ['redirect' => '?page=view_consent&id='.$consentId]));

    return;
}

if ($action === 'supersede_consent') {
    $consentId = (int) ($_POST['consent_id'] ?? 0);
    $reason = trim((string) ($_POST['supersede_reason'] ?? ''));
    $result = wpu_medical_consent_supersede($pdo, $consentId, $actor, $reason, $role);
    if ($result['success'] && ! empty($result['new_consent_id'])) {
        $result['redirect'] = '?page=secure_consent&id='.(int) $result['new_consent_id'];
    }
    wpu_consent_json_send($result);

    return;
}

wpu_consent_json_send(['success' => false, 'message' => 'Unknown action.']);
