<?php
$tab = $_GET['tab'] ?? 'interagency';
if (! in_array($tab, ['interagency', 'external'], true)) {
    $tab = 'interagency';
}
$interagency = function_exists('config') ? config('healthhub.interagency_units', []) : [];
$external = function_exists('config') ? config('healthhub.external_agencies', []) : [];
?>
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-share-square" aria-hidden="true"></i> Referral Management</h2>
    </div>
    <div class="card-body">
        <div class="cr-tabs" role="tablist">
            <a href="?page=referrals_hub&amp;tab=interagency" class="cr-tab <?php echo $tab === 'interagency' ? 'is-active' : ''; ?>">Interagency</a>
            <a href="?page=referrals_hub&amp;tab=external" class="cr-tab <?php echo $tab === 'external' ? 'is-active' : ''; ?>">External</a>
            <a href="?page=certificates_referrals&amp;tab=referrals" class="cr-tab">Legacy referrals</a>
        </div>

        <?php if ($tab === 'interagency'): ?>
            <p class="settings-intro" style="margin-top:1rem">Referrals between Health Services and other university units. Only disclose information necessary for the referral.</p>
            <ul class="form-helper">
                <?php foreach ($interagency as $unit): ?>
                    <li><?php echo htmlspecialchars($unit, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="form-helper">Full interagency workflow (create, status, acknowledgment) connects to <code>interagency_referrals</code> — implementation in progress.</p>
        <?php else: ?>
            <p class="settings-intro" style="margin-top:1rem">Referrals to external agencies and clinics.</p>
            <ul class="form-helper">
                <?php foreach ($external as $agency): ?>
                    <li><?php echo htmlspecialchars($agency, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="form-helper">Printable referral form will use the approved Word template when supplied.</p>
        <?php endif; ?>
    </div>
</div>
