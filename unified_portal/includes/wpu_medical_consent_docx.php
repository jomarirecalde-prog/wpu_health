<?php

declare(strict_types=1);

require_once __DIR__.'/wpu_medical_consent.php';

/**
 * @param  array<string, mixed>  $row
 */
function wpu_medical_consent_generate_docx(array $row): string
{
    $template = wpu_medical_consent_docx_template_path();
    if ($template === null) {
        throw new RuntimeException('Medical consent Word template is missing.');
    }

    $ctx = wpu_medical_consent_print_context($row);
    $physician = (string) $ctx['mc_physician'];

    $tmpIn = tempnam(sys_get_temp_dir(), 'mc_tpl_');
    $tmpOut = tempnam(sys_get_temp_dir(), 'mc_out_');
    if ($tmpIn === false || $tmpOut === false) {
        throw new RuntimeException('Unable to allocate temporary files for consent DOCX.');
    }

    copy($template, $tmpIn);
    @unlink($tmpOut);

    $zip = new ZipArchive();
    if ($zip->open($tmpIn) !== true) {
        @unlink($tmpIn);
        throw new RuntimeException('Unable to open consent template.');
    }

    $documentXml = (string) $zip->getFromName('word/document.xml');
    if ($documentXml === '') {
        $zip->close();
        @unlink($tmpIn);
        throw new RuntimeException('Consent template document.xml is missing.');
    }

    $relsPath = 'word/_rels/document.xml.rels';
    $relsXml = (string) $zip->getFromName($relsPath);
    $contentTypesPath = '[Content_Types].xml';
    $contentTypesXml = (string) $zip->getFromName($contentTypesPath);

    $documentXml = wpu_medical_consent_docx_sync_physician_name($documentXml, $physician);
    $documentXml = wpu_medical_consent_docx_force_page2_break($documentXml);

    $treatment = wpu_medical_consent_docx_trim_field((string) $ctx['mc_treatment'], 120);
    $namedPatient = wpu_medical_consent_docx_trim_field((string) $ctx['mc_named_patient'], 80);

    $treatmentCell = $treatment !== ''
        ? wpu_medical_consent_docx_escape($treatment).','
        : '___________________________,';
    $documentXml = wpu_medical_consent_docx_replace_once($documentXml, '___________________________,', $treatmentCell);

    $namedLinePrefix = 'should be given to me or the named patient (if a minor): ________________________';
    $namedReplacement = 'should be given to me or the named patient (if a minor): '.wpu_medical_consent_docx_escape($namedPatient !== '' ? $namedPatient : '________________________');
    $documentXml = wpu_medical_consent_docx_replace_once($documentXml, $namedLinePrefix, $namedReplacement);

    if ($namedPatient !== '') {
        $documentXml = wpu_medical_consent_docx_replace_once($documentXml, '__________', wpu_medical_consent_docx_escape(''));
    }

    $patientLeft = wpu_medical_consent_docx_sig_left_label(
        (string) $ctx['mc_patient_name'],
        (string) $ctx['mc_patient_relationship']
    );
    $patientRight = wpu_medical_consent_docx_escape((string) $ctx['mc_patient_date']);
    $patientLineSearch = ' '.str_repeat('_', 27)."\xC2\xA0".str_repeat(' ', 50).str_repeat('_', 19);
    $documentXml = wpu_medical_consent_docx_replace_dual_line(
        $documentXml,
        $patientLineSearch,
        $patientLeft,
        $patientRight
    );

    $interpreterLeft = wpu_medical_consent_docx_sig_left_label((string) $ctx['mc_interpreter_name'], '');
    $interpreterRight = wpu_medical_consent_docx_escape((string) $ctx['mc_interpreter_date']);
    $documentXml = wpu_medical_consent_docx_replace_dual_line(
        $documentXml,
        str_repeat('_', 28).str_repeat(' ', 50).str_repeat('_', 20),
        $interpreterLeft,
        $interpreterRight
    );

    $witnessPrefix = 'Witness: ______________________';
    if (str_contains($documentXml, $witnessPrefix)) {
        $witnessLeft = wpu_medical_consent_docx_sig_left_label((string) $ctx['mc_witness_name'], '');
        $witnessRight = wpu_medical_consent_docx_escape((string) $ctx['mc_witness_date']);
        $witnessSearch = 'Witness: ______________________'.str_repeat(' ', 49).str_repeat('_', 20);
        $witnessReplace = 'Witness: '.wpu_medical_consent_docx_pad_field($witnessLeft, 22).str_repeat(' ', 49).wpu_medical_consent_docx_pad_field($witnessRight, 20);
        $documentXml = wpu_medical_consent_docx_replace_once($documentXml, $witnessSearch, $witnessReplace);
    }

    $physicianDate = wpu_medical_consent_docx_escape((string) $ctx['mc_physician_cert_date']);
    $documentXml = wpu_medical_consent_docx_replace_once(
        $documentXml,
        '____________',
        $physicianDate !== '' ? $physicianDate : '____________'
    );

    $embedIndex = 0;
    foreach ([
        ['field' => 'mc_patient_sig', 'marker' => 'Patient/Relative or Guardian'],
        ['field' => 'mc_interpreter_sig', 'marker' => 'Interpreter (if used):'],
        ['field' => 'mc_witness_sig', 'marker' => 'Witness:'],
        ['field' => 'mc_physician_sig', 'marker' => 'Physician:'],
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
            'mc_sig_'.$embedIndex.'.png'
        );
    }

    $printedPhysician = trim((string) $ctx['mc_physician_printed']);
    if ($printedPhysician !== '' && strcasecmp($printedPhysician, $physician) !== 0) {
        $documentXml = str_replace(
            wpu_medical_consent_docx_escape($physician),
            wpu_medical_consent_docx_escape($printedPhysician),
            $documentXml
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
        throw new RuntimeException('Generated consent DOCX is empty.');
    }

    return $binary;
}

function wpu_medical_consent_docx_escape(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function wpu_medical_consent_docx_trim_field(string $text, int $maxLen): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $maxLen) {
        return $text;
    }

    return mb_substr($text, 0, $maxLen - 1).'…';
}

function wpu_medical_consent_docx_replace_once(string $xml, string $search, string $replacement): string
{
    $pos = strpos($xml, $search);
    if ($pos === false) {
        return $xml;
    }

    return substr_replace($xml, $replacement, $pos, strlen($search));
}

function wpu_medical_consent_docx_sync_physician_name(string $xml, string $physician): string
{
    $legacy = [
        'MICAELA T. BAGALANON- LABUTOY, MD, OHP, SHC',
        'MICAELA T. BAGALANON-LABUTOY, MD, OHP, SHC',
    ];
    $escaped = wpu_medical_consent_docx_escape($physician);
    foreach ($legacy as $old) {
        $xml = str_replace(wpu_medical_consent_docx_escape($old), $escaped, $xml);
        $xml = str_replace($old, $escaped, $xml);
    }

    return $xml;
}

function wpu_medical_consent_docx_force_page2_break(string $xml): string
{
    $needle = '<w:t>Physician Certification</w:t>';
    $pos = strpos($xml, $needle);
    if ($pos === false) {
        return $xml;
    }
    $pStart = strrpos(substr($xml, 0, $pos), '<w:p ');
    if ($pStart === false) {
        return $xml;
    }
    $pPrPos = strpos($xml, '<w:pPr>', $pStart);
    if ($pPrPos === false || $pPrPos > $pos) {
        return $xml;
    }
    if (str_contains(substr($xml, $pStart, $pos - $pStart), 'pageBreakBefore')) {
        return $xml;
    }

    return substr_replace($xml, '<w:pPr><w:pageBreakBefore w:val="1"/>', $pPrPos, strlen('<w:pPr>'));
}

function wpu_medical_consent_docx_sig_left_label(string $name, string $relationship): string
{
    $name = trim($name);
    $relationship = trim($relationship);
    if ($name === '') {
        return '';
    }
    if ($relationship !== '') {
        return wpu_medical_consent_docx_escape($name.', '.$relationship);
    }

    return wpu_medical_consent_docx_escape($name);
}

function wpu_medical_consent_docx_pad_field(string $text, int $width): string
{
    if ($text === '') {
        return str_repeat('_', $width);
    }
    $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (mb_strlen($plain) >= $width) {
        return wpu_medical_consent_docx_escape($plain);
    }

    return wpu_medical_consent_docx_escape($plain).str_repeat('_', max(0, $width - mb_strlen($plain)));
}

function wpu_medical_consent_docx_replace_dual_line(
    string $xml,
    string $search,
    string $left,
    string $right
): string {
    $leftPlain = $left !== '' ? html_entity_decode(strip_tags($left), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    $rightPlain = $right !== '' ? html_entity_decode(strip_tags($right), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    $leftCell = $leftPlain !== '' ? wpu_medical_consent_docx_escape($leftPlain) : str_repeat('_', 27);
    $rightCell = $rightPlain !== '' ? wpu_medical_consent_docx_escape($rightPlain) : str_repeat('_', 19);
    $replacement = $leftCell.str_repeat(' ', 50).$rightCell;

    return wpu_medical_consent_docx_replace_once($xml, $search, $replacement);
}

function wpu_medical_consent_docx_png_from_data_url(string $dataUrl): ?string
{
    if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
        return null;
    }
    $raw = base64_decode($m[1], true);
    if ($raw === false || ! str_starts_with($raw, "\x89PNG\r\n\x1a\n")) {
        return null;
    }

    return $raw;
}

/**
 * @param -out  string  $relsXml
 * @param -out  string  $contentTypesXml
 */
function wpu_medical_consent_docx_insert_signature_image(
    ZipArchive $zip,
    string $documentXml,
    string &$relsXml,
    string &$contentTypesXml,
    string $sectionMarker,
    string $pngBinary,
    string $mediaName
): string {
    $markerPos = strpos($documentXml, '<w:t>'.wpu_medical_consent_docx_escape($sectionMarker).'</w:t>');
    if ($markerPos === false && $sectionMarker === 'Witness:') {
        $markerPos = strpos($documentXml, '<w:t>Witness:');
    }
    if ($markerPos === false) {
        return $documentXml;
    }

    $after = substr($documentXml, $markerPos);
    if (! preg_match('/<w:p [^>]*>.*?_{5,}.*?<\/w:p>/s', $after, $m, PREG_OFFSET_CAPTURE)) {
        return $documentXml;
    }
    $linePos = $markerPos + $m[0][1];

    $rid = wpu_medical_consent_docx_next_rel_id($relsXml);
    $mediaPath = 'word/media/'.$mediaName;
    $zip->addFromString($mediaPath, $pngBinary);

    $relsXml = wpu_medical_consent_docx_add_image_relationship($relsXml, $rid, 'media/'.$mediaName);
    $contentTypesXml = wpu_medical_consent_docx_ensure_png_content_type($contentTypesXml);

    $size = @getimagesizefromstring($pngBinary);
    $widthPx = (int) ($size[0] ?? 220);
    $heightPx = (int) ($size[1] ?? 60);
    $maxWidthPx = 220;
    if ($widthPx > $maxWidthPx && $widthPx > 0) {
        $scale = $maxWidthPx / $widthPx;
        $widthPx = $maxWidthPx;
        $heightPx = (int) max(1, round($heightPx * $scale));
    }
    $cx = (int) ($widthPx * 9525);
    $cy = (int) ($heightPx * 9525);

    $drawing = wpu_medical_consent_docx_build_inline_image($rid, $cx, $cy);
    $insert = '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr>'.$drawing.'</w:p>';

    return substr_replace($documentXml, $insert, $linePos, 0);
}

function wpu_medical_consent_docx_next_rel_id(string $relsXml): string
{
    preg_match_all('/Id="rId(\d+)"/', $relsXml, $m);
    $max = 0;
    foreach ($m[1] ?? [] as $num) {
        $max = max($max, (int) $num);
    }

    return 'rId'.($max + 1);
}

function wpu_medical_consent_docx_add_image_relationship(string $relsXml, string $rid, string $target): string
{
    if ($relsXml === '') {
        return $relsXml;
    }
    $rel = '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="'.$target.'"/>';

    return str_replace('</Relationships>', $rel.'</Relationships>', $relsXml);
}

function wpu_medical_consent_docx_ensure_png_content_type(string $contentTypesXml): string
{
    if ($contentTypesXml === '' || str_contains($contentTypesXml, 'Extension="png"')) {
        return $contentTypesXml;
    }

    return str_replace(
        '</Types>',
        '<Default Extension="png" ContentType="image/png"/></Types>',
        $contentTypesXml
    );
}

function wpu_medical_consent_docx_build_inline_image(string $relId, int $cx, int $cy): string
{
    return '<w:r><w:drawing>'
        .'<wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
        .'<wp:extent cx="'.$cx.'" cy="'.$cy.'"/>'
        .'<wp:docPr id="'.random_int(1000, 999999).'" name="Signature"/>'
        .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
        .'<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        .'<pic:nvPicPr><pic:cNvPr id="0" name="Signature"/><pic:cNvPicPr/></pic:nvPicPr>'
        .'<pic:blipFill><a:blip r:embed="'.$relId.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>'
        .'<a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
        .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm>'
        .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
        .'</pic:pic></a:graphicData></a:graphic>'
        .'</wp:inline></w:drawing></w:r>';
}

/**
 * @return array{ok: bool, binary?: string, message?: string}
 */
function wpu_medical_consent_try_generate_pdf(array $row): array
{
    if (! function_exists('wpu_medical_consent_generate_docx')) {
        require_once __DIR__.'/wpu_medical_consent_docx.php';
    }

    $soffice = wpu_medical_consent_find_libreoffice();
    if ($soffice === null) {
        return ['ok' => false, 'message' => 'LibreOffice is not installed; use DOCX or browser print.'];
    }

    try {
        $docx = wpu_medical_consent_generate_docx($row);
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }

    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mc_pdf_'.bin2hex(random_bytes(4));
    if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
        return ['ok' => false, 'message' => 'Unable to create temp directory for PDF.'];
    }

    $docxPath = $dir.DIRECTORY_SEPARATOR.'consent.docx';
    $pdfPath = $dir.DIRECTORY_SEPARATOR.'consent.pdf';
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

function wpu_medical_consent_find_libreoffice(): ?string
{
    $candidates = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }
    $which = trim((string) shell_exec('where soffice 2>NUL'));
    if ($which !== '' && is_readable(strtok($which, "\r\n"))) {
        return strtok($which, "\r\n") ?: null;
    }

    return null;
}

function wpu_medical_consent_rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir.DIRECTORY_SEPARATOR.$item;
        if (is_dir($path)) {
            wpu_medical_consent_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
