<?php
require_once __DIR__.'/../../includes/wpu_medical_consent.php';

$pdo = $pdo ?? null;
$role = ($pdo instanceof PDO) ? wpu_healthhub_admin_role($pdo) : 'guest';
$consentId = (int) ($_GET['id'] ?? 0);
$consentRow = null;
$auditRows = [];
$versionRows = [];

if ($pdo instanceof PDO && $consentId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consents WHERE id = ? LIMIT 1');
    $stmt->execute([$consentId]);
    $consentRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($consentRow) {
        $consentRow = wpu_medical_consent_filter_row($consentRow, $role);
    }
    if ($consentRow && wpu_schema_has_column($pdo, 'healthhub_consent_audit_log', 'id')) {
        $a = $pdo->prepare('SELECT * FROM healthhub_consent_audit_log WHERE consent_id = ? ORDER BY created_at DESC LIMIT 25');
        $a->execute([$consentId]);
        $auditRows = $a->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    if ($consentRow && wpu_schema_has_column($pdo, 'healthhub_consent_versions', 'id')) {
        $v = $pdo->prepare('SELECT * FROM healthhub_consent_versions WHERE consent_id = ? ORDER BY created_at DESC');
        $v->execute([$consentId]);
        $versionRows = $v->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$recent = [];
if ($pdo instanceof PDO && $consentId === 0) {
    $stmt = $pdo->query(
        'SELECT id, patient_name, purpose, consent_status, signed_at, physician_certified_at, created_at
         FROM healthhub_consents ORDER BY id DESC LIMIT 40'
    );
    if ($stmt) {
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$consent_process_url = 'components/process_consent.php';
if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('route')) {
    $workspaceRoute = defined('WPU_LARAVEL_WORKSPACE_ROUTE') && WPU_LARAVEL_WORKSPACE_ROUTE !== ''
        ? (string) WPU_LARAVEL_WORKSPACE_ROUTE
        : 'admin.workspace';
    $consent_process_url = route($workspaceRoute, ['path' => 'admin/components/process_consent.php']);
}

$canCertify = wpu_medical_consent_can_certify($role);
$canViewSigs = wpu_medical_consent_can_view_full($role);
$canManageLifecycle = wpu_medical_consent_can_manage_lifecycle($role);
$his_assets_path = htmlspecialchars($his_assets ?? '../assets', ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $his_assets_path; ?>/css/medical-consent.css?v=1">

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-folder-open" aria-hidden="true"></i> Consent records</h2>
        <p class="card-subtitle"><a href="?page=consent_refusal_module">Consent &amp; Refusal module</a></p>
    </div>
    <div class="card-body">
        <?php include __DIR__.'/../partials/consent_refusal_module_nav.php'; ?>
        <?php if ($consentId === 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Treatment / procedure</th>
                        <th>Status</th>
                        <th>Signed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                        <?php
                        $st = (string) ($row['consent_status'] ?? 'awaiting_patient_signature');
                        $badgeClass = match ($st) {
                            'completed' => 'is-completed',
                            'withdrawn', 'superseded' => 'is-withdrawn',
                            'draft', 'awaiting_patient_signature' => 'is-draft',
                            default => 'is-awaiting',
                        };
                        ?>
                        <tr>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) $row['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($row['purpose'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="consent-status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(wpu_medical_consent_status_label($st), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo ! empty($row['signed_at']) ? htmlspecialchars((string) $row['signed_at'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td><a class="btn btn-sm btn-secondary" href="?page=view_consent&amp;id=<?php echo (int) $row['id']; ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recent === []): ?>
                        <tr><td colspan="6">No consent records yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif (! $consentRow): ?>
            <div class="alert alert-error">Consent record not found.</div>
        <?php else: ?>
            <?php
            $st = (string) ($consentRow['consent_status'] ?? '');
            $badgeClass = match ($st) {
                'completed' => 'is-completed',
                'withdrawn', 'superseded' => 'is-withdrawn',
                'draft', 'awaiting_patient_signature' => 'is-draft',
                default => 'is-awaiting',
            };
            ?>
            <p>
                <strong><?php echo htmlspecialchars((string) $consentRow['patient_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="consent-status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(wpu_medical_consent_status_label($st), ENT_QUOTES, 'UTF-8'); ?></span>
            </p>
            <p class="form-helper">
                Record #<?php echo (int) $consentId; ?>
                <?php if (! empty($consentRow['patient_record_id'])): ?>
                    · Visit #<?php echo (int) $consentRow['patient_record_id']; ?>
                <?php endif; ?>
                · <a href="print_medical_consent.php?id=<?php echo (int) $consentId; ?>&amp;format=docx" download="Medical-Consent-<?php echo (int) $consentId; ?>.docx">Download official DOCX</a>
                · <a href="print_medical_consent.php?id=<?php echo (int) $consentId; ?>" target="_blank" rel="noopener">Print preview</a>
            </p>

            <div class="consent-official-preview">
                <?php
                $mc_treatment = (string) ($consentRow['purpose'] ?? '');
                $mc_named_patient = (string) ($consentRow['named_patient'] ?? '');
                $mc_logo_url = ($his_assets ?? '../assets').'/images/logo.png';
                if ($canViewSigs) {
                    extract(wpu_medical_consent_print_context($consentRow), EXTR_SKIP);
                }
                include __DIR__.'/../partials/medical_consent_document.php';
                ?>
            </div>

            <?php if ($st === 'awaiting_physician_certification' && $canCertify): ?>
                <hr style="margin:2rem 0">
                <h3>Physician certification</h3>
                <p class="form-helper">Only an authorized physician may sign the certification below. This records your authenticated account (<?php echo htmlspecialchars((string) ($_SESSION['admin_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>).</p>
                <form id="healthhubPhysicianCertForm" method="post" action="<?php echo htmlspecialchars($consent_process_url, ENT_QUOTES, 'UTF-8'); ?>" class="settings-form-grid-2 no-validate">
                    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="physician_certify">
                    <input type="hidden" name="consent_id" value="<?php echo (int) $consentId; ?>">
                    <input type="hidden" name="physician_signature_data" id="physician_signature_data">
                    <div class="form-group full-width">
                        <label for="physician_printed_name">Physician printed name *</label>
                        <input type="text" class="form-control" id="physician_printed_name" name="physician_printed_name" required maxlength="255" value="<?php echo htmlspecialchars(wpu_medical_consent_advising_physician(), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>Physician signature *</label>
                        <div class="consent-sig-pad-wrap" data-healthhub-signature='{"canvasId":"physicianSignaturePad","hiddenId":"physician_signature_data","clearId":"physicianClearSig","redoId":"physicianRedoSig","formId":"healthhubPhysicianCertForm"}'>
                            <canvas id="physicianSignaturePad" width="520" height="160"></canvas>
                            <div class="consent-sig-actions">
                                <button type="button" class="btn btn-secondary btn-sm" id="physicianClearSig">Clear</button>
                                <button type="button" class="btn btn-secondary btn-sm" id="physicianRedoSig">Redo</button>
                            </div>
                        </div>
                    </div>
                    <div class="settings-form-actions full-width">
                        <button type="submit" class="btn btn-success">Complete physician certification</button>
                    </div>
                </form>
            <?php elseif ($st === 'awaiting_physician_certification'): ?>
                <div class="alert alert-warning">This consent is pending physician certification.</div>
            <?php endif; ?>

            <?php
            $supFrom = (int) ($consentRow['superseded_from_id'] ?? 0);
            $supBy = (int) ($consentRow['superseded_by_id'] ?? 0);
            if ($supFrom > 0 || $supBy > 0): ?>
                <p class="form-helper">
                    <?php if ($supFrom > 0): ?>Replaces consent #<?php echo $supFrom; ?>. <?php endif; ?>
                    <?php if ($supBy > 0): ?>Superseded by <a href="?page=view_consent&amp;id=<?php echo $supBy; ?>">#<?php echo $supBy; ?></a>.<?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($canManageLifecycle && ! in_array($st, ['withdrawn', 'superseded'], true)): ?>
                <hr style="margin:2rem 0">
                <h3>Consent lifecycle</h3>
                <p class="form-helper">Withdrawal and replacement preserve the original signed record in version history.</p>
                <div class="settings-form-grid-2">
                    <form id="consentWithdrawForm" class="full-width" style="grid-column:1/-1" method="post" action="<?php echo htmlspecialchars($consent_process_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="withdraw_consent">
                        <input type="hidden" name="consent_id" value="<?php echo (int) $consentId; ?>">
                        <div class="form-group full-width">
                            <label for="withdrawal_reason">Withdraw consent</label>
                            <textarea id="withdrawal_reason" name="withdrawal_reason" class="form-control" rows="2" required placeholder="Reason for withdrawal"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">Withdraw consent</button>
                    </form>
                    <?php if ($st === 'completed'): ?>
                    <form id="consentSupersedeForm" class="full-width" style="grid-column:1/-1;margin-top:1rem" method="post" action="<?php echo htmlspecialchars($consent_process_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="supersede_consent">
                        <input type="hidden" name="consent_id" value="<?php echo (int) $consentId; ?>">
                        <div class="form-group full-width">
                            <label for="supersede_reason">Replace with new consent (supersede)</label>
                            <textarea id="supersede_reason" name="supersede_reason" class="form-control" rows="2" required placeholder="Reason correction or new procedure requires new consent"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm">Create replacement consent</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($st === 'withdrawn' && ! empty($consentRow['withdrawal_reason'])): ?>
                <div class="alert alert-error">Withdrawn: <?php echo htmlspecialchars((string) $consentRow['withdrawal_reason'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($versionRows !== [] && wpu_medical_consent_can_view_full($role)): ?>
                <h3 style="margin-top:2rem">Version history</h3>
                <ul class="form-helper">
                    <?php foreach ($versionRows as $ver): ?>
                        <li>
                            <?php echo htmlspecialchars((string) ($ver['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            — <?php echo htmlspecialchars((string) ($ver['version_kind'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (! empty($ver['replaced_by_consent_id'])): ?>
                                → #<?php echo (int) $ver['replaced_by_consent_id']; ?>
                            <?php endif; ?>
                            <?php if (! empty($ver['reason'])): ?>
                                (<?php echo htmlspecialchars((string) $ver['reason'], ENT_QUOTES, 'UTF-8'); ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($auditRows !== [] && wpu_medical_consent_can_view_full($role)): ?>
                <h3 style="margin-top:2rem">Audit history</h3>
                <ul class="form-helper">
                    <?php foreach ($auditRows as $log): ?>
                        <li><?php echo htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars((string) ($log['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string) ($log['actor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<script src="<?php echo $his_assets_path; ?>/js/healthhub-signature.js?v=5"></script>
<script>
(function () {
    function bindLifecycleForm(formId) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm('This action is recorded in the audit trail. Continue?')) return;
            fetch(form.getAttribute('action') || '<?php echo htmlspecialchars($consent_process_url, ENT_QUOTES, 'UTF-8'); ?>', {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (typeof AlertSystem !== 'undefined') {
                        AlertSystem.show(data.message || 'Done.', data.success ? 'success' : 'error');
                    }
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(function () {
                    if (typeof AlertSystem !== 'undefined') {
                        AlertSystem.show('Request failed.', 'error');
                    }
                });
        });
    }
    bindLifecycleForm('consentWithdrawForm');
    bindLifecycleForm('consentSupersedeForm');
    ['consentWithdrawForm', 'consentSupersedeForm'].forEach(function (id) {
        var f = document.getElementById(id);
        if (f && !f.getAttribute('action')) {
            f.setAttribute('action', '<?php echo htmlspecialchars($consent_process_url, ENT_QUOTES, 'UTF-8'); ?>');
        }
    });
})();
</script>
