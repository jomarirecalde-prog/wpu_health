<?php
/**
 * Consent linkage fields for health/dental record modals.
 *
 * @var string $rcf_prefix  Field id prefix, e.g. health_, dental_edit_
 * @var string $rcf_module  health|dental
 * @var list<string> $rcf_transactions
 */
$rcf_prefix = $rcf_prefix ?? 'health_';
$rcf_module = $rcf_module ?? 'health';
$rcf_transactions = $rcf_transactions ?? [];
if ($rcf_transactions === [] && function_exists('wpu_healthhub_transactions')) {
    $rcf_transactions = wpu_healthhub_transactions();
}
if ($rcf_module === 'dental') {
    $rcf_transactions = array_values(array_unique(array_merge(['Dental'], $rcf_transactions)));
}
?>
<div class="form-group full-width" style="grid-column: 1 / -1; border-top: 1px solid var(--his-border,#e2e8f0); padding-top: 1rem; margin-top: 0.5rem;">
    <label class="form-label"><i class="fas fa-file-signature" aria-hidden="true"></i> Informed consent</label>
    <p class="form-helper" style="margin: 0 0 0.75rem;">A <strong>completed</strong> medical consent is required for this visit type. <a href="?page=consent_refusal_module" target="_blank" rel="noopener">Open Consent &amp; Refusal module</a></p>
</div>
<div class="form-group">
    <label class="form-label" for="<?php echo htmlspecialchars($rcf_prefix, ENT_QUOTES, 'UTF-8'); ?>transaction_type">Transaction / visit type *</label>
    <select id="<?php echo htmlspecialchars($rcf_prefix, ENT_QUOTES, 'UTF-8'); ?>transaction_type" name="transaction_type" required>
        <option value="">Select transaction</option>
        <?php foreach ($rcf_transactions as $tx): ?>
            <option value="<?php echo htmlspecialchars($tx, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($rcf_module === 'dental' && $tx === 'Dental') ? ' selected' : ''; ?>><?php echo htmlspecialchars($tx, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group">
    <label class="form-label" for="<?php echo htmlspecialchars($rcf_prefix, ENT_QUOTES, 'UTF-8'); ?>consent_record_id">Completed consent record ID *</label>
    <input type="number" min="1" step="1" required id="<?php echo htmlspecialchars($rcf_prefix, ENT_QUOTES, 'UTF-8'); ?>consent_record_id" name="consent_record_id" class="consent-record-id-input" placeholder="e.g. 12">
    <p class="form-helper">From <a href="?page=view_consent">Consent records</a> after physician certification, or start from the <a href="?page=consent_refusal_module">module home</a>.</p>
</div>
<?php if (! empty($rcf_show_bypass)): ?>
<div class="form-group full-width consent-bypass-panel" data-consent-bypass-panel>
    <label class="form-label">Emergency clinical bypass <span class="form-helper">(authorized roles only)</span></label>
    <label><input type="checkbox" name="consent_emergency_bypass" value="1"> Bypass consent requirement for this save</label>
    <textarea name="consent_bypass_reason" rows="2" placeholder="Clinical justification (required for bypass)" style="margin-top:0.5rem"></textarea>
</div>
<?php endif; ?>
