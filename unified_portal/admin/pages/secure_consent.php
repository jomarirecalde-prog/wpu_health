<?php
/**
 * Official WPU HSO Medical Consent Form — digital capture workflow.
 */
require_once __DIR__.'/../../includes/wpu_medical_consent.php';

$pdo = $pdo ?? null;
$consent_pathway = $_GET['pathway'] ?? 'adult';
if (! in_array($consent_pathway, ['adult', 'minor'], true)) {
    $consent_pathway = 'adult';
}

$consentId = (int) ($_GET['id'] ?? 0);
$prefillRecordId = (int) ($_GET['patient_record_id'] ?? 0);
$consentRow = null;

if ($pdo instanceof PDO && $consentId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM healthhub_consents WHERE id = ? LIMIT 1');
    $stmt->execute([$consentId]);
    $consentRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($consentRow) {
        $consent_pathway = ($consentRow['consent_pathway'] ?? '') === 'minor_guardian' ? 'minor' : 'adult';
    }
}

$prefill = [
    'patient_name' => '',
    'patient_identifier' => '',
    'patient_age' => '',
    'date_of_birth' => '',
    'patient_type_label' => '',
    'treatment_procedure' => '',
    'named_patient' => '',
    'guardian_name' => '',
    'patient_record_id' => $prefillRecordId,
];

if ($consentRow) {
    $prefill['patient_name'] = (string) ($consentRow['patient_name'] ?? '');
    $prefill['patient_identifier'] = (string) ($consentRow['patient_identifier'] ?? '');
    $prefill['patient_age'] = (string) ($consentRow['patient_age'] ?? '');
    $prefill['date_of_birth'] = (string) ($consentRow['date_of_birth'] ?? '');
    $prefill['patient_type_label'] = (string) ($consentRow['patient_type_label'] ?? '');
    $prefill['treatment_procedure'] = (string) ($consentRow['purpose'] ?? '');
    $prefill['named_patient'] = (string) ($consentRow['named_patient'] ?? '');
    $prefill['guardian_name'] = (string) ($consentRow['guardian_name'] ?? '');
    $prefill['patient_record_id'] = (int) ($consentRow['patient_record_id'] ?? 0);
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
        $prefill['treatment_procedure'] = (string) ($rec['treatment'] ?? $rec['transaction_type'] ?? '');
        $prefill['guardian_name'] = (string) ($rec['guardian_name'] ?? '');
        if (($rec['is_minor'] ?? '') === 'Yes' || (int) ($rec['age'] ?? 0) < 18) {
            $consent_pathway = 'minor';
            $prefill['named_patient'] = $prefill['patient_name'];
        }
    }
}

$consentStatus = (string) ($consentRow['consent_status'] ?? '');
$patientSigned = in_array($consentStatus, ['awaiting_physician_certification', 'completed'], true);
$readOnlyForm = $patientSigned;

$idempotencyKey = bin2hex(random_bytes(16));

$consent_process_url = 'components/process_consent.php';
if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('route')) {
    $workspaceRoute = defined('WPU_LARAVEL_WORKSPACE_ROUTE') && WPU_LARAVEL_WORKSPACE_ROUTE !== ''
        ? (string) WPU_LARAVEL_WORKSPACE_ROUTE
        : 'admin.workspace';
    $consent_process_url = route($workspaceRoute, ['path' => 'admin/components/process_consent.php']);
}

$his_assets_path = htmlspecialchars($his_assets ?? '../assets', ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $his_assets_path; ?>/css/medical-consent.css?v=1">

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-file-signature" aria-hidden="true"></i> Medical Consent Form</h2>
        <p class="card-subtitle">Official WPU Health Services Office informed consent — <?php echo htmlspecialchars(wpu_medical_consent_form_reference(), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($consentStatus !== ''): ?>
            <?php
            $badgeClass = match ($consentStatus) {
                'completed' => 'is-completed',
                'withdrawn', 'superseded' => 'is-withdrawn',
                'draft', 'awaiting_patient_signature' => 'is-draft',
                default => 'is-awaiting',
            };
            ?>
            <p><span class="consent-status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(wpu_medical_consent_status_label($consentStatus), ENT_QUOTES, 'UTF-8'); ?></span></p>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php include __DIR__.'/../partials/consent_refusal_module_nav.php'; ?>
        <div class="cr-tabs" role="tablist" aria-label="Consent pathway">
            <a href="?page=secure_consent&amp;pathway=adult<?php echo $consentId ? '&amp;id='.$consentId : ''; ?>" class="cr-tab <?php echo $consent_pathway === 'adult' ? 'is-active' : ''; ?>">Patient 18+</a>
            <a href="?page=secure_consent&amp;pathway=minor<?php echo $consentId ? '&amp;id='.$consentId : ''; ?>" class="cr-tab <?php echo $consent_pathway === 'minor' ? 'is-active' : ''; ?>">Minor / representative</a>
        </div>

        <?php if ($patientSigned): ?>
            <div class="alert alert-info" style="margin-top:1rem">
                Patient or representative signature is on file.
                <a href="?page=view_consent&amp;id=<?php echo (int) $consentId; ?>">Open consent record</a>
                <?php if ($consentStatus === 'completed'): ?>
                    to
                    <a href="print_medical_consent.php?id=<?php echo (int) $consentId; ?>&amp;format=docx" download="Medical-Consent-<?php echo (int) $consentId; ?>.docx">download the official DOCX</a>.
                <?php else: ?>
                    for physician certification.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form id="healthhubConsentForm" method="post" action="<?php echo htmlspecialchars($consent_process_url, ENT_QUOTES, 'UTF-8'); ?>" class="settings-form-grid-2 no-validate" style="margin-top:1.5rem"<?php echo $readOnlyForm ? ' data-readonly="1"' : ''; ?>>
            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="submit_patient_consent">
            <input type="hidden" name="consent_id" value="<?php echo (int) $consentId; ?>">
            <input type="hidden" name="idempotency_key" value="<?php echo htmlspecialchars($idempotencyKey, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="consent_pathway" value="<?php echo htmlspecialchars($consent_pathway === 'minor' ? 'minor_guardian' : 'adult', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="signature_data" id="consent_signature_data">
            <input type="hidden" name="interpreter_signature_data" id="consent_interpreter_signature_data">
            <input type="hidden" name="witness_signature_data" id="consent_witness_signature_data">

            <div class="form-group">
                <label for="consent_patient_name">Patient full name *</label>
                <input type="text" class="form-control" id="consent_patient_name" name="patient_name" required maxlength="255" value="<?php echo htmlspecialchars($prefill['patient_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="consent_patient_identifier">Patient ID *</label>
                <input type="text" class="form-control" id="consent_patient_identifier" name="patient_identifier" required maxlength="64" value="<?php echo htmlspecialchars($prefill['patient_identifier'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="consent_patient_age">Age *</label>
                <input type="number" class="form-control" id="consent_patient_age" name="patient_age" required min="0" max="120" value="<?php echo htmlspecialchars($prefill['patient_age'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="consent_dob">Date of birth</label>
                <input type="date" class="form-control" id="consent_dob" name="date_of_birth" value="<?php echo htmlspecialchars($prefill['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="consent_patient_type">Patient type</label>
                <input type="text" class="form-control" id="consent_patient_type" name="patient_type_label" maxlength="128" value="<?php echo htmlspecialchars($prefill['patient_type_label'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="consent_patient_record_id">Linked visit / record ID</label>
                <input type="number" class="form-control" id="consent_patient_record_id" name="patient_record_id" min="1" step="1" value="<?php echo $prefill['patient_record_id'] > 0 ? (int) $prefill['patient_record_id'] : ''; ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group full-width">
                <label for="consent_treatment">Treatment / procedure / diagnostic / medication / referral *</label>
                <input type="text" class="form-control" id="consent_treatment" name="treatment_procedure" required maxlength="500" value="<?php echo htmlspecialchars($prefill['treatment_procedure'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <?php if ($consent_pathway === 'minor'): ?>
            <div class="form-group full-width">
                <label for="consent_named_patient">Named patient (if a minor)</label>
                <input type="text" class="form-control" id="consent_named_patient" name="named_patient" maxlength="255" value="<?php echo htmlspecialchars($prefill['named_patient'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group full-width">
                <label for="consent_guardian_name">Authorized representative (print name) *</label>
                <input type="text" class="form-control" id="consent_guardian_name" name="guardian_name" <?php echo $consent_pathway === 'minor' && ! $readOnlyForm ? 'required' : ''; ?> maxlength="255" value="<?php echo htmlspecialchars($prefill['guardian_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readOnlyForm ? 'readonly' : ''; ?>>
            </div>
            <?php endif; ?>

            <div class="form-group full-width consent-official-preview">
                <?php
                $mc_treatment = $prefill['treatment_procedure'];
                $mc_named_patient = $consent_pathway === 'minor' ? ($prefill['named_patient'] ?: $prefill['patient_name']) : '';
                $mc_logo_url = ($his_assets ?? '../assets').'/images/logo.png';
                include __DIR__.'/../partials/medical_consent_document.php';
                ?>
            </div>

            <?php if (! $readOnlyForm): ?>
            <div class="form-group full-width">
                <label for="consent_signed_by">Signed by (print name) *</label>
                <input type="text" class="form-control" id="consent_signed_by" name="signed_by_name" required maxlength="255" placeholder="<?php echo $consent_pathway === 'minor' ? 'Authorized representative' : 'Patient'; ?>">
            </div>
            <div class="form-group">
                <label for="consent_relationship">Relationship / capacity <?php echo $consent_pathway === 'minor' ? '*' : ''; ?></label>
                <input type="text" class="form-control" id="consent_relationship" name="representative_relationship" maxlength="128" placeholder="<?php echo $consent_pathway === 'minor' ? 'e.g. Parent, legal guardian' : 'Self (patient) or representative capacity'; ?>" <?php echo $consent_pathway === 'minor' ? 'required' : ''; ?>>
            </div>

            <div class="form-group full-width">
                <label>Patient / representative digital signature *</label>
                <div class="consent-sig-pad-wrap" data-healthhub-signature='{"canvasId":"consentSignaturePad","hiddenId":"consent_signature_data","clearId":"consentClearSig","redoId":"consentRedoSig"}'>
                    <canvas id="consentSignaturePad" width="520" height="160"></canvas>
                    <div class="consent-sig-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="consentClearSig">Clear</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="consentRedoSig">Redo</button>
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="consent_interpreter_name">Interpreter (if used)</label>
                <input type="text" class="form-control" id="consent_interpreter_name" name="interpreter_name" maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Interpreter signature</label>
                <div class="consent-sig-pad-wrap" data-healthhub-signature='{"canvasId":"consentInterpreterPad","hiddenId":"consent_interpreter_signature_data","clearId":"consentInterpreterClear","redoId":"consentInterpreterRedo","optional":true}'>
                    <canvas id="consentInterpreterPad" width="520" height="120"></canvas>
                    <div class="consent-sig-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="consentInterpreterClear">Clear</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="consentInterpreterRedo">Redo</button>
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="consent_witness_name">Witness</label>
                <input type="text" class="form-control" id="consent_witness_name" name="witness_name" maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Witness signature</label>
                <div class="consent-sig-pad-wrap" data-healthhub-signature='{"canvasId":"consentWitnessPad","hiddenId":"consent_witness_signature_data","clearId":"consentWitnessClear","redoId":"consentWitnessRedo","optional":true}'>
                    <canvas id="consentWitnessPad" width="520" height="120"></canvas>
                    <div class="consent-sig-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="consentWitnessClear">Clear</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="consentWitnessRedo">Redo</button>
                    </div>
                </div>
            </div>

            <div class="settings-form-actions full-width">
                <button type="button" class="btn btn-secondary" id="consentSaveDraftBtn" data-draft-action="save_draft">Save draft</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check" aria-hidden="true"></i> Submit patient consent</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<script src="<?php echo $his_assets_path; ?>/js/healthhub-signature.js?v=5"></script>
<script>
(function () {
    var draftBtn = document.getElementById('consentSaveDraftBtn');
    if (draftBtn) {
        draftBtn.addEventListener('click', function () {
            if (typeof window.healthhubSubmitConsentDraft === 'function') {
                window.healthhubSubmitConsentDraft('healthhubConsentForm');
            }
        });
    }
    var treatment = document.getElementById('consent_treatment');
    var named = document.getElementById('consent_named_patient');
    if (treatment) {
        treatment.addEventListener('input', function () {
            document.querySelectorAll('.mc-fill').forEach(function (el, i) {
                if (i === 0) el.textContent = treatment.value || '\u00a0';
            });
        });
    }
    if (named) {
        named.addEventListener('input', function () {
            var fills = document.querySelectorAll('.mc-fill');
            if (fills[1]) fills[1].textContent = named.value || '\u00a0';
        });
    }
})();
</script>
