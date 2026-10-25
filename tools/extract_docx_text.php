<?php
$p = $argv[1] ?? '';
if ($p === '' || ! is_readable($p)) {
    fwrite(STDERR, "Usage: php extract_docx_text.php <path>\n");
    exit(1);
}
$z = new ZipArchive();
if ($z->open($p) !== true) {
    fwrite(STDERR, "Cannot open zip\n");
    exit(1);
}
$x = (string) $z->getFromName('word/document.xml');
$z->close();
$x = preg_replace('/<w:tab[^>]*\/>/', "\t", $x);
$x = preg_replace('/<\/w:p>/', "\n", $x);
$x = strip_tags($x);
echo $x;
