<?php
/**
 * Official WPU HSO Refusal of Consent Form — digital capture workflow.
 */
require_once __DIR__.'/../../includes/wpu_refusal_consent.php';

$pdo = $pdo ?? null;
$refusal_pathway = $_GET['pathway'] ?? 'adult';
if (! in_array($refusal_pathway, ['adult', 'minor'], true)) {
    $refusal_pathway = 'adult';
}

$refusalId = (int) ($_GET['id'] ?? 0);
$prefillRecordId = (int) ($_GET['patient_record_id'] ?? 0);
$refusalRow = null;

if ($pdo instanceof PDO && $refusalId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consent_refusals WHERE id = ? LIMIT 1');
    $stmt->execute([$refusalId]);
    $refusalRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($refusalRow) {
        $refusal_pathway = ($refusalRow['refusal_pathway'] ?? '') === 'minor_guardian' ? 'minor' : 'adult';
    }
}

$prefill = [
    'patient_name' => '',
    'patient_identifier' => '',
    'patient_age' => '',
    'date_of_birth' => '',
    'patient_type_label' => '',
    'declined_procedure' => '',
    'advising_physician' => '',
    'named_patient' => '',
    'guardian_name' => '',
    'patient_record_id' => $prefillRecordId,
];

if ($refusalRow) {
    $prefill['patient_name'] = (string) ($refusalRow['patient_name'] ?? '');
    $prefill['patient_identifier'] = (string) ($refusalRow['patient_identifier'] ?? '');
    $prefill['patient_age'] = (string) ($refusalRow['patient_age'] ?? '');
    $prefill['date_of_birth'] = (string) ($refusalRow['date_of_birth'] ?? '');
    $prefill['patient_type_label'] = (string) ($refusalRow['patient_type_label'] ?? '');
    $prefill['declined_procedure'] = (string) ($refusalRow['declined_procedure'] ?? '');
    $prefill['advising_physician'] = (string) ($refusalRow['advising_physician'] ?? '');
    $prefill['named_patient'] = (string) ($refusalRow['named_patient'] ?? '');
    $prefill['guardian_name'] = (string) ($refusalRow['guardian_name'] ?? '');
    $prefill['patient_record_id'] = (int) ($refusalRow['patient_record_id'] ?? 0);
} elseif ($pdo instanceof PDO && $prefillRecordId > 0) {
    $stmt = $pdo->prepare(
        'SELECT pr.*, pt.type_name AS patient_type_name
         FROM patient_records pr
         LEFT JOIN patient_types pt ON pt.id = pr.patient_type_id
         WHERE pr.id = ? LIMIT 1'
    );
    $stmt->execute([$prefillRecordId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rec) {
        $prefill['patient_name'] = (string) ($rec['full_name'] ?? '');
        $prefill['patient_identifier'] = (string) ($rec['student_id'] ?? '');
        $prefill['patient_age'] = (string) ($rec['age'] ?? '');
        $prefill['patient_type_label'] = (string) ($rec['patient_type_name'] ?? '');
        $prefill['declined_procedure'] = (string) ($rec['treatment'] ?? $rec['transaction_type'] ?? '');
        $prefill['guardian_name'] = (string) ($rec['guardian_name'] ?? '');
        if (($rec['is_minor'] ?? '') === 'Yes' || (int) ($rec['age'] ?? 0) < 18) {
            $refusal_pathway = 'minor';
            $prefill['named_patient'] = $prefill['patient_name'];
        }
    }
}

$refusalStatus = (string) ($refusalRow['refusal_status'] ?? '');
$patientSigned = in_array($refusalStatus, ['awaiting_physician_certification', 'completed'], true);
$readOnlyForm = $patientSigned;

$idempotencyKey = bin2hex(random_bytes(16));

$refusal_process_url = 'components/process_consent_refusal.php';
if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('route')) {
    $workspaceRoute = defined('WPU_LARAVEL_WORKSPACE_ROUTE') && WPU_LARAVEL_WORKSPACE_ROUTE !== ''
        ? (string) WPU_LARAVEL_WORKSPACE_ROUTE
        : 'admin.workspace';
    $refusal_process_url = route($workspaceRoute, ['path' => 'admin/components/process_consent_refusal.php']);
}

$his_assets_path = htmlspecialchars($his_assets ?? '../assets', ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $his_assets_path; ?>/css/medical-consent.css?v=1">

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-hand-paper" aria-hidden="true"></i> Refusal of Consent</h2>
        <p class="card-subtitle">Official WPU Health Services Office refusal form — <?php echo htmlspecialchars(wpu_refusal_consent_form_reference(), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($refusalStatus !== ''): ?>
            <?php
            $badgeClass = match ($refusalStatus) {
                'completed' => 'is-completed',
                'withdrawn', 'superseded' => 'is-withdrawn',
                'draft', 'awaiting_patient_signature' => 'is-draft',
                default => 'is-awaiting',
            };
            ?>
            <p><span class="consent-status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(wpu_refusal_consent_status_label($refusalStatus), ENT_QUOTES, 'UTF-8'); ?></span></p>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php include __DIR__.'/../partials/consent_refusal_module_nav.php'; ?>
        <p class="form-helper">Documenting refusal does not block unrelated future care or emergencies.</p>
        <div class="alert alert-warning" style="margin-top:1rem;font-size:0.9rem">
            The official form includes a release-of-liability clause. It is reproduced exactly as in the approved template and should be reviewed by the university’s authorized legal or compliance personnel. It does not automatically eliminate liability or enforceability concerns.
        </div>

        <div class="cr-tabs" role="tablist" aria-label="Refusal pathway">
            <a href="?page=consent_refusal&amp;pathway=adult<?php echo $refusalId ? '&amp;id='.$refusalId : ''; ?>" class="cr-tab <?php echo $refusal_pathway === 'adult' ? 'is-active' : ''; ?>">Patient 18+</a>
            <a href="?page=consent_refusal&amp;pathway=minor<?php echo $refusalId ? '&amp;id='.$refusalId : ''; ?>" class="cr-tab <?php echo $refusal_pathway === 'minor' ? 'is-active' : ''; ?>">Minor / representative</a>
        </div>

        <?php if ($patientSigned): ?>
            <div class="alert alert-info" style="margin-top:1rem">
                Patient or representative signature is on file.
                <a href="?page=view_refusal&amp;id=<?php echo (int) $refusalId; ?>">Open refusal record</a>
                <?php if ($refusalStatus === 'completed'): ?>
                    to
                    <a href="print_refusal_consent.php?id=<?php echo (int) $refusalId; ?>&amp;format=docx" download="Refusal-of-Consent-<?php echo (int) $refusalId; ?>.docx">download the official DOCX</a>.
                <?php else: ?>
                    for physician certification.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form id="healthhubRefusalForm" method="post" action="<?php echo htmlspecialchars($refusal_process_url, ENT_QUOTES, 'UTF-8'); ?>" class="settings-form-grid-2 no-validate" style="margin-top:1.5rem"<?php echo $readOnlyForm ? ' data-readonly="1"' : ''; ?>>
            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="submit_patient_refusal">
            <input type="hidden" name="refusal_id" value="<?php echo (int) $refusalId; ?>">
            <input type="hidden" name="idempotency_key" value="<?php echo htmlspecialchars($idempotencyKey, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="refusal_pathway" value="<?php echo htmlspecialchars($refusal_pathway === 'minor' ? 'minor_guardian' : 'adult', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="signature_data" id="refusal_signature_data">
            <input type="hidden" name="interpreter_signature_data" id="refusal_interpreter_signature_data">
            <input type="hidden" name="witness_signature_data" id="refusal_witness_signature_data">

            <div class="form-group">
                <label for="refusal_patient_name">Patient full name *</label>
                <input type="text" class="form-control" id="refusal_patient_name" name="patient_name" required maxlength="255" value="<?php echo htmlspecialchars($prefill['patient_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="refusal_patient_identifier">Patient ID *</label>
                <input type="text" class="form-control" id="refusal_patient_identifier" name="patient_identifier" required maxlength="64" value="<?php echo htmlspecialchars($prefill['patient_identifier'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="refusal_patient_age">Age *</label>
                <input type="number" class="form-control" id="refusal_patient_age" name="patient_age" required min="0" max="120" value="<?php echo htmlspecialchars($prefill['patient_age'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="refusal_dob">Date of birth</label>
                <input type="date" class="form-control" id="refusal_dob" name="date_of_birth" value="<?php echo htmlspecialchars($prefill['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="refusal_patient_type">Patient type</label>
                <input type="text" class="form-control" id="refusal_patient_type" name="patient_type_label" maxlength="128" value="<?php echo htmlspecialchars($prefill['patient_type_label'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="refusal_patient_record_id">Linked visit / record ID</label>
                <input type="number" class="form-control" id="refusal_patient_record_id" name="patient_record_id" min="1" step="1" value="<?php echo $prefill['patient_record_id'] > 0 ? (int) $prefill['patient_record_id'] : ''; ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group full-width">
                <label for="refusal_declined_procedure">Treatment / procedure / diagnostic / medication / referral declined *</label>
                <input type="text" class="form-control" id="refusal_declined_procedure" name="declined_procedure" required maxlength="500" value="<?php echo htmlspecialchars($prefill['declined_procedure'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group full-width">
                <label for="refusal_advising_physician">Advising physician (as on form: Dr. …) *</label>
                <input type="text" class="form-control" id="refusal_advising_physician" name="advising_physician" required maxlength="255" placeholder="Type physician name and credentials" value="<?php echo htmlspecialchars($prefill['advising_physician'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
                <p class="form-helper">This appears on page 1 of the official refusal form. It is not taken from medical consent settings.</p>
            </div>
            <?php if ($refusal_pathway === 'minor'): ?>
            <div class="form-group full-width">
                <label for="refusal_named_patient">Named patient (if a minor)</label>
                <input type="text" class="form-control" id="refusal_named_patient" name="named_patient" maxlength="255" value="<?php echo htmlspecialchars($prefill['named_patient'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group full-width">
                <label for="refusal_guardian_name">Authorized representative (print name) *</label>
                <input type="text" class="form-control" id="refusal_guardian_name" name="guardian_name" <?php echo $refusal_pathway === 'minor' && ! $readOnlyForm ? 'required' : ''; ?> maxlength="255" value="<?php echo htmlspecialchars($prefill['guardian_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <?php endif; ?>

            <div class="form-group full-width consent-official-preview">
                <?php
                $rc_treatment = $prefill['declined_procedure'];
                $rc_physician = $prefill['advising_physician'];
                $rc_named_patient = $refusal_pathway === 'minor' ? ($prefill['named_patient'] ?: $prefill['patient_name']) : '';
                $rc_logo_url = ($his_assets ?? '../assets').'/images/logo.png';
                include __DIR__.'/../partials/refusal_consent_document.php';
                ?>
            </div>

            <?php if (! $readOnlyForm): ?>
            <div class="form-group full-width">
                <label for="refusal_signed_by">Signed by (print name) *</label>
                <input type="text" class="form-control" id="refusal_signed_by" name="signed_by_name" required maxlength="255" placeholder="<?php echo $refusal_pathway === 'minor' ? 'Authorized representative' : 'Patient'; ?>">
            </div>
            <div class="form-group">
                <label for="refusal_relationship">Relationship / capacity <?php echo $refusal_pathway === 'minor' ? '*' : ''; ?></label>
                <input type="text" class="form-control" id="refusal_relationship" name="representative_relationship" maxlength="128" placeholder="<?php echo $refusal_pathway === 'minor' ? 'e.g. Parent, legal guardian' : 'Self (patient) or representative capacity'; ?>" <?php echo $refusal_pathway === 'minor' ? 'required' : ''; ?>>
            </div>

            <div class="form-group full-width">
                <label>Patient / representative digital signature *</label>
                <div class="consent-sig-pad-wrap" data-healthhub-signature='{"formId":"healthhubRefusalForm","canvasId":"refusalSignaturePad","hiddenId":"refusal_signature_data","clearId":"refusalClearSig","redoId":"refusalRedoSig"}'>
                    <canvas id="refusalSignaturePad" width="520" height="160"></canvas>
                    <div class="consent-sig-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="refusalClearSig">Clear</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="refusalRedoSig">Redo</button>
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="refusal_interpreter_name">Interpreter (if used)</label>
                <input type="text" class="form-control" id="refusal_interpreter_name" name="interpreter_name" maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Interpreter signature</label>
                <div class="consent-sig-pad-wrap" data-healthhub-signature='{"formId":"healthhubRefusalForm","canvasId":"refusalInterpreterPad","hiddenId":"refusal_interpreter_signature_data","clearId":"refusalInterpreterClear","redoId":"refusalInterpreterRedo","optional":true}'>
                    <canvas id="refusalInterpreterPad" width="520" height="120"></canvas>
                    <div class="consent-sig-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="refusalInterpreterClear">Clear</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="refusalInterpreterRedo">Redo</button>
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="refusal_witness_name">Witness</label>
                <input type="text" class="form-control" id="refusal_witness_name" name="witness_name" maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Witness signature</label>
                <div class="consent-sig-pad-wrap" data-healthhub-signature='{"formId":"healthhubRefusalForm","canvasId":"refusalWitnessPad","hiddenId":"refusal_witness_signature_data","clearId":"refusalWitnessClear","redoId":"refusalWitnessRedo","optional":true}'>
                    <canvas id="refusalWitnessPad" width="520" height="120"></canvas>
                    <div class="consent-sig-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="refusalWitnessClear">Clear</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="refusalWitnessRedo">Redo</button>
                    </div>
                </div>
            </div>

            <div class="settings-form-actions full-width">
                <button type="button" class="btn btn-secondary" id="refusalSaveDraftBtn">Save draft</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check" aria-hidden="true"></i> Submit signed refusal</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<script src="<?php echo $his_assets_path; ?>/js/healthhub-signature.js?v=6"></script>
<script>
(function () {
    var draftBtn = document.getElementById('refusalSaveDraftBtn');
    if (draftBtn) {
        draftBtn.addEventListener('click', function () {
            if (typeof window.healthhubSubmitConsentDraft === 'function') {
                window.healthhubSubmitConsentDraft('healthhubRefusalForm');
            }
        });
    }
    var procedure = document.getElementById('refusal_declined_procedure');
    var named = document.getElementById('refusal_named_patient');
    if (procedure) {
        procedure.addEventListener('input', function () {
            document.querySelectorAll('.rc-fill').forEach(function (el, i) {
                if (i === 0) el.textContent = procedure.value || '\u00a0';
            });
        });
    }
    if (named) {
        named.addEventListener('input', function () {
            var fills = document.querySelectorAll('.rc-fill');
            if (fills[1]) fills[1].textContent = named.value || '\u00a0';
        });
    }
    var advising = document.getElementById('refusal_advising_physician');
    function syncRcDrLine(name) {
        var text = (name || '').trim();
        document.querySelectorAll('.rc-dr-line').forEach(function (el) {
            if (text !== '') {
                el.textContent = '';
                var strong = document.createElement('strong');
                var u = document.createElement('u');
                u.textContent = 'Dr. ' + text;
                strong.appendChild(u);
                el.appendChild(strong);
            } else {
                el.innerHTML = '<span class="mc-fill rc-dr-blank-text">Dr. ________________</span>';
            }
        });
    }
    if (advising) {
        advising.addEventListener('input', function () {
            syncRcDrLine(advising.value);
        });
    }
})();
</script>
