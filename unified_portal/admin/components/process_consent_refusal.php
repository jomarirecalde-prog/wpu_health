<?php

require_once __DIR__.'/../../includes/wpu_security.php';
wpu_bootstrap_admin_api();
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/wpu_healthhub.php';
require_once __DIR__.'/../../includes/wpu_refusal_consent.php';

/**
 * @param  array<string, mixed>  $payload
 */
function wpu_refusal_json_send(array $payload): void
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
function wpu_refusal_fetch(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consent_refusals WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

$pdo = getDBConnection();
wpu_healthhub_require_module_access($pdo, 'health');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wpu_refusal_json_send(['success' => false, 'message' => 'Invalid request']);

    return;
}

wpu_verify_csrf_request(true);

if (! wpu_schema_has_column($pdo, 'healthhub_consent_refusals', 'id')) {
    wpu_refusal_json_send(['success' => false, 'message' => 'Refusal storage is not migrated yet. Run php artisan migrate.']);

    return;
}

$action = (string) ($_POST['action'] ?? '');
if ($action === 'save_refusal') {
    $action = 'submit_patient_refusal';
}

$role = wpu_healthhub_admin_role($pdo);
$actor = (string) ($_SESSION['admin_username'] ?? 'system');

if (! wpu_refusal_consent_can_create($role) && $action !== 'physician_certify_refusal') {
    wpu_refusal_json_send(['success' => false, 'message' => 'You do not have permission to manage refusal forms.']);

    return;
}

if ($action === 'physician_certify_refusal' && ! wpu_refusal_consent_can_certify($role)) {
    wpu_refusal_json_send(['success' => false, 'message' => 'Only an authorized physician may complete physician certification.']);

    return;
}

$idempotencyKey = trim((string) ($_POST['idempotency_key'] ?? ''));
if ($idempotencyKey !== '' && wpu_schema_has_column($pdo, 'healthhub_consent_refusals', 'idempotency_key')) {
    $dup = $pdo->prepare('SELECT id FROM healthhub_consent_refusals WHERE idempotency_key = ? LIMIT 1');
    $dup->execute([$idempotencyKey]);
    $existingId = (int) $dup->fetchColumn();
    if ($existingId > 0 && ! in_array($action, ['physician_certify_refusal'], true)) {
        wpu_refusal_json_send([
            'success' => true,
            'message' => 'Refusal already saved.',
            'id' => $existingId,
            'redirect' => '?page=view_refusal&id='.$existingId,
        ]);

        return;
    }
}

$refusalId = (int) ($_POST['refusal_id'] ?? 0);
$formRef = wpu_refusal_consent_form_reference();
$templateVersion = (string) (wpu_refusal_consent_config()['template_version'] ?? $formRef);

if ($action === 'save_draft') {
    $patientName = trim((string) ($_POST['patient_name'] ?? ''));
    $patientIdentifier = trim((string) ($_POST['patient_identifier'] ?? ''));
    $patientAge = (int) ($_POST['patient_age'] ?? 0);
    $dobRaw = trim((string) ($_POST['date_of_birth'] ?? ''));
    $patientTypeLabel = trim((string) ($_POST['patient_type_label'] ?? ''));
    $pathway = $_POST['refusal_pathway'] ?? 'adult';
    if (! in_array($pathway, ['adult', 'minor_guardian'], true)) {
        $pathway = 'adult';
    }
    $guardian = trim((string) ($_POST['guardian_name'] ?? ''));
    $namedPatient = trim((string) ($_POST['named_patient'] ?? ''));
    $procedure = trim((string) ($_POST['declined_procedure'] ?? ''));
    $advisingPhysician = trim((string) ($_POST['advising_physician'] ?? ''));
    $recordId = (int) ($_POST['patient_record_id'] ?? 0);

    if ($patientName === '' || $procedure === '') {
        wpu_refusal_json_send(['success' => false, 'message' => 'Patient name and declined procedure are required for a draft.']);

        return;
    }

    $dob = ($dobRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobRaw)) ? $dobRaw : null;

    try {
        if ($refusalId > 0) {
            $existing = wpu_refusal_fetch($pdo, $refusalId);
            $status = (string) ($existing['refusal_status'] ?? 'awaiting_patient_signature');
            if (! $existing || ! in_array($status, ['draft', 'awaiting_patient_signature', ''], true)) {
                wpu_refusal_json_send(['success' => false, 'message' => 'This refusal can no longer be edited as a draft.']);

                return;
            }
            $stmt = $pdo->prepare(
                'UPDATE healthhub_consent_refusals SET
                    patient_name = ?, patient_identifier = ?, patient_age = ?, date_of_birth = ?,
                    patient_type_label = ?, refusal_pathway = ?, guardian_name = ?, named_patient = ?,
                    declined_procedure = ?, advising_physician = ?, patient_record_id = ?, refusal_status = ?,
                    form_reference = ?, refusal_version = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                $patientName,
                $patientIdentifier !== '' ? $patientIdentifier : null,
                $patientAge > 0 ? $patientAge : null,
                $dob,
                $patientTypeLabel !== '' ? $patientTypeLabel : null,
                $pathway,
                $guardian !== '' ? $guardian : null,
                $namedPatient !== '' ? $namedPatient : null,
                $procedure,
                $advisingPhysician !== '' ? $advisingPhysician : null,
                $recordId > 0 ? $recordId : null,
                'awaiting_patient_signature',
                $formRef,
                $templateVersion,
                $refusalId,
            ]);
            wpu_refusal_consent_audit($pdo, $refusalId, 'draft_updated', $actor, $patientName);
            wpu_refusal_json_send(['success' => true, 'message' => 'Draft saved.', 'id' => $refusalId]);

            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO healthhub_consent_refusals
            (patient_record_id, patient_name, patient_identifier, patient_age, date_of_birth, patient_type_label,
             refusal_pathway, guardian_name, named_patient, declined_procedure, advising_physician, refusal_status,
             refusal_version, form_reference, idempotency_key, created_by, recorded_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
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
            $procedure,
            $advisingPhysician !== '' ? $advisingPhysician : null,
            'awaiting_patient_signature',
            $templateVersion,
            $formRef,
            $idempotencyKey !== '' ? $idempotencyKey : null,
            $actor,
            $actor,
        ]);
        $newId = (int) $pdo->lastInsertId();
        wpu_refusal_consent_audit($pdo, $newId, 'draft_created', $actor, $patientName);
        wpu_refusal_json_send([
            'success' => true,
            'message' => 'Draft created. Capture patient signature to continue.',
            'id' => $newId,
            'redirect' => '?page=consent_refusal&id='.$newId,
        ]);
    } catch (PDOException) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Could not save draft.']);
    }

    return;
}

if ($action === 'submit_patient_refusal') {
    $pathway = $_POST['refusal_pathway'] ?? 'adult';
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
    $procedure = trim((string) ($_POST['declined_procedure'] ?? ''));
    $advisingPhysician = trim((string) ($_POST['advising_physician'] ?? ''));
    $signedBy = trim((string) ($_POST['signed_by_name'] ?? ''));
    $relationship = trim((string) ($_POST['representative_relationship'] ?? ''));
    $signature = (string) ($_POST['signature_data'] ?? '');
    $recordId = (int) ($_POST['patient_record_id'] ?? 0);

    $interpreterName = trim((string) ($_POST['interpreter_name'] ?? ''));
    $interpreterSig = (string) ($_POST['interpreter_signature_data'] ?? '');
    $witnessName = trim((string) ($_POST['witness_name'] ?? ''));
    $witnessSig = (string) ($_POST['witness_signature_data'] ?? '');

    if ($patientName === '' || $procedure === '' || $advisingPhysician === '' || $signedBy === '' || ! wpu_medical_consent_validate_signature_data_url($signature)) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Complete all required fields (including advising physician) and sign the form.']);

        return;
    }

    if ($pathway === 'minor_guardian' && ($guardian === '' || $relationship === '')) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Authorized representative name and relationship are required for a minor refusal.']);

        return;
    }

    if ($interpreterName !== '' && ! wpu_medical_consent_validate_signature_data_url($interpreterSig)) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Interpreter signature is required when an interpreter is named.']);

        return;
    }

    if ($witnessName !== '' && ! wpu_medical_consent_validate_signature_data_url($witnessSig)) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Witness signature is required when a witness is named.']);

        return;
    }

    $dob = ($dobRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobRaw)) ? $dobRaw : null;
    $capacity = $pathway === 'minor_guardian' ? 'guardian' : ($relationship !== '' ? 'representative' : 'patient');

    try {
        if ($refusalId > 0) {
            $existing = wpu_refusal_fetch($pdo, $refusalId);
            $status = (string) ($existing['refusal_status'] ?? '');
            if (! $existing || in_array($status, ['completed', 'withdrawn', 'superseded'], true)) {
                wpu_refusal_json_send(['success' => false, 'message' => 'This refusal record cannot accept a patient signature.']);

                return;
            }
        }

        if ($refusalId <= 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO healthhub_consent_refusals
                (patient_record_id, patient_name, patient_identifier, patient_age, date_of_birth, patient_type_label,
                 refusal_pathway, guardian_name, named_patient, declined_procedure, advising_physician, signed_by_name,
                 signed_capacity, representative_relationship, refusal_status, refusal_version, form_reference,
                 idempotency_key, created_by, recorded_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
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
                $procedure,
                $advisingPhysician,
                $signedBy,
                $capacity,
                $relationship !== '' ? $relationship : null,
                'awaiting_physician_certification',
                $templateVersion,
                $formRef,
                $idempotencyKey !== '' ? $idempotencyKey : null,
                $actor,
                $actor,
            ]);
            $refusalId = (int) $pdo->lastInsertId();
        }

        $patientSigPath = wpu_refusal_consent_store_signature($refusalId, 'patient', $signature);
        if ($patientSigPath === null) {
            wpu_refusal_json_send(['success' => false, 'message' => 'Could not store patient signature securely.']);

            return;
        }

        $interpreterPath = null;
        $witnessPath = null;
        if ($interpreterName !== '') {
            $interpreterPath = wpu_refusal_consent_store_signature($refusalId, 'interpreter', $interpreterSig);
        }
        if ($witnessName !== '') {
            $witnessPath = wpu_refusal_consent_store_signature($refusalId, 'witness', $witnessSig);
        }

        $stmt = $pdo->prepare(
            'UPDATE healthhub_consent_refusals SET
                patient_name = ?, patient_identifier = ?, patient_age = ?, date_of_birth = ?, patient_type_label = ?,
                refusal_pathway = ?, guardian_name = ?, named_patient = ?, declined_procedure = ?,
                advising_physician = ?, signed_by_name = ?, signed_capacity = ?, representative_relationship = ?,
                signature_data = NULL, signature_storage_path = ?, signed_at = NOW(),
                interpreter_name = ?, interpreter_signature_path = ?, interpreter_signed_at = ?,
                witness_name = ?, witness_signature_path = ?, witness_signed_at = ?,
                refusal_status = ?, patient_record_id = COALESCE(?, patient_record_id),
                form_reference = ?, refusal_version = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $patientName,
            $patientIdentifier !== '' ? $patientIdentifier : null,
            $age > 0 ? $age : null,
            $dob,
            $patientTypeLabel !== '' ? $patientTypeLabel : null,
            $pathway,
            $guardian !== '' ? $guardian : null,
            $namedPatient !== '' ? $namedPatient : null,
            $procedure,
            $advisingPhysician,
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
            $refusalId,
        ]);

        wpu_refusal_consent_audit($pdo, $refusalId, 'patient_signed', $actor, $signedBy);
        wpu_security_audit($pdo, 'refusal_patient_signed', 'Patient: '.$patientName.' (# '.$refusalId.')');

        wpu_refusal_json_send([
            'success' => true,
            'message' => 'Refusal captured. Awaiting physician certification.',
            'id' => $refusalId,
            'redirect' => '?page=view_refusal&id='.$refusalId,
        ]);
    } catch (PDOException) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Could not save refusal. Run database migrations if columns are missing.']);
    }

    return;
}

if ($action === 'physician_certify_refusal') {
    $refusalId = (int) ($_POST['refusal_id'] ?? 0);
    $physicianPrinted = trim((string) ($_POST['physician_printed_name'] ?? ''));
    $physicianSig = (string) ($_POST['physician_signature_data'] ?? '');

    if ($refusalId <= 0 || $physicianPrinted === '' || ! wpu_medical_consent_validate_signature_data_url($physicianSig)) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Physician printed name and signature are required.']);

        return;
    }

    $existing = wpu_refusal_fetch($pdo, $refusalId);
    if (! $existing) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Refusal record not found.']);

        return;
    }

    $status = (string) ($existing['refusal_status'] ?? '');
    if ($status !== 'awaiting_physician_certification') {
        wpu_refusal_json_send(['success' => false, 'message' => 'This refusal is not awaiting physician certification.']);

        return;
    }

    if (empty($existing['signature_storage_path']) && empty($existing['signature_data'])) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Patient or representative signature is missing.']);

        return;
    }

    $physicianPath = wpu_refusal_consent_store_signature($refusalId, 'physician', $physicianSig);
    if ($physicianPath === null) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Could not store physician signature.']);

        return;
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE healthhub_consent_refusals SET
                physician_printed_name = ?, physician_signature_path = ?,
                physician_certified_at = NOW(), physician_certified_by = ?,
                refusal_status = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $physicianPrinted,
            $physicianPath,
            $actor,
            'completed',
            $refusalId,
        ]);

        wpu_refusal_consent_audit($pdo, $refusalId, 'physician_certified', $actor, $physicianPrinted);
        wpu_security_audit($pdo, 'refusal_completed', 'Refusal #'.$refusalId.' certified by '.$actor);

        wpu_refusal_json_send([
            'success' => true,
            'message' => 'Physician certification completed. Refusal is fully documented.',
            'id' => $refusalId,
            'redirect' => '?page=view_refusal&id='.$refusalId,
        ]);
    } catch (PDOException) {
        wpu_refusal_json_send(['success' => false, 'message' => 'Could not save physician certification.']);
    }

    return;
}

if ($action === 'withdraw_refusal') {
    $refusalId = (int) ($_POST['refusal_id'] ?? 0);
    $reason = trim((string) ($_POST['withdrawal_reason'] ?? ''));
    $result = wpu_refusal_consent_withdraw($pdo, $refusalId, $actor, $reason, $role);
    wpu_refusal_json_send(array_merge($result, ['redirect' => '?page=view_refusal&id='.$refusalId]));

    return;
}

wpu_refusal_json_send(['success' => false, 'message' => 'Unknown action.']);
