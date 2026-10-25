<?php
/**
 * Official WPU HSO Medical Consent Form (page 1 + physician certification page 2).
 *
 * Expected: $mc_treatment, $mc_named_patient, optional signature/display fields, $mc_logo_url
 */
require_once __DIR__.'/../../includes/wpu_medical_consent.php';

$mc_cfg = wpu_medical_consent_config();
$mc_univ = $mc_cfg['university'] ?? [];
$mc_physician = $mc_physician ?? wpu_medical_consent_advising_physician();
$mc_treatment = trim((string) ($mc_treatment ?? ''));
$mc_named_patient = trim((string) ($mc_named_patient ?? ''));
$mc_form_ref = wpu_medical_consent_form_reference();

$mc_esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$mc_u = static function ($text) use ($mc_esc) {
    return '<span class="mc-fill">'.($text !== '' ? $mc_esc($text) : '&nbsp;').'</span>';
};

$mc_sig_block = static function (
    string $label,
    ?string $sig,
    string $caption,
    string $printed,
    string $date
) use ($mc_esc): void {
    ?>
    <div class="mc-signature-block">
        <p class="mc-section-label"><?php echo $mc_esc($label); ?></p>
        <table class="mc-sig-table" role="presentation">
            <tr class="mc-sig-line-row">
                <td class="mc-sig-main">
                    <?php if ($sig): ?>
                        <div class="mc-sig-ink"><img src="<?php echo $mc_esc($sig); ?>" alt="" class="mc-sig-img"></div>
                    <?php endif; ?>
                    <div class="mc-sig-line"><?php echo $printed !== '' ? $mc_esc($printed) : '&nbsp;'; ?></div>
                </td>
                <td class="mc-sig-date-col">
                    <div class="mc-sig-line mc-sig-date-value"><?php echo $date !== '' ? $mc_esc($date) : '&nbsp;'; ?></div>
                </td>
            </tr>
            <tr class="mc-sig-caption-row">
                <td class="mc-sig-caption"><?php echo $mc_esc($caption); ?></td>
                <td class="mc-sig-caption mc-sig-date-label">Date signed</td>
            </tr>
        </table>
    </div>
    <?php
};

$mc_patient_printed = trim((string) ($mc_patient_name ?? ''));
if ($mc_patient_printed !== '' && ! empty($mc_patient_relationship)) {
    $mc_patient_printed .= ', '.trim((string) $mc_patient_relationship);
}
?>
<div class="mc-document">
    <section class="mc-page mc-page-1" aria-label="Medical consent form page 1">
        <?php include __DIR__.'/medical_consent_header.php'; ?>

        <h1 class="mc-title"><?php echo $mc_esc($mc_cfg['form_title'] ?? 'MEDICAL CONSENT FORM'); ?></h1>

        <div class="mc-body">
            <p class="mc-paragraph">
                I have been advised by <strong><u>Dr. <?php echo $mc_esc($mc_physician); ?></u></strong> that the following treatment/procedure/diagnostic/medication/referral: <?php echo $mc_u($mc_treatment); ?>,
            </p>
            <p class="mc-paragraph">
                should be given to me or the named patient (if a minor): <?php echo $mc_u($mc_named_patient); ?>.
            </p>
            <p class="mc-paragraph">
                <strong><u>Dr. <?php echo $mc_esc($mc_physician); ?></u></strong> has fully explained to me the nature, purpose, risks, and benefits of the proposed treatment/procedure/diagnostic/medication/referral, the possible alternatives thereto, and the possible risks/adverse effects/consequences of the treatment/procedure/diagnostic/medication/referral.
            </p>
            <p class="mc-paragraph">
                I willingly consent to undergo the proposed treatment/procedure/diagnostic/medication/referral, I have been given an opportunity to ask questions, and all my questions have been answered fully and satisfactorily.
            </p>
            <p class="mc-paragraph">
                I confirm that I have read and fully understand the above and that all the blank spaces were completed prior to my signing.
            </p>
        </div>

        <?php
        $mc_sig_block(
            'Patient/Relative or Guardian',
            $mc_patient_sig ?? null,
            'Signature over printed name, Relationship',
            $mc_patient_printed,
            (string) ($mc_patient_date ?? '')
        );
        $mc_sig_block(
            'Interpreter (if used):',
            $mc_interpreter_sig ?? null,
            'Signature over printed name',
            (string) ($mc_interpreter_name ?? ''),
            (string) ($mc_interpreter_date ?? '')
        );
        $mc_sig_block(
            'Witness:',
            $mc_witness_sig ?? null,
            'Signature over printed name',
            (string) ($mc_witness_name ?? ''),
            (string) ($mc_witness_date ?? '')
        );
        ?>

        <p class="mc-form-ref mc-page-footer"><?php echo $mc_esc($mc_form_ref); ?></p>
    </section>

    <section class="mc-page mc-page-2" aria-label="Physician certification page 2">
        <?php include __DIR__.'/medical_consent_header.php'; ?>

        <h2 class="mc-cert-title">Physician Certification</h2>
        <p class="mc-paragraph">
            I hereby certify that I have explained the nature and purposes of, and alternatives to, the proposed treatment/procedure/diagnostic/medication/referral mentioned above, and the risks/adverse effects/ consequences of undergoing the treatment/procedure/diagnostic/medication/referral. I have offered to answer any questions and have fully answered all such questions. I believe that the patient/relative/guardian fully understands what I have explained and answered.
        </p>
        <p class="mc-section-label">Physician:</p>
        <p class="mc-physician-name"><strong><?php echo $mc_esc($mc_physician); ?></strong></p>
        <table class="mc-sig-table mc-physician-table" role="presentation">
            <tr class="mc-sig-line-row">
                <td class="mc-sig-main">
                    <?php if (! empty($mc_physician_sig)): ?>
                        <div class="mc-sig-ink"><img src="<?php echo $mc_esc($mc_physician_sig); ?>" alt="" class="mc-sig-img"></div>
                    <?php endif; ?>
                    <div class="mc-sig-line"><?php echo ! empty($mc_physician_printed) ? $mc_esc($mc_physician_printed) : '&nbsp;'; ?></div>
                </td>
                <td class="mc-sig-date-col">
                    <div class="mc-sig-line mc-sig-date-value"><?php echo ! empty($mc_physician_cert_date) ? $mc_esc($mc_physician_cert_date) : '&nbsp;'; ?></div>
                </td>
            </tr>
            <tr class="mc-sig-caption-row">
                <td class="mc-sig-caption">Signature over printed name</td>
                <td class="mc-sig-caption mc-sig-date-label">Date</td>
            </tr>
        </table>

        <p class="mc-form-ref mc-page-footer"><?php echo $mc_esc($mc_form_ref); ?></p>
    </section>
</div>
