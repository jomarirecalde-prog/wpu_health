<?php
require_once __DIR__.'/../../includes/wpu_refusal_consent.php';

$pdo = $pdo ?? null;
$role = ($pdo instanceof PDO) ? wpu_healthhub_admin_role($pdo) : 'guest';
$refusalId = (int) ($_GET['id'] ?? 0);
$refusalRow = null;
$auditRows = [];
$versionRows = [];

if ($pdo instanceof PDO && $refusalId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consent_refusals WHERE id = ? LIMIT 1');
    $stmt->execute([$refusalId]);
    $refusalRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($refusalRow) {
        $refusalRow = wpu_refusal_consent_filter_row($refusalRow, $role);
    }
    if ($refusalRow && wpu_schema_has_column($pdo, 'healthhub_refusal_audit_log', 'id')) {
        $a = $pdo->prepare('SELECT * FROM healthhub_refusal_audit_log WHERE refusal_id = ? ORDER BY created_at DESC LIMIT 25');
        $a->execute([$refusalId]);
        $auditRows = $a->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    if ($refusalRow && wpu_schema_has_column($pdo, 'healthhub_refusal_versions', 'id')) {
        $v = $pdo->prepare('SELECT * FROM healthhub_refusal_versions WHERE refusal_id = ? ORDER BY created_at DESC');
        $v->execute([$refusalId]);
        $versionRows = $v->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$recent = [];
if ($pdo instanceof PDO && $refusalId === 0) {
    $stmt = $pdo->query(
        'SELECT id, patient_name, declined_procedure, refusal_status, signed_at, physician_certified_at, created_at
         FROM healthhub_consent_refusals ORDER BY id DESC LIMIT 40'
    );
    if ($stmt) {
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$refusal_process_url = 'components/process_consent_refusal.php';
if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('route')) {
    $workspaceRoute = defined('WPU_LARAVEL_WORKSPACE_ROUTE') && WPU_LARAVEL_WORKSPACE_ROUTE !== ''
        ? (string) WPU_LARAVEL_WORKSPACE_ROUTE
        : 'admin.workspace';
    $refusal_process_url = route($workspaceRoute, ['path' => 'admin/components/process_consent_refusal.php']);
}

$canCertify = wpu_refusal_consent_can_certify($role);
$canViewSigs = wpu_refusal_consent_can_view_full($role);
$canManageLifecycle = wpu_refusal_consent_can_manage_lifecycle($role);
$his_assets_path = htmlspecialchars($his_assets ?? '../assets', ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $his_assets_path; ?>/css/medical-consent.css?v=1">

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-folder-open" aria-hidden="true"></i> Refusal records</h2>
        <p class="card-subtitle"><a href="?page=consent_refusal_module">Consent &amp; Refusal module</a></p>
    </div>
    <div class="card-body">
        <?php include __DIR__.'/../partials/consent_refusal_module_nav.php'; ?>
        <?php if ($refusalId === 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Declined service</th>
                        <th>Status</th>
                        <th>Signed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                        <?php
                        $st = (string) ($row['refusal_status'] ?? 'awaiting_patient_signature');
                        if ($st === '' && ! empty($row['signed_at'])) {
                            $st = empty($row['physician_certified_at']) ? 'awaiting_physician_certification' : 'completed';
                        }
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
                            <td><?php echo htmlspecialchars((string) ($row['declined_procedure'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="consent-status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(wpu_refusal_consent_status_label($st), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo ! empty($row['signed_at']) ? htmlspecialchars((string) $row['signed_at'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                            <td><a class="btn btn-sm btn-secondary" href="?page=view_refusal&amp;id=<?php echo (int) $row['id']; ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recent === []): ?>
                        <tr><td colspan="6">No refusal records yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif (! $refusalRow): ?>
            <div class="alert alert-error">Refusal record not found.</div>
        <?php else: ?>
            <?php
            $st = (string) ($refusalRow['refusal_status'] ?? '');
            if ($st === '' && ! empty($refusalRow['signed_at'])) {
                $st = empty($refusalRow['physician_certified_at']) ? 'awaiting_physician_certification' : 'completed';
            }
            $badgeClass = match ($st) {
                'completed' => 'is-completed',
                'withdrawn', 'superseded' => 'is-withdrawn',
                'draft', 'awaiting_patient_signature' => 'is-draft',
                default => 'is-awaiting',
            };
            ?>
            <p>
                <strong><?php echo htmlspecialchars((string) $refusalRow['patient_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="consent-status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(wpu_refusal_consent_status_label($st), ENT_QUOTES, 'UTF-8'); ?></span>
            </p>
            <p class="form-helper">
                Record #<?php echo (int) $refusalId; ?>
                <?php if (! empty($refusalRow['patient_record_id'])): ?>
                    · Visit #<?php echo (int) $refusalRow['patient_record_id']; ?>
                <?php endif; ?>
                · <a href="print_refusal_consent.php?id=<?php echo (int) $refusalId; ?>&amp;format=docx" download="Refusal-of-Consent-<?php echo (int) $refusalId; ?>.docx">Download official DOCX</a>
                · <a href="print_refusal_consent.php?id=<?php echo (int) $refusalId; ?>" target="_blank" rel="noopener">Print preview</a>
            </p>

            <div class="consent-official-preview">
                <?php
                $rc_treatment = (string) ($refusalRow['declined_procedure'] ?? '');
                $rc_named_patient = (string) ($refusalRow['named_patient'] ?? '');
                $rc_logo_url = ($his_assets ?? '../assets').'/images/logo.png';
                if ($canViewSigs) {
                    extract(wpu_refusal_consent_print_context($refusalRow), EXTR_SKIP);
                }
                include __DIR__.'/../partials/refusal_consent_document.php';
                ?>
            </div>

            <?php if ($st === 'awaiting_physician_certification' && $canCertify): ?>
                <hr style="margin:2rem 0">
                <h3>Physician certification</h3>
                <p class="form-helper">Only an authorized physician may sign the certification below. This records your authenticated account (<?php echo htmlspecialchars((string) ($_SESSION['admin_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>).</p>
                <form id="healthhubRefusalPhysicianCertForm" method="post" action="<?php echo htmlspecialchars($refusal_process_url, ENT_QUOTES, 'UTF-8'); ?>" class="settings-form-grid-2 no-validate">
                    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="physician_certify_refusal">
                    <input type="hidden" name="refusal_id" value="<?php echo (int) $refusalId; ?>">
                    <input type="hidden" name="physician_signature_data" id="refusal_physician_signature_data">
                    <div class="form-group full-width">
                        <label for="refusal_physician_printed_name">Physician printed name *</label>
                        <input type="text" class="form-control" id="refusal_physician_printed_name" name="physician_printed_name" required maxlength="255" placeholder="Certifying physician printed name" value="<?php echo htmlspecialchars((string) ($refusalRow['physician_printed_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>Physician signature *</label>
                        <div class="consent-sig-pad-wrap" data-healthhub-signature='{"formId":"healthhubRefusalPhysicianCertForm","canvasId":"refusalPhysicianSignaturePad","hiddenId":"refusal_physician_signature_data","clearId":"refusalPhysicianClearSig","redoId":"refusalPhysicianRedoSig"}'>
                            <canvas id="refusalPhysicianSignaturePad" width="520" height="160"></canvas>
                            <div class="consent-sig-actions">
                                <button type="button" class="btn btn-secondary btn-sm" id="refusalPhysicianClearSig">Clear</button>
                                <button type="button" class="btn btn-secondary btn-sm" id="refusalPhysicianRedoSig">Redo</button>
                            </div>
                        </div>
                    </div>
                    <div class="settings-form-actions full-width">
                        <button type="submit" class="btn btn-success">Complete physician certification</button>
                    </div>
                </form>
            <?php elseif ($st === 'awaiting_physician_certification'): ?>
                <div class="alert alert-warning">This refusal is pending physician certification.</div>
            <?php endif; ?>

            <?php if ($canManageLifecycle && ! in_array($st, ['withdrawn', 'superseded'], true)): ?>
                <hr style="margin:2rem 0">
                <h3>Refusal lifecycle</h3>
                <form id="refusalWithdrawForm" method="post" action="<?php echo htmlspecialchars($refusal_process_url, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="withdraw_refusal">
                    <input type="hidden" name="refusal_id" value="<?php echo (int) $refusalId; ?>">
                    <div class="form-group full-width">
                        <label for="refusal_withdrawal_reason">Withdraw refusal record</label>
                        <textarea id="refusal_withdrawal_reason" name="withdrawal_reason" class="form-control" rows="2" required placeholder="Reason for withdrawal"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">Withdraw refusal</button>
                </form>
            <?php endif; ?>

            <?php if ($st === 'withdrawn' && ! empty($refusalRow['withdrawal_reason'])): ?>
                <div class="alert alert-error">Withdrawn: <?php echo htmlspecialchars((string) $refusalRow['withdrawal_reason'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($versionRows !== [] && wpu_refusal_consent_can_view_full($role)): ?>
                <h3 style="margin-top:2rem">Version history</h3>
                <ul class="form-helper">
                    <?php foreach ($versionRows as $ver): ?>
                        <li><?php echo htmlspecialchars((string) ($ver['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars((string) ($ver['version_kind'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($auditRows !== [] && wpu_refusal_consent_can_view_full($role)): ?>
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
<script src="<?php echo $his_assets_path; ?>/js/healthhub-signature.js?v=6"></script>
<script>
(function () {
    var form = document.getElementById('refusalWithdrawForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!confirm('This action is recorded in the audit trail. Continue?')) return;
        fetch(form.getAttribute('action') || '', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (typeof AlertSystem !== 'undefined') {
                    AlertSystem.show(data.message || 'Done.', data.success ? 'success' : 'error');
                }
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            });
    });
})();
</script>
