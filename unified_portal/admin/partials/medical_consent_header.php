<?php
/** @var callable $mc_esc */
?>
<div class="mc-header">
    <img src="<?php echo $mc_esc($mc_logo_url ?? '../assets/images/logo.png'); ?>" alt="" class="mc-logo">
    <div class="mc-header-text">
        <p class="mc-republic"><?php echo $mc_esc($mc_univ['republic'] ?? 'Republic of the Philippines'); ?></p>
        <p class="mc-univ-name"><?php echo $mc_esc($mc_univ['name'] ?? 'Western Philippines University'); ?></p>
        <p class="mc-tagline"><?php echo $mc_esc($mc_univ['tagline'] ?? 'A STRONG PARTNER FOR SUSTAINABLE DEVELOPMENT'); ?></p>
        <p class="mc-office"><?php echo $mc_esc($mc_cfg['office_title'] ?? 'HEALTH SERVICES OFFICE'); ?></p>
    </div>
</div>
