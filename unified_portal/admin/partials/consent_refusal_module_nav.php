<?php
/**
 * In-module navigation for Consent & Refusal workspace pages.
 *
 * Expects: $page (current workspace page key)
 */
require_once __DIR__.'/../../includes/wpu_consent_refusal_module.php';

$cr_mod_page = $page ?? '';
$cr_mod_tabs = [
    'consent_refusal_module' => ['label' => 'Module home', 'href' => '?page=consent_refusal_module', 'icon' => 'fa-th-large'],
    'secure_consent' => ['label' => 'Medical consent', 'href' => '?page=secure_consent', 'icon' => 'fa-file-signature'],
    'view_consent' => ['label' => 'Consent records', 'href' => '?page=view_consent', 'icon' => 'fa-folder-open'],
    'consent_refusal' => ['label' => 'Refusal form', 'href' => '?page=consent_refusal', 'icon' => 'fa-hand-paper'],
    'view_refusal' => ['label' => 'Refusal records', 'href' => '?page=view_refusal', 'icon' => 'fa-folder'],
];
?>
<nav class="cr-tabs cr-module-nav" role="tablist" aria-label="Consent and refusal module" style="margin-bottom:1rem">
    <?php foreach ($cr_mod_tabs as $key => $tab): ?>
        <a href="<?php echo htmlspecialchars($tab['href'], ENT_QUOTES, 'UTF-8'); ?>"
           class="cr-tab <?php echo $cr_mod_page === $key ? 'is-active' : ''; ?>">
            <i class="fas <?php echo htmlspecialchars($tab['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
            <?php echo htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    <?php endforeach; ?>
</nav>
