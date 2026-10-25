<?php

declare(strict_types=1);

require_once __DIR__.'/../../includes/wpu_medical_consent.php';

/**
 * @param  array<string, mixed>  $post
 * @return array{ok: true, consent_id: int}|array{ok: false, response: array<string, mixed>}
 */
function wpu_record_consent_gate_from_post(PDO $pdo, string $moduleType, array $post, string $role, ?array $existingRecord = null): array
{
    $params = [
        'module_type' => $moduleType,
        'transaction_type' => (string) ($post['transaction_type'] ?? ''),
        'treatment' => (string) ($post['treatment'] ?? ''),
        'student_id' => (string) ($post['student_id'] ?? ''),
        'full_name' => (string) ($post['full_name'] ?? ''),
        'consent_record_id' => (int) ($post['consent_record_id'] ?? 0),
        'existing_consent_record_id' => (int) ($existingRecord['consent_record_id'] ?? 0),
        'patient_record_id' => (int) ($existingRecord['id'] ?? $post['record_id'] ?? 0),
        'role' => $role,
        'consent_emergency_bypass' => (string) ($post['consent_emergency_bypass'] ?? ''),
        'consent_bypass_reason' => (string) ($post['consent_bypass_reason'] ?? ''),
    ];

    $check = wpu_medical_consent_validate_for_visit($pdo, $params);
    if (! $check['ok']) {
        $response = [
            'success' => false,
            'message' => $check['message'],
        ];
        if (! empty($check['consent_required'])) {
            $response['consent_required'] = true;
        }
        if (! empty($check['consent_start_url'])) {
            $response['consent_start_url'] = $check['consent_start_url'];
        }

        return ['ok' => false, 'response' => $response];
    }

    $consentId = (int) ($check['consent_id'] ?? $params['consent_record_id'] ?? 0);

    return ['ok' => true, 'consent_id' => $consentId];
}

function wpu_record_apply_consent_metadata(PDO $pdo, int $recordId, int $consentId, string $transactionType): void
{
    if ($consentId > 0) {
        wpu_medical_consent_attach_to_visit($pdo, $consentId, $recordId);
    }

    if ($transactionType !== '' && wpu_schema_has_column($pdo, 'patient_records', 'transaction_type')) {
        try {
            $stmt = $pdo->prepare('UPDATE patient_records SET transaction_type = ? WHERE id = ?');
            $stmt->execute([$transactionType, $recordId]);
        } catch (PDOException) {
            // ignore
        }
    }
}
