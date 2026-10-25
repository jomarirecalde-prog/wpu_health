<?php

declare(strict_types=1);

if (defined('WPU_CONSENT_REFUSAL_MODULE_LOADED')) {
    return;
}
define('WPU_CONSENT_REFUSAL_MODULE_LOADED', true);

require_once __DIR__.'/wpu_medical_consent.php';
require_once __DIR__.'/wpu_refusal_consent.php';

/** Workspace page keys that belong to the Consent & Refusal module. */
function wpu_consent_refusal_module_page_keys(): array
{
    return [
        'consent_refusal_module',
        'secure_consent',
        'view_consent',
        'consent_refusal',
        'view_refusal',
    ];
}

function wpu_consent_refusal_module_is_page(string $page): bool
{
    $page = strtolower(trim($page));

    return in_array($page, wpu_consent_refusal_module_page_keys(), true);
}

function wpu_consent_refusal_module_can_access(string $role): bool
{
    if (! function_exists('wpu_healthhub_can_access_workspace_page')) {
        require_once __DIR__.'/wpu_healthhub.php';
    }

    return wpu_healthhub_can_access_workspace_page($role, 'consent_refusal_module');
}

/**
 * @return list<array<string, mixed>>
 */
function wpu_consent_refusal_module_recent_consents(PDO $pdo, int $limit = 8): array
{
    if (! wpu_schema_has_column($pdo, 'healthhub_consents', 'id')) {
        return [];
    }

    try {
        $stmt = $pdo->query(
            'SELECT id, patient_name, purpose, consent_status, signed_at, created_at
             FROM healthhub_consents ORDER BY COALESCE(signed_at, created_at) DESC LIMIT '.(int) max(1, min($limit, 25))
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException) {
        return [];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function wpu_consent_refusal_module_recent_refusals(PDO $pdo, int $limit = 8): array
{
    if (! wpu_schema_has_column($pdo, 'healthhub_consent_refusals', 'id')) {
        return [];
    }

    try {
        $stmt = $pdo->query(
            'SELECT id, patient_name, declined_procedure, refusal_status, signed_at, created_at
             FROM healthhub_consent_refusals ORDER BY COALESCE(signed_at, created_at) DESC LIMIT '.(int) max(1, min($limit, 25))
        );

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (PDOException) {
        return [];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function wpu_consent_refusal_module_merged_recent(PDO $pdo, int $limit = 12): array
{
    $rows = [];
    foreach (wpu_consent_refusal_module_recent_consents($pdo, $limit) as $r) {
        $rows[] = [
            'kind' => 'consent',
            'id' => (int) $r['id'],
            'patient_name' => (string) ($r['patient_name'] ?? ''),
            'summary' => (string) ($r['purpose'] ?? ''),
            'status' => (string) ($r['consent_status'] ?? ''),
            'when' => (string) ($r['signed_at'] ?? $r['created_at'] ?? ''),
        ];
    }
    foreach (wpu_consent_refusal_module_recent_refusals($pdo, $limit) as $r) {
        $status = (string) ($r['refusal_status'] ?? '');
        if ($status === '' && ! empty($r['signed_at'])) {
            $status = 'signed';
        }
        $rows[] = [
            'kind' => 'refusal',
            'id' => (int) $r['id'],
            'patient_name' => (string) ($r['patient_name'] ?? ''),
            'summary' => (string) ($r['declined_procedure'] ?? ''),
            'status' => $status,
            'when' => (string) ($r['signed_at'] ?? $r['created_at'] ?? ''),
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        return strcmp($b['when'], $a['when']);
    });

    return array_slice($rows, 0, $limit);
}

function wpu_consent_refusal_module_status_label(string $kind, string $status): string
{
    if ($kind === 'refusal') {
        return wpu_refusal_consent_status_label($status !== '' ? $status : 'awaiting_patient_signature');
    }

    return wpu_medical_consent_status_label($status !== '' ? $status : 'awaiting_patient_signature');
}
