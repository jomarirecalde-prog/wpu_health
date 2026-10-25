<?php
/**
 * Official WPU HSO Refusal of Consent Form (page 1 + physician certification page 2).
 *
 * Expected: $rc_treatment, $rc_named_patient, optional signature/display fields, $rc_logo_url
 */
require_once __DIR__.'/../../includes/wpu_refusal_consent.php';

$rc_cfg = wpu_refusal_consent_config();
$rc_univ = $rc_cfg['university'] ?? [];
$rc_physician = trim((string) ($rc_physician ?? ''));
$rc_treatment = trim((string) ($rc_treatment ?? ''));
$rc_named_patient = trim((string) ($rc_named_patient ?? ''));
$rc_form_ref = wpu_refusal_consent_form_reference();

$rc_esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$rc_dr = static function (string $name) use ($rc_esc): string {
    if ($name !== '') {
        return '<span class="rc-dr-line"><strong><u>Dr. '.$rc_esc($name).'</u></strong></span>';
    }

    return '<span class="rc-dr-line"><span class="mc-fill rc-dr-blank-text">Dr. ________________</span></span>';
};
$rc_u = static function ($text) use ($rc_esc) {
    return '<span class="mc-fill rc-fill">'.($text !== '' ? $rc_esc($text) : '&nbsp;').'</span>';
};

$mc_cfg = $rc_cfg;
$mc_univ = $rc_univ;
$mc_esc = $rc_esc;
$mc_logo_url = $rc_logo_url ?? ($mc_logo_url ?? '../assets/images/logo.png');

$rc_sig_block = static function (
    string $label,
    ?string $sig,
    string $caption,
    string $printed,
    string $date
) use ($rc_esc): void {
    ?>
    <div class="mc-signature-block">
        <p class="mc-section-label"><?php echo $rc_esc($label); ?></p>
        <table class="mc-sig-table" role="presentation">
            <tr class="mc-sig-line-row">
                <td class="mc-sig-main">
                    <?php if ($sig): ?>
                        <div class="mc-sig-ink"><img src="<?php echo $rc_esc($sig); ?>" alt="" class="mc-sig-img"></div>
                    <?php endif; ?>
                    <div class="mc-sig-line"><?php echo $printed !== '' ? $rc_esc($printed) : '&nbsp;'; ?></div>
                </td>
                <td class="mc-sig-date-col">
                    <div class="mc-sig-line mc-sig-date-value"><?php echo $date !== '' ? $rc_esc($date) : '&nbsp;'; ?></div>
                </td>
            </tr>
            <tr class="mc-sig-caption-row">
                <td class="mc-sig-caption"><?php echo $rc_esc($caption); ?></td>
                <td class="mc-sig-caption mc-sig-date-label">Date signed</td>
            </tr>
        </table>
    </div>
    <?php
};

$rc_patient_printed = trim((string) ($rc_patient_name ?? ''));
if ($rc_patient_printed !== '' && ! empty($rc_patient_relationship)) {
    $rc_patient_printed .= ', '.trim((string) $rc_patient_relationship);
}
?>
<div class="mc-document rc-document">
    <section class="mc-page mc-page-1" aria-label="Refusal of consent form page 1">
        <?php include __DIR__.'/medical_consent_header.php'; ?>

        <h1 class="mc-title"><?php echo $rc_esc($rc_cfg['form_title'] ?? 'REFUSAL OF CONSENT'); ?></h1>

        <div class="mc-body">
            <p class="mc-paragraph">
                I have been advised by <?php echo $rc_dr($rc_physician); ?> that the following treatment/procedure/diagnostic/medication/referral:
            </p>
            <p class="mc-paragraph">
                <?php echo $rc_u($rc_treatment); ?>, should be given to me or the named patient (if a minor): <?php echo $rc_u($rc_named_patient); ?>.
            </p>
            <p class="mc-paragraph">
                <?php echo $rc_dr($rc_physician); ?> has fully explained to me the nature, purpose, risks, and benefits of the proposed treatment/procedure/diagnostic/medication/referral, the possible alternatives thereto, and the risks and consequences of not proceeding.
            </p>
            <p class="mc-paragraph">
                I nonetheless refuse to consent to the proposed treatment/procedure/diagnostic/medication/referral, I have been given an opportunity to ask questions, and all my questions have been answered fully and satisfactorily.
            </p>
            <p class="mc-paragraph">
                I hereby release the university, infirmary, its employees and medical staff, on-the-job training students, and the attending physician from any liability for ill effects that may result from my decision to refuse to consent to the proposed treatment/procedure/diagnostic/medication/referral.
            </p>
            <p class="mc-paragraph">
                I confirm that I have read and fully understand the above and that all the blank spaces were completed prior to my signing.
            </p>
        </div>

        <?php
        $rc_sig_block(
            'Patient/Relative or Guardian',
            $rc_patient_sig ?? null,
            'Signature over printed name, Relationship',
            $rc_patient_printed,
            (string) ($rc_patient_date ?? '')
        );
        $rc_sig_block(
            'Interpreter (if used):',
            $rc_interpreter_sig ?? null,
            'Signature over printed name',
            (string) ($rc_interpreter_name ?? ''),
            (string) ($rc_interpreter_date ?? '')
        );
        $rc_sig_block(
            'Witness:',
            $rc_witness_sig ?? null,
            'Signature over printed name',
            (string) ($rc_witness_name ?? ''),
            (string) ($rc_witness_date ?? '')
        );
        ?>

        <p class="mc-form-ref mc-page-footer"><?php echo $rc_esc($rc_form_ref); ?></p>
    </section>

    <section class="mc-page mc-page-2" aria-label="Physician certification page 2">
        <?php include __DIR__.'/medical_consent_header.php'; ?>

        <h2 class="mc-cert-title">Physician Certification</h2>
        <p class="mc-paragraph">
            I hereby certify that I have explained the nature and purposes of, and alternatives to, the proposed treatment mentioned above, and the risks and consequences of not proceeding. I have offered to answer any questions and have fully answered all such questions. I believe that the patient/relative/guardian fully understands what I have explained and answered.
        </p>
        <p class="mc-section-label">Physician:</p>
        <table class="mc-sig-table mc-physician-table" role="presentation">
            <tr class="mc-sig-line-row">
                <td class="mc-sig-main">
                    <?php if (! empty($rc_physician_sig)): ?>
                        <div class="mc-sig-ink"><img src="<?php echo $rc_esc($rc_physician_sig); ?>" alt="" class="mc-sig-img"></div>
                    <?php endif; ?>
                    <div class="mc-sig-line"><?php echo ! empty($rc_physician_printed) ? $rc_esc($rc_physician_printed) : '&nbsp;'; ?></div>
                </td>
                <td class="mc-sig-date-col">
                    <div class="mc-sig-line mc-sig-date-value"><?php echo ! empty($rc_physician_cert_date) ? $rc_esc($rc_physician_cert_date) : '&nbsp;'; ?></div>
                </td>
            </tr>
            <tr class="mc-sig-caption-row">
                <td class="mc-sig-caption">Signature over printed name</td>
                <td class="mc-sig-caption mc-sig-date-label">Date</td>
            </tr>
        </table>

        <p class="mc-form-ref mc-page-footer"><?php echo $rc_esc($rc_form_ref); ?></p>
    </section>
</div>
