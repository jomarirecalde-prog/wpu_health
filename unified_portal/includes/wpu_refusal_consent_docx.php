<?php

declare(strict_types=1);

require_once __DIR__.'/wpu_refusal_consent.php';
require_once __DIR__.'/wpu_medical_consent_docx.php';

/**
 * @param  array<string, mixed>  $row
 */
function wpu_refusal_consent_generate_docx(array $row): string
{
    $template = wpu_refusal_consent_docx_template_path();
    if ($template === null) {
        throw new RuntimeException('Refusal of consent Word template is missing.');
    }

    $ctx = wpu_refusal_consent_print_context($row);
    $physician = trim((string) $ctx['rc_physician']);

    $tmpIn = tempnam(sys_get_temp_dir(), 'rf_tpl_');
    if ($tmpIn === false) {
        throw new RuntimeException('Unable to allocate temporary files for refusal DOCX.');
    }

    copy($template, $tmpIn);

    $zip = new ZipArchive();
    if ($zip->open($tmpIn) !== true) {
        @unlink($tmpIn);
        throw new RuntimeException('Unable to open refusal template.');
    }

    $documentXml = (string) $zip->getFromName('word/document.xml');
    if ($documentXml === '') {
        $zip->close();
        @unlink($tmpIn);
        throw new RuntimeException('Refusal template document.xml is missing.');
    }

    $relsPath = 'word/_rels/document.xml.rels';
    $relsXml = (string) $zip->getFromName($relsPath);
    $contentTypesPath = '[Content_Types].xml';
    $contentTypesXml = (string) $zip->getFromName($contentTypesPath);

    $documentXml = wpu_refusal_docx_force_page2_break($documentXml);

    if ($physician !== '') {
        $physicianDr = wpu_medical_consent_docx_escape('Dr. '.$physician);
        $documentXml = wpu_medical_consent_docx_replace_once(
            $documentXml,
            'Dr. ________________',
            $physicianDr
        );
        $documentXml = wpu_medical_consent_docx_replace_once(
            $documentXml,
            'Dr. __________________',
            $physicianDr
        );
    }

    $treatment = wpu_medical_consent_docx_trim_field((string) $ctx['rc_treatment'], 120);
    if ($treatment !== '') {
        $documentXml = wpu_medical_consent_docx_replace_once(
            $documentXml,
            '_____________________________________________',
            wpu_medical_consent_docx_escape($treatment)
        );
        $documentXml = wpu_medical_consent_docx_replace_once($documentXml, '<w:t>_, should</w:t>', '<w:t>, should</w:t>');
    }

    $namedPatient = wpu_medical_consent_docx_trim_field((string) $ctx['rc_named_patient'], 80);
    $namedPrefix = 'named patient (if a minor): ________________________';
    $namedReplacement = 'named patient (if a minor): '.wpu_medical_consent_docx_escape(
        $namedPatient !== '' ? $namedPatient : '________________________'
    );
    $documentXml = wpu_medical_consent_docx_replace_once($documentXml, $namedPrefix, $namedReplacement);
    if ($namedPatient !== '') {
        $documentXml = wpu_medical_consent_docx_replace_once($documentXml, '<w:t>_.</w:t>', '<w:t>.</w:t>');
    }

    $documentXml = wpu_refusal_docx_fill_split_signature_runs(
        $documentXml,
        wpu_medical_consent_docx_sig_left_label((string) $ctx['rc_patient_name'], (string) $ctx['rc_patient_relationship']),
        (string) $ctx['rc_patient_date'],
        'patient'
    );
    $documentXml = wpu_refusal_docx_fill_split_signature_runs(
        $documentXml,
        wpu_medical_consent_docx_sig_left_label((string) $ctx['rc_interpreter_name'], ''),
        (string) $ctx['rc_interpreter_date'],
        'interpreter'
    );
    $documentXml = wpu_refusal_docx_fill_split_signature_runs(
        $documentXml,
        wpu_medical_consent_docx_sig_left_label((string) $ctx['rc_witness_name'], ''),
        (string) $ctx['rc_witness_date'],
        'witness'
    );

    $printedPhysician = trim((string) $ctx['rc_physician_printed']);
    $physicianDate = (string) $ctx['rc_physician_cert_date'];
    $documentXml = wpu_refusal_docx_fill_split_signature_runs(
        $documentXml,
        $printedPhysician !== '' ? wpu_medical_consent_docx_escape($printedPhysician) : '',
        $physicianDate,
        'physician'
    );

    $embedIndex = 0;
    foreach ([
        ['field' => 'rc_patient_sig', 'marker' => 'Patient/Relative or Guardian'],
        ['field' => 'rc_interpreter_sig', 'marker' => 'Interpreter (if used):'],
        ['field' => 'rc_witness_sig', 'marker' => 'Witness:'],
        ['field' => 'rc_physician_sig', 'marker' => 'Physician:'],
    ] as $sigSpec) {
        $dataUrl = $ctx[$sigSpec['field']] ?? null;
        if (! is_string($dataUrl) || $dataUrl === '') {
            continue;
        }
        $png = wpu_medical_consent_docx_png_from_data_url($dataUrl);
        if ($png === null) {
            continue;
        }
        ++$embedIndex;
        $documentXml = wpu_medical_consent_docx_insert_signature_image(
            $zip,
            $documentXml,
            $relsXml,
            $contentTypesXml,
            $sigSpec['marker'],
            $png,
            'rc_sig_'.$embedIndex.'.png'
        );
    }

    $zip->addFromString('word/document.xml', $documentXml);
    if ($relsXml !== '') {
        $zip->addFromString($relsPath, $relsXml);
    }
    if ($contentTypesXml !== '') {
        $zip->addFromString($contentTypesPath, $contentTypesXml);
    }
    $zip->close();

    $binary = (string) file_get_contents($tmpIn);
    @unlink($tmpIn);

    if ($binary === '') {
        throw new RuntimeException('Generated refusal DOCX is empty.');
    }

    return $binary;
}

/**
 * REFUSAL.docx has no embedded page break before Physician Certification (unlike CONSENT.docx).
 */
function wpu_refusal_docx_force_page2_break(string $xml): string
{
    return wpu_medical_consent_docx_force_page2_break($xml);
}

/**
 * REFUSAL.docx stores signature blanks in separate Word runs; fill them without flattening layout.
 */
function wpu_refusal_docx_fill_split_signature_runs(
    string $xml,
    string $leftHtml,
    string $rightPlain,
    string $kind
): string {
    $leftPlain = $leftHtml !== '' ? html_entity_decode(strip_tags($leftHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    $leftEsc = $leftPlain !== '' ? wpu_medical_consent_docx_escape($leftPlain) : '';
    $rightEsc = $rightPlain !== '' ? wpu_medical_consent_docx_escape($rightPlain) : '';

    $sets = [
        'patient' => [
            'left' => '<w:t xml:space="preserve"> ___________________________'."\xC2\xA0".' </w:t>',
            'right' => '<w:t xml:space="preserve">                                                 ___________________</w:t>',
        ],
        'interpreter' => [
            'left' => '<w:t>____________________________</w:t>',
            'right' => '<w:t xml:space="preserve">                                                  ____________________</w:t>',
        ],
        'witness' => [
            'left' => '<w:t>Witness: ______________________</w:t>',
            'right' => '<w:t xml:space="preserve">                                                 ____________________</w:t>',
        ],
        'physician' => [
            'left' => '<w:t xml:space="preserve"> __________________________</w:t>',
            'right' => '<w:t xml:space="preserve">                                                    _________________</w:t>',
        ],
    ];

    if (! isset($sets[$kind])) {
        return $xml;
    }

    $leftBlank = $sets[$kind]['left'];
    $rightBlank = $sets[$kind]['right'];

    if ($leftEsc !== '') {
        if ($kind === 'witness') {
            $witnessLabel = $leftPlain !== ''
                ? 'Witness: '.wpu_medical_consent_docx_escape($leftPlain)
                : 'Witness: ______________________';
            $xml = wpu_medical_consent_docx_replace_once(
                $xml,
                $leftBlank,
                '<w:t>'.$witnessLabel.'</w:t>'
            );
        } elseif (str_contains($leftBlank, 'xml:space="preserve"')) {
            $xml = wpu_medical_consent_docx_replace_once(
                $xml,
                $leftBlank,
                '<w:t xml:space="preserve"> '.$leftEsc.' </w:t>'
            );
        } else {
            $xml = wpu_medical_consent_docx_replace_once($xml, $leftBlank, '<w:t>'.$leftEsc.'</w:t>');
        }
    }

    if ($rightEsc !== '') {
        $xml = wpu_medical_consent_docx_replace_once(
            $xml,
            $rightBlank,
            '<w:t xml:space="preserve"> '.$rightEsc.'</w:t>'
        );
    }

    return $xml;
}

function wpu_refusal_consent_try_generate_pdf(array $row): array
{
    $soffice = wpu_medical_consent_find_libreoffice();
    if ($soffice === null) {
        return ['ok' => false, 'message' => 'LibreOffice is not installed; use DOCX or browser print.'];
    }

    try {
        $docx = wpu_refusal_consent_generate_docx($row);
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }

    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rc_pdf_'.bin2hex(random_bytes(4));
    if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
        return ['ok' => false, 'message' => 'Unable to create temp directory for PDF.'];
    }

    $docxPath = $dir.DIRECTORY_SEPARATOR.'refusal.docx';
    $pdfPath = $dir.DIRECTORY_SEPARATOR.'refusal.pdf';
    file_put_contents($docxPath, $docx);

    $cmd = '"'.$soffice.'" --headless --convert-to pdf --outdir '.escapeshellarg($dir).' '.escapeshellarg($docxPath);
    $output = [];
    $code = 1;
    exec($cmd, $output, $code);
    if ($code !== 0 || ! is_readable($pdfPath)) {
        wpu_medical_consent_rrmdir($dir);

        return ['ok' => false, 'message' => 'PDF conversion failed.'];
    }

    $binary = (string) file_get_contents($pdfPath);
    wpu_medical_consent_rrmdir($dir);

    if ($binary === '') {
        return ['ok' => false, 'message' => 'PDF conversion produced an empty file.'];
    }

    return ['ok' => true, 'binary' => $binary];
}
