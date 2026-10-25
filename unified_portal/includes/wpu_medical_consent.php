<?php

declare(strict_types=1);

if (defined('WPU_MEDICAL_CONSENT_LOADED')) {
    return;
}
define('WPU_MEDICAL_CONSENT_LOADED', true);

/**
 * @return array<string, mixed>
 */
function wpu_medical_consent_config(): array
{
    if (function_exists('config')) {
        return config('medical_consent', []);
    }

    $path = __DIR__.'/../../config/medical_consent.php';
    if (is_readable($path)) {
        return require $path;
    }

    return [];
}

function wpu_medical_consent_form_reference(): string
{
    $cfg = wpu_medical_consent_config();

    return (string) ($cfg['form_reference'] ?? 'WPU-QSF-OSAS-HSO-23 Rev. 00 (08.15.25)');
}

function wpu_medical_consent_advising_physician(): string
{
    $cfg = wpu_medical_consent_config();

    return (string) ($cfg['advising_physician'] ?? 'MICAELA T. BAGALANON-LABUTOY, MD, OHP, SHC');
}

/** @return list<string> */
function wpu_medical_consent_statuses(): array
{
    return [
        'draft',
        'awaiting_patient_signature',
        'awaiting_physician_certification',
        'completed',
        'withdrawn',
        'superseded',
    ];
}

function wpu_medical_consent_can_view_full(string $role): bool
{
    return in_array(strtolower(trim($role)), ['super_admin', 'admin', 'physician', 'dentist', 'nurse'], true);
}

function wpu_medical_consent_can_certify(string $role): bool
{
    return in_array(strtolower(trim($role)), ['super_admin', 'physician'], true);
}

function wpu_medical_consent_can_create(string $role): bool
{
    return in_array(strtolower(trim($role)), ['super_admin', 'admin', 'physician', 'dentist', 'nurse'], true);
}

function wpu_medical_consent_storage_root(): string
{
    if (function_exists('storage_path')) {
        return storage_path('app/private/healthhub_consents');
    }

    return dirname(__DIR__).'/storage/private/healthhub_consents';
}

function wpu_medical_consent_validate_signature_data_url(string $data): bool
{
    if ($data === '') {
        return false;
    }
    if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $data, $m)) {
        return false;
    }
    $raw = base64_decode($m[1], true);
    if ($raw === false || strlen($raw) < 200) {
        return false;
    }

    return str_starts_with($raw, "\x89PNG\r\n\x1a\n");
}

/**
 * Persist PNG signature; returns relative path segment (consent id / filename).
 */
function wpu_medical_consent_store_signature(int $consentId, string $kind, string $dataUrl): ?string
{
    if (! wpu_medical_consent_validate_signature_data_url($dataUrl)) {
        return null;
    }

    preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m);
    $binary = base64_decode($m[1], true);
    if ($binary === false) {
        return null;
    }

    $kind = preg_replace('/[^a-z_]/', '', strtolower($kind)) ?: 'signature';
    $dir = wpu_medical_consent_storage_root().DIRECTORY_SEPARATOR.$consentId;
    if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
        return null;
    }

    $filename = $kind.'_'.bin2hex(random_bytes(8)).'.png';
    $full = $dir.DIRECTORY_SEPARATOR.$filename;
    if (file_put_contents($full, $binary) === false) {
        return null;
    }

    return $consentId.'/'.$filename;
}

function wpu_medical_consent_signature_full_path(string $relativePath): ?string
{
    $relativePath = str_replace(['..', '\\'], ['', '/'], $relativePath);
    $full = wpu_medical_consent_storage_root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (! is_readable($full)) {
        return null;
    }

    return $full;
}

function wpu_medical_consent_audit(PDO $pdo, int $consentId, string $action, string $actor, string $details = ''): void
{
    if (! wpu_schema_has_column($pdo, 'healthhub_consent_audit_log', 'id')) {
        wpu_security_audit($pdo, 'consent_'.$action, 'Consent #'.$consentId.': '.$details);

        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO healthhub_consent_audit_log (consent_id, action, actor, details, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$consentId, $action, $actor, $details]);
    } catch (PDOException) {
        wpu_security_audit($pdo, 'consent_'.$action, 'Consent #'.$consentId.': '.$details);
    }
}

/**
 * @param  array<string, mixed>  $row
 * @return array<string, mixed>
 */
function wpu_medical_consent_filter_row(array $row, string $role): array
{
    if (wpu_medical_consent_can_view_full($role)) {
        return $row;
    }

    foreach ([
        'signature_data', 'signature_storage_path', 'interpreter_signature_path',
        'witness_signature_path', 'physician_signature_path',
    ] as $field) {
        if (array_key_exists($field, $row)) {
            $row[$field] = null;
        }
    }

    return $row;
}

function wpu_medical_consent_link_patient_record(PDO $pdo, int $consentId, int $patientRecordId): void
{
    if (! wpu_schema_has_column($pdo, 'patient_records', 'consent_record_id')) {
        return;
    }

    try {
        $stmt = $pdo->prepare('UPDATE patient_records SET consent_record_id = ? WHERE id = ?');
        $stmt->execute([$consentId, $patientRecordId]);
    } catch (PDOException) {
        // ignore
    }
}

/**
 * @param  array<string, mixed>  $row
 */
function wpu_medical_consent_signature_uri(array $row, string $pathField, string $legacyField = 'signature_data'): ?string
{
    $relative = (string) ($row[$pathField] ?? '');
    if ($relative !== '') {
        $full = wpu_medical_consent_signature_full_path($relative);
        if ($full !== null) {
            $bin = file_get_contents($full);
            if ($bin !== false) {
                return 'data:image/png;base64,'.base64_encode($bin);
            }
        }
    }

    $legacy = (string) ($row[$legacyField] ?? '');
    if ($legacy !== '' && str_starts_with($legacy, 'data:image/png')) {
        return $legacy;
    }

    return null;
}

function wpu_medical_consent_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Draft',
        'awaiting_patient_signature' => 'Awaiting patient / representative signature',
        'awaiting_physician_certification' => 'Awaiting physician certification',
        'completed' => 'Completed',
        'withdrawn' => 'Withdrawn',
        'superseded' => 'Superseded',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function wpu_medical_consent_can_manage_lifecycle(string $role): bool
{
    return in_array(strtolower(trim($role)), ['super_admin', 'admin', 'physician'], true);
}

function wpu_medical_consent_visit_requires_consent(string $moduleType, ?string $transactionType, string $treatment): bool
{
    $cfg = wpu_medical_consent_config();
    $moduleType = strtolower(trim($moduleType));
    $tx = trim((string) $transactionType);
    $treatment = trim($treatment);

    if ($moduleType === 'dental' && ($cfg['dental_always_requires_consent'] ?? true)) {
        return true;
    }

    $exempt = $cfg['transactions_exempt_from_consent'] ?? [];
    if ($tx !== '' && in_array($tx, $exempt, true)) {
        return false;
    }

    $requireList = $cfg['transactions_requiring_consent'] ?? [];
    if ($tx !== '' && in_array($tx, $requireList, true)) {
        return true;
    }

    if ($moduleType === 'health' && ($cfg['require_consent_when_treatment_present'] ?? true) && $treatment !== '') {
        return true;
    }

    return false;
}

/**
 * @param  array<string, mixed>  $consentRow
 */
function wpu_medical_consent_is_completed(array $consentRow): bool
{
    $status = (string) ($consentRow['consent_status'] ?? '');
    if ($status === 'completed') {
        return true;
    }
    if ($status === '' && ! empty($consentRow['signed_at']) && ! empty($consentRow['physician_certified_at'])) {
        return true;
    }

    return false;
}

/**
 * @param  array<string, mixed>  $consentRow
 */
function wpu_medical_consent_patient_matches(array $consentRow, string $studentId, string $fullName): bool
{
    $cid = trim((string) ($consentRow['patient_identifier'] ?? ''));
    $cname = strtolower(trim((string) ($consentRow['patient_name'] ?? '')));
    $sid = trim($studentId);
    $name = strtolower(trim($fullName));

    if ($sid !== '' && $cid !== '' && strcasecmp($sid, $cid) === 0) {
        return true;
    }

    return $name !== '' && $cname !== '' && $name === $cname;
}

/**
 * @return array<string, mixed>|null
 */
function wpu_medical_consent_get(PDO $pdo, int $consentId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consents WHERE id = ? LIMIT 1');
    $stmt->execute([$consentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * @param  array<string, mixed>  $params
 * @return array{ok: bool, message: string, consent_id?: int, consent_start_url?: string}
 */
function wpu_medical_consent_validate_for_visit(PDO $pdo, array $params): array
{
    $module = (string) ($params['module_type'] ?? 'health');
    $transactionType = (string) ($params['transaction_type'] ?? '');
    $treatment = (string) ($params['treatment'] ?? '');
    $studentId = (string) ($params['student_id'] ?? '');
    $fullName = (string) ($params['full_name'] ?? '');
    $consentRecordId = (int) ($params['consent_record_id'] ?? 0);
    $existingRecordConsentId = (int) ($params['existing_consent_record_id'] ?? 0);
    $role = (string) ($params['role'] ?? 'staff');
    $bypass = (string) ($params['consent_emergency_bypass'] ?? '') === '1';
    $bypassReason = trim((string) ($params['consent_bypass_reason'] ?? ''));

    if ($module === 'dental' && $transactionType === '') {
        $transactionType = 'Dental';
    }

    if (! wpu_medical_consent_visit_requires_consent($module, $transactionType, $treatment)) {
        return ['ok' => true, 'message' => ''];
    }

    if ($bypass) {
        $cfg = wpu_medical_consent_config();
        $allowedRoles = $cfg['consent_bypass_roles'] ?? ['super_admin', 'physician'];
        if (! in_array(strtolower(trim($role)), array_map('strtolower', $allowedRoles), true)) {
            return [
                'ok' => false,
                'message' => 'Emergency consent bypass is not permitted for your role.',
            ];
        }
        if (strlen($bypassReason) < 10) {
            return [
                'ok' => false,
                'message' => 'Document a clinical reason (at least 10 characters) for emergency bypass.',
            ];
        }
        wpu_security_audit($pdo, 'consent_bypass_used', $module.' visit for '.$fullName.': '.$bypassReason);

        return ['ok' => true, 'message' => '', 'consent_bypass' => true];
    }

    if ($consentRecordId <= 0 && $existingRecordConsentId > 0) {
        $consentRecordId = $existingRecordConsentId;
    }

    if ($consentRecordId <= 0) {
        $start = wpu_medical_consent_start_url($params);

        return [
            'ok' => false,
            'message' => 'A completed medical consent is required before saving this visit. Complete the Medical Consent Form and enter the consent record ID below.',
            'consent_required' => true,
            'consent_start_url' => $start,
        ];
    }

    $consent = wpu_medical_consent_get($pdo, $consentRecordId);
    if (! $consent) {
        return [
            'ok' => false,
            'message' => 'Consent record #'.$consentRecordId.' was not found.',
            'consent_required' => true,
        ];
    }

    $status = (string) ($consent['consent_status'] ?? '');
    if (in_array($status, ['withdrawn', 'superseded'], true)) {
        return [
            'ok' => false,
            'message' => 'Consent #'.$consentRecordId.' is '.$status.' and cannot be used. Create a new consent form.',
            'consent_required' => true,
            'consent_start_url' => wpu_medical_consent_start_url($params),
        ];
    }

    if (! wpu_medical_consent_is_completed($consent)) {
        return [
            'ok' => false,
            'message' => 'Consent #'.$consentRecordId.' is not fully completed (physician certification pending).',
            'consent_required' => true,
        ];
    }

    if (! wpu_medical_consent_patient_matches($consent, $studentId, $fullName)) {
        return [
            'ok' => false,
            'message' => 'Consent #'.$consentRecordId.' does not match this patient ID or name.',
        ];
    }

    return ['ok' => true, 'message' => '', 'consent_id' => $consentRecordId];
}

/**
 * @param  array<string, mixed>  $params
 */
function wpu_medical_consent_start_url(array $params): string
{
    $q = [
        'page' => 'secure_consent',
    ];
    if (! empty($params['full_name'])) {
        // Prefill via record id when available only; name passed for manual entry
    }
    if (! empty($params['patient_record_id'])) {
        $q['patient_record_id'] = (int) $params['patient_record_id'];
    }

    return '?'.http_build_query($q);
}

/**
 * @param  array<string, mixed>  $row
 */
function wpu_medical_consent_encode_snapshot(array $row): string
{
    $copy = $row;
    foreach (['signature_data'] as $heavy) {
        if (isset($copy[$heavy]) && is_string($copy[$heavy]) && strlen($copy[$heavy]) > 256) {
            $copy[$heavy] = '[redacted:stored_on_disk]';
        }
    }

    return json_encode($copy, JSON_UNESCAPED_UNICODE) ?: '{}';
}

function wpu_medical_consent_save_version(
    PDO $pdo,
    int $consentId,
    string $kind,
    array $snapshotRow,
    string $actor,
    ?int $replacedBy = null,
    ?string $reason = null
): void {
    if (! wpu_schema_has_column($pdo, 'healthhub_consent_versions', 'id')) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO healthhub_consent_versions
            (consent_id, version_kind, snapshot_json, reason, replaced_by_consent_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $consentId,
            $kind,
            wpu_medical_consent_encode_snapshot($snapshotRow),
            $reason,
            $replacedBy,
            $actor,
        ]);
    } catch (PDOException) {
        // ignore
    }
}

/**
 * @return array{success: bool, message: string, new_consent_id?: int}
 */
function wpu_medical_consent_withdraw(PDO $pdo, int $consentId, string $actor, string $reason, string $role): array
{
    if (! wpu_medical_consent_can_manage_lifecycle($role)) {
        return ['success' => false, 'message' => 'You cannot withdraw consent records.'];
    }

    $row = wpu_medical_consent_get($pdo, $consentId);
    if (! $row) {
        return ['success' => false, 'message' => 'Consent not found.'];
    }

    $status = (string) ($row['consent_status'] ?? '');
    if (in_array($status, ['withdrawn', 'superseded'], true)) {
        return ['success' => false, 'message' => 'This consent is already inactive.'];
    }

    if (strlen(trim($reason)) < 5) {
        return ['success' => false, 'message' => 'Withdrawal reason is required.'];
    }

    wpu_medical_consent_save_version($pdo, $consentId, 'withdrawn_snapshot', $row, $actor, null, $reason);

    $stmt = $pdo->prepare(
        'UPDATE healthhub_consents SET consent_status = ?, withdrawn_at = NOW(), withdrawn_by = ?,
         withdrawal_reason = ?, updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute(['withdrawn', $actor, $reason, $consentId]);

    wpu_medical_consent_audit($pdo, $consentId, 'withdrawn', $actor, $reason);
    wpu_security_audit($pdo, 'consent_withdrawn', 'Consent #'.$consentId.': '.$reason);

    return ['success' => true, 'message' => 'Consent withdrawn. Original record preserved in version history.'];
}

/**
 * @return array{success: bool, message: string, new_consent_id?: int}
 */
function wpu_medical_consent_supersede(PDO $pdo, int $consentId, string $actor, string $reason, string $role): array
{
    if (! wpu_medical_consent_can_manage_lifecycle($role)) {
        return ['success' => false, 'message' => 'You cannot supersede consent records.'];
    }

    $row = wpu_medical_consent_get($pdo, $consentId);
    if (! $row) {
        return ['success' => false, 'message' => 'Consent not found.'];
    }

    $status = (string) ($row['consent_status'] ?? '');
    if ($status !== 'completed') {
        return ['success' => false, 'message' => 'Only completed consents can be superseded. Use withdraw for in-progress forms.'];
    }

    if (strlen(trim($reason)) < 5) {
        return ['success' => false, 'message' => 'Reason for replacement is required.'];
    }

    $formRef = wpu_medical_consent_form_reference();
    $templateVersion = (string) (wpu_medical_consent_config()['template_version'] ?? $formRef);

    $stmt = $pdo->prepare(
        'INSERT INTO healthhub_consents
        (patient_record_id, patient_name, patient_identifier, patient_age, date_of_birth, patient_type_label,
         consent_pathway, guardian_name, named_patient, purpose, consent_status, consent_version, form_reference,
         superseded_from_id, created_by, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $stmt->execute([
        $row['patient_record_id'] ?? null,
        $row['patient_name'] ?? '',
        $row['patient_identifier'] ?? null,
        $row['patient_age'] ?? null,
        $row['date_of_birth'] ?? null,
        $row['patient_type_label'] ?? null,
        $row['consent_pathway'] ?? 'adult',
        $row['guardian_name'] ?? null,
        $row['named_patient'] ?? null,
        $row['purpose'] ?? '',
        'awaiting_patient_signature',
        $templateVersion,
        $formRef,
        $consentId,
        $actor,
    ]);
    $newId = (int) $pdo->lastInsertId();

    $upd = $pdo->prepare(
        'UPDATE healthhub_consents SET consent_status = ?, superseded_by_id = ?, updated_at = NOW() WHERE id = ?'
    );
    $upd->execute(['superseded', $newId, $consentId]);

    wpu_medical_consent_save_version($pdo, $consentId, 'superseded_snapshot', $row, $actor, $newId, $reason);

    wpu_medical_consent_audit($pdo, $consentId, 'superseded', $actor, 'Replaced by #'.$newId.': '.$reason);
    wpu_medical_consent_audit($pdo, $newId, 'created_from_supersede', $actor, 'Supersedes #'.$consentId);
    wpu_security_audit($pdo, 'consent_superseded', '#'.$consentId.' -> #'.$newId);

    return [
        'success' => true,
        'message' => 'New consent #'.$newId.' created. Complete signatures on the replacement form.',
        'new_consent_id' => $newId,
    ];
}

/**
 * Link consent ↔ visit after save.
 */
function wpu_medical_consent_attach_to_visit(PDO $pdo, int $consentId, int $patientRecordId): void
{
    wpu_medical_consent_link_patient_record($pdo, $consentId, $patientRecordId);
    try {
        $stmt = $pdo->prepare('UPDATE healthhub_consents SET patient_record_id = ? WHERE id = ?');
        $stmt->execute([$patientRecordId, $consentId]);
    } catch (PDOException) {
        // ignore
    }
}

function wpu_medical_consent_format_signed_date(?string $datetime): string
{
    if ($datetime === null || trim($datetime) === '') {
        return '';
    }
    $ts = strtotime($datetime);

    return $ts !== false ? date('F j, Y', $ts) : '';
}

/**
 * Build view/DOCX variables from a consent row.
 *
 * @param  array<string, mixed>  $row
 * @return array<string, mixed>
 */
function wpu_medical_consent_print_context(array $row): array
{
    $relationship = (string) ($row['representative_relationship'] ?? $row['signed_capacity'] ?? '');

    return [
        'mc_treatment' => (string) ($row['purpose'] ?? ''),
        'mc_named_patient' => (string) ($row['named_patient'] ?? ''),
        'mc_patient_sig' => wpu_medical_consent_signature_uri($row, 'signature_storage_path'),
        'mc_patient_name' => (string) ($row['signed_by_name'] ?? ''),
        'mc_patient_relationship' => $relationship,
        'mc_patient_date' => wpu_medical_consent_format_signed_date(isset($row['signed_at']) ? (string) $row['signed_at'] : null),
        'mc_interpreter_sig' => wpu_medical_consent_signature_uri($row, 'interpreter_signature_path', ''),
        'mc_interpreter_name' => (string) ($row['interpreter_name'] ?? ''),
        'mc_interpreter_date' => wpu_medical_consent_format_signed_date(isset($row['interpreter_signed_at']) ? (string) $row['interpreter_signed_at'] : null),
        'mc_witness_sig' => wpu_medical_consent_signature_uri($row, 'witness_signature_path', ''),
        'mc_witness_name' => (string) ($row['witness_name'] ?? ''),
        'mc_witness_date' => wpu_medical_consent_format_signed_date(isset($row['witness_signed_at']) ? (string) $row['witness_signed_at'] : null),
        'mc_physician_sig' => wpu_medical_consent_signature_uri($row, 'physician_signature_path', ''),
        'mc_physician_printed' => (string) ($row['physician_printed_name'] ?? ''),
        'mc_physician_cert_date' => wpu_medical_consent_format_signed_date(isset($row['physician_certified_at']) ? (string) $row['physician_certified_at'] : null),
        'mc_physician' => wpu_medical_consent_advising_physician(),
    ];
}

function wpu_medical_consent_docx_template_path(): ?string
{
    $cfg = wpu_medical_consent_config();
    $relative = (string) ($cfg['docx_template'] ?? 'assets/templates/MEDICAL_CONSENT_OFFICIAL.docx');
    $candidates = [
        dirname(__DIR__).'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative),
        dirname(__DIR__, 2).'/unified_portal/'.str_replace('/', DIRECTORY_SEPARATOR, $relative),
        dirname(__DIR__, 2).'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative),
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }

    return null;
}
