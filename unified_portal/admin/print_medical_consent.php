<?php
require_once __DIR__.'/../includes/wpu_security.php';
wpu_bootstrap_admin_page();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/wpu_healthhub.php';
require_once __DIR__.'/../includes/wpu_medical_consent.php';
require_once __DIR__.'/../includes/wpu_medical_consent_docx.php';

$pdo = getDBConnection();
$role = wpu_healthhub_admin_role($pdo);

if (! wpu_medical_consent_can_view_full($role)) {
    http_response_code(403);
    echo 'You do not have permission to print this consent form.';

    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo 'Consent not found.';

    exit;
}

$stmt = $pdo->prepare('SELECT * FROM healthhub_consents WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (! $row) {
    http_response_code(404);
    echo 'Consent not found.';

    exit;
}

wpu_medical_consent_audit($pdo, $id, 'printed', (string) ($_SESSION['admin_username'] ?? 'system'), 'Print view opened');

$format = strtolower(trim((string) ($_GET['format'] ?? '')));
if ($format === 'docx') {
    try {
        $binary = wpu_medical_consent_generate_docx($row);
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Unable to generate consent document: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

        exit;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="Medical-Consent-'.$id.'.docx"');
    header('Content-Length: '.strlen($binary));
    echo $binary;

    exit;
}

if ($format === 'pdf') {
    $pdf = wpu_medical_consent_try_generate_pdf($row);
    if (! ($pdf['ok'] ?? false)) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo (string) ($pdf['message'] ?? 'PDF generation is unavailable.');

        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Medical-Consent-'.$id.'.pdf"');
    header('Content-Length: '.strlen((string) $pdf['binary']));
    echo $pdf['binary'];

    exit;
}

$ctx = wpu_medical_consent_print_context($row);
extract($ctx, EXTR_SKIP);
$mc_logo_url = '../assets/images/logo.png';

$status = (string) ($row['consent_status'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Consent Form</title>
    <link rel="stylesheet" href="../assets/css/medical-consent.css?v=2">
    <style>
        body {
            margin: 0;
            padding: 1rem;
            background: #f1f5f9;
        }
        .print-toolbar {
            max-width: 6.5in;
            margin: 0 auto 1rem;
            font-family: system-ui, sans-serif;
            font-size: 14px;
        }
        .print-banner {
            font-size: 10pt;
            color: #92400e;
            background: #fef3c7;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.75rem;
            border-radius: 6px;
        }
        .print-toolbar .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .print-toolbar button,
        .print-toolbar a.btn-link {
            font: inherit;
            padding: 0.45rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            cursor: pointer;
        }
        .print-toolbar .hint {
            margin: 0.5rem 0 0;
            color: #475569;
            font-size: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <?php if ($status !== 'completed'): ?>
            <div class="print-banner">
                This consent is <strong><?php echo htmlspecialchars(wpu_medical_consent_status_label($status), ENT_QUOTES, 'UTF-8'); ?></strong>.
                The printed copy is not a fully executed document until physician certification is complete.
            </div>
        <?php endif; ?>
        <div class="actions">
            <button type="button" onclick="window.print()">Print / Save as PDF</button>
            <a class="btn-link" href="print_medical_consent.php?id=<?php echo $id; ?>&amp;format=docx">Download official DOCX</a>
            <a class="btn-link" href="print_medical_consent.php?id=<?php echo $id; ?>&amp;format=pdf">Open server PDF</a>
        </div>
        <p class="hint">For layout matching the approved Word template, prefer <strong>Download official DOCX</strong> or server PDF. In the browser print dialog, turn off headers and footers to avoid URLs and page titles.</p>
    </div>
    <?php include __DIR__.'/partials/medical_consent_document.php'; ?>
</body>
</html>
