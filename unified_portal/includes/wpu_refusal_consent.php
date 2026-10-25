<?php

declare(strict_types=1);

if (defined('WPU_REFUSAL_CONSENT_LOADED')) {
    return;
}
define('WPU_REFUSAL_CONSENT_LOADED', true);

require_once __DIR__.'/wpu_medical_consent.php';

/**
 * @return array<string, mixed>
 */
function wpu_refusal_consent_config(): array
{
    if (function_exists('config')) {
        return config('refusal_consent', []);
    }

    $path = __DIR__.'/../../config/refusal_consent.php';
    if (is_readable($path)) {
        return require $path;
    }

    return [];
}

function wpu_refusal_consent_form_reference(): string
{
    $cfg = wpu_refusal_consent_config();

    return (string) ($cfg['form_reference'] ?? 'WPU-QSF-OSAS-HSO-31 Rev. 00 (08.15.25)');
}

/**
 * Page-1 advising physician — entered manually on the refusal form (REFUSAL.docx Dr. line).
 *
 * @param  array<string, mixed>  $row
 */
function wpu_refusal_consent_advising_physician_for_record(array $row): string
{
    return trim((string) ($row['advising_physician'] ?? ''));
}

/** @return list<string> */
function wpu_refusal_consent_statuses(): array
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

function wpu_refusal_consent_can_view_full(string $role): bool
{
    return wpu_medical_consent_can_view_full($role);
}

function wpu_refusal_consent_can_certify(string $role): bool
{
    return wpu_medical_consent_can_certify($role);
}

function wpu_refusal_consent_can_create(string $role): bool
{
    return wpu_medical_consent_can_create($role);
}

function wpu_refusal_consent_can_manage_lifecycle(string $role): bool
{
    return wpu_medical_consent_can_manage_lifecycle($role);
}

function wpu_refusal_consent_storage_root(): string
{
    if (function_exists('storage_path')) {
        return storage_path('app/private/healthhub_refusals');
    }

    return dirname(__DIR__).'/storage/private/healthhub_refusals';
}

function wpu_refusal_consent_store_signature(int $refusalId, string $kind, string $dataUrl): ?string
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
    $dir = wpu_refusal_consent_storage_root().DIRECTORY_SEPARATOR.$refusalId;
    if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
        return null;
    }

    $filename = $kind.'_'.bin2hex(random_bytes(8)).'.png';
    $full = $dir.DIRECTORY_SEPARATOR.$filename;
    if (file_put_contents($full, $binary) === false) {
        return null;
    }

    return $refusalId.'/'.$filename;
}

function wpu_refusal_consent_signature_full_path(string $relativePath): ?string
{
    $relativePath = str_replace(['..', '\\'], ['', '/'], $relativePath);
    $full = wpu_refusal_consent_storage_root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (! is_readable($full)) {
        return null;
    }

    return $full;
}

/**
 * @param  array<string, mixed>  $row
 */
function wpu_refusal_consent_signature_uri(array $row, string $pathField, string $legacyField = 'signature_data'): ?string
{
    $relative = (string) ($row[$pathField] ?? '');
    if ($relative !== '') {
        $full = wpu_refusal_consent_signature_full_path($relative);
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

function wpu_refusal_consent_audit(PDO $pdo, int $refusalId, string $action, string $actor, string $details = ''): void
{
    if (! wpu_schema_has_column($pdo, 'healthhub_refusal_audit_log', 'id')) {
        wpu_security_audit($pdo, 'refusal_'.$action, 'Refusal #'.$refusalId.': '.$details);

        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO healthhub_refusal_audit_log (refusal_id, action, actor, details, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$refusalId, $action, $actor, $details]);
    } catch (PDOException) {
        wpu_security_audit($pdo, 'refusal_'.$action, 'Refusal #'.$refusalId.': '.$details);
    }
}

/**
 * @param  array<string, mixed>  $row
 * @return array<string, mixed>
 */
function wpu_refusal_consent_filter_row(array $row, string $role): array
{
    if (wpu_refusal_consent_can_view_full($role)) {
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

function wpu_refusal_consent_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Draft',
        'awaiting_patient_signature' => 'Awaiting patient / representative signature',
        'awaiting_physician_certification' => 'Awaiting required attestation',
        'completed' => 'Completed',
        'withdrawn' => 'Withdrawn',
        'superseded' => 'Superseded',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

/**
 * @param  array<string, mixed>  $row
 */
function wpu_refusal_consent_is_completed(array $row): bool
{
    $status = (string) ($row['refusal_status'] ?? '');
    if ($status === 'completed') {
        return true;
    }
    if ($status === '' && ! empty($row['signed_at']) && ! empty($row['physician_certified_at'])) {
        return true;
    }

    return false;
}

/**
 * @return array<string, mixed>|null
 */
function wpu_refusal_consent_get(PDO $pdo, int $refusalId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consent_refusals WHERE id = ? LIMIT 1');
    $stmt->execute([$refusalId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * @param  array<string, mixed>  $row
 */
function wpu_refusal_consent_encode_snapshot(array $row): string
{
    $copy = $row;
    if (isset($copy['signature_data']) && is_string($copy['signature_data']) && strlen($copy['signature_data']) > 256) {
        $copy['signature_data'] = '[redacted:stored_on_disk]';
    }

    return json_encode($copy, JSON_UNESCAPED_UNICODE) ?: '{}';
}

function wpu_refusal_consent_save_version(
    PDO $pdo,
    int $refusalId,
    string $kind,
    array $snapshotRow,
    string $actor,
    ?int $replacedBy = null,
    ?string $reason = null
): void {
    if (! wpu_schema_has_column($pdo, 'healthhub_refusal_versions', 'id')) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO healthhub_refusal_versions
            (refusal_id, version_kind, snapshot_json, reason, replaced_by_refusal_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $refusalId,
            $kind,
            wpu_refusal_consent_encode_snapshot($snapshotRow),
            $reason,
            $replacedBy,
            $actor,
        ]);
    } catch (PDOException) {
        // ignore
    }
}

/**
 * @return array{success: bool, message: string}
 */
function wpu_refusal_consent_withdraw(PDO $pdo, int $refusalId, string $actor, string $reason, string $role): array
{
    if (! wpu_refusal_consent_can_manage_lifecycle($role)) {
        return ['success' => false, 'message' => 'You cannot withdraw refusal records.'];
    }

    $row = wpu_refusal_consent_get($pdo, $refusalId);
    if (! $row) {
        return ['success' => false, 'message' => 'Refusal not found.'];
    }

    $status = (string) ($row['refusal_status'] ?? '');
    if (in_array($status, ['withdrawn', 'superseded'], true)) {
        return ['success' => false, 'message' => 'This refusal is already inactive.'];
    }

    if (strlen(trim($reason)) < 5) {
        return ['success' => false, 'message' => 'Withdrawal reason is required.'];
    }

    wpu_refusal_consent_save_version($pdo, $refusalId, 'withdrawn_snapshot', $row, $actor, null, $reason);

    $stmt = $pdo->prepare(
        'UPDATE healthhub_consent_refusals SET refusal_status = ?, withdrawn_at = NOW(), withdrawn_by = ?,
         withdrawal_reason = ?, updated_at = NOW() WHERE id = ?'
    );
    $stmt->execute(['withdrawn', $actor, $reason, $refusalId]);

    wpu_refusal_consent_audit($pdo, $refusalId, 'withdrawn', $actor, $reason);
    wpu_security_audit($pdo, 'refusal_withdrawn', 'Refusal #'.$refusalId.': '.$reason);

    return ['success' => true, 'message' => 'Refusal withdrawn. Original record preserved in version history.'];
}

/**
 * @param  array<string, mixed>  $row
 * @return array<string, mixed>
 */
function wpu_refusal_consent_print_context(array $row): array
{
    $relationship = (string) ($row['representative_relationship'] ?? $row['signed_capacity'] ?? '');
    $procedure = (string) ($row['declined_procedure'] ?? '');

    return [
        'rc_treatment' => $procedure,
        'rc_named_patient' => (string) ($row['named_patient'] ?? ''),
        'rc_physician' => wpu_refusal_consent_advising_physician_for_record($row),
        'rc_patient_sig' => wpu_refusal_consent_signature_uri($row, 'signature_storage_path'),
        'rc_patient_name' => (string) ($row['signed_by_name'] ?? ''),
        'rc_patient_relationship' => $relationship,
        'rc_patient_date' => wpu_medical_consent_format_signed_date(isset($row['signed_at']) ? (string) $row['signed_at'] : null),
        'rc_interpreter_sig' => wpu_refusal_consent_signature_uri($row, 'interpreter_signature_path', ''),
        'rc_interpreter_name' => (string) ($row['interpreter_name'] ?? ''),
        'rc_interpreter_date' => wpu_medical_consent_format_signed_date(isset($row['interpreter_signed_at']) ? (string) $row['interpreter_signed_at'] : null),
        'rc_witness_sig' => wpu_refusal_consent_signature_uri($row, 'witness_signature_path', ''),
        'rc_witness_name' => (string) ($row['witness_name'] ?? ''),
        'rc_witness_date' => wpu_medical_consent_format_signed_date(isset($row['witness_signed_at']) ? (string) $row['witness_signed_at'] : null),
        'rc_physician_sig' => wpu_refusal_consent_signature_uri($row, 'physician_signature_path', ''),
        'rc_physician_printed' => (string) ($row['physician_printed_name'] ?? ''),
        'rc_physician_cert_date' => wpu_medical_consent_format_signed_date(isset($row['physician_certified_at']) ? (string) $row['physician_certified_at'] : null),
    ];
}

function wpu_refusal_consent_docx_template_path(): ?string
{
    $cfg = wpu_refusal_consent_config();
    $relative = (string) ($cfg['docx_template'] ?? 'assets/templates/REFUSAL_OFFICIAL.docx');
    $candidates = [
        dirname(__DIR__).'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative),
        dirname(__DIR__, 2).'/unified_portal/'.str_replace('/', DIRECTORY_SEPARATOR, $relative),
        dirname(__DIR__, 2).'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative),
        dirname(__DIR__, 2).'/docs/REFUSAL.docx',
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }

    return null;
}
