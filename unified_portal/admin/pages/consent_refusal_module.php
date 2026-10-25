<?php
/**
 * Consent & Refusal — unified HealthHub module (informed consent + refusal documentation).
 */
require_once __DIR__.'/../../includes/wpu_consent_refusal_module.php';

$pdo = $pdo ?? null;
$role = ($pdo instanceof PDO) ? wpu_healthhub_admin_role($pdo) : 'guest';
$prefillRecordId = (int) ($_GET['patient_record_id'] ?? 0);

$recent = ($pdo instanceof PDO) ? wpu_consent_refusal_module_merged_recent($pdo, 12) : [];

$consentRef = wpu_medical_consent_form_reference();
$refusalRef = wpu_refusal_consent_form_reference();
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($his_assets ?? '../assets', ENT_QUOTES, 'UTF-8'); ?>/css/medical-consent.css?v=1">

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-file-contract" aria-hidden="true"></i> Consent &amp; Refusal</h2>
        <p class="card-subtitle">Official WPU Health Services Office informed consent and refusal of consent workflows.</p>
    </div>
    <div class="card-body">
        <?php include __DIR__.'/../partials/consent_refusal_module_nav.php'; ?>

        <div class="settings-form-grid-2" style="margin-top:0.5rem">
            <div class="content-card" style="margin:0;border:1px solid var(--his-border,#e2e8f0)">
                <div class="card-body">
                    <h3 class="card-title" style="font-size:1.05rem"><i class="fas fa-file-signature" aria-hidden="true"></i> Medical consent</h3>
                    <p class="form-helper"><?php echo htmlspecialchars($consentRef, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="form-helper">Capture informed consent before applicable treatments, procedures, diagnostics, medications, or referrals.</p>
                    <div class="settings-form-actions" style="margin-top:1rem;flex-wrap:wrap;gap:0.5rem">
                        <a class="btn btn-success" href="?page=secure_consent<?php echo $prefillRecordId > 0 ? '&amp;patient_record_id='.$prefillRecordId : ''; ?>">Start consent form</a>
                        <a class="btn btn-secondary" href="?page=view_consent">View consent records</a>
                    </div>
                </div>
            </div>
            <div class="content-card" style="margin:0;border:1px solid var(--his-border,#e2e8f0)">
                <div class="card-body">
                    <h3 class="card-title" style="font-size:1.05rem"><i class="fas fa-hand-paper" aria-hidden="true"></i> Refusal of consent</h3>
                    <p class="form-helper"><?php echo htmlspecialchars($refusalRef, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="form-helper">Document a signed refusal for a specific service. Does not block unrelated future care or emergencies.</p>
                    <div class="settings-form-actions" style="margin-top:1rem;flex-wrap:wrap;gap:0.5rem">
                        <a class="btn btn-success" href="?page=consent_refusal<?php echo $prefillRecordId > 0 ? '&amp;patient_record_id='.$prefillRecordId : ''; ?>">Start refusal form</a>
                        <a class="btn btn-secondary" href="?page=view_refusal">View refusal records</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($prefillRecordId > 0): ?>
            <p class="form-helper" style="margin-top:1rem">Prefill from visit / record #<?php echo (int) $prefillRecordId; ?> is applied when you open a new form.</p>
        <?php endif; ?>

        <h3 style="margin-top:2rem;font-size:1rem">Recent activity</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Treatment / declined service</th>
                    <th>Status</th>
                    <th>Last activity</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <?php
                    $kind = (string) $row['kind'];
                    $openPage = $kind === 'consent' ? 'view_consent' : 'view_refusal';
                    $typeLabel = $kind === 'consent' ? 'Consent' : 'Refusal';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) $row['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['summary'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(wpu_consent_refusal_module_status_label($kind, (string) $row['status']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['when'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a class="btn btn-sm btn-secondary" href="?page=<?php echo $openPage; ?>&amp;id=<?php echo (int) $row['id']; ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recent === []): ?>
                    <tr><td colspan="7">No consent or refusal records yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
