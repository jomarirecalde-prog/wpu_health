<?php
$schemaReady = wpu_schema_has_column($pdo, 'dispensary_items', 'id');
$canManageDispensary = wpu_healthhub_can_manage_dispensary($clinical_role ?? 'guest');
$items = [];

if ($schemaReady) {
    try {
        $stmt = $pdo->query(
            'SELECT id, item_name, category, unit_of_measure, quantity_on_hand, reorder_threshold, expiration_date
             FROM dispensary_items ORDER BY item_name LIMIT 500'
        );
        $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException) {
        $items = [];
    }
}

$dispensary_js_v = @filemtime(__DIR__.'/../../assets/js/dispensary-inventory.js') ?: time();
?>
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-pills" aria-hidden="true"></i> Dispensary &amp; Inventory</h2>
    </div>
    <div class="card-body">
        <p class="settings-intro">Manage medicines and supplies, stock movements, expiration monitoring, and dispensing linked to patient care when applicable.</p>

        <div id="dispensaryStatus" class="dispensary-inline-status" role="status" hidden aria-live="polite"></div>

        <?php if (! $schemaReady): ?>
            <p class="form-helper" role="alert">Inventory tables are not installed yet. From the project root run <code>php artisan migrate</code>, then reload this page.</p>
        <?php else: ?>
            <?php if ($canManageDispensary): ?>
            <details class="dispensary-add-panel" open>
                <summary class="dispensary-add-summary"><i class="fas fa-plus-circle" aria-hidden="true"></i> Add inventory item</summary>
                <form id="dispensaryAddForm" class="settings-form-grid-2" style="margin-top:1rem">
                    <div class="form-group full-width">
                        <label for="disp_item_name">Item name *</label>
                        <input type="text" class="form-control" id="disp_item_name" name="item_name" required maxlength="255" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="disp_category">Category</label>
                        <select class="form-control" id="disp_category" name="category">
                            <option value="">— Select category —</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Supplies">Supplies</option>
                            <option value="Equipment">Equipment</option>
                            <option value="__custom__">Other…</option>
                        </select>
                    </div>
                    <div class="form-group" id="disp_category_custom_wrap" hidden>
                        <label for="disp_category_custom">Custom category</label>
                        <input type="text" class="form-control" id="disp_category_custom" name="category_custom" maxlength="100" autocomplete="off" placeholder="Enter category name">
                    </div>
                    <div class="form-group">
                        <label for="disp_unit">Unit</label>
                        <input type="text" class="form-control" id="disp_unit" name="unit_of_measure" maxlength="32" value="pcs">
                    </div>
                    <div class="form-group">
                        <label for="disp_qty">Initial quantity on hand</label>
                        <input type="number" class="form-control" id="disp_qty" name="quantity_on_hand" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label for="disp_reorder">Reorder threshold</label>
                        <input type="number" class="form-control" id="disp_reorder" name="reorder_threshold" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label for="disp_expiration">Expiration date</label>
                        <input type="date" class="form-control" id="disp_expiration" name="expiration_date">
                    </div>
                    <div class="form-group">
                        <label for="disp_supplier">Supplier</label>
                        <input type="text" class="form-control" id="disp_supplier" name="supplier" maxlength="255">
                    </div>
                    <div class="form-group full-width">
                        <label for="disp_remarks">Remarks</label>
                        <textarea class="form-control" id="disp_remarks" name="remarks" rows="2" maxlength="5000"></textarea>
                    </div>
                    <div class="settings-form-actions full-width">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save" aria-hidden="true"></i> Save item</button>
                    </div>
                </form>
            </details>
            <?php endif; ?>

            <div class="table-responsive" style="margin-top:1.5rem">
                <table class="data-table" id="dispensaryInventoryTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>On hand</th>
                            <th>Reorder at</th>
                            <th>Expiration</th>
                            <?php if ($canManageDispensary): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="dispensaryInventoryBody">
                        <?php if ($items === []): ?>
                        <tr>
                            <td colspan="<?php echo $canManageDispensary ? 7 : 6; ?>" class="text-muted">No inventory items yet.<?php echo $canManageDispensary ? ' Add one using the form above.' : ''; ?></td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($items as $row):
                            $onHand = (float) ($row['quantity_on_hand'] ?? 0);
                            $reorder = (float) ($row['reorder_threshold'] ?? 0);
                            $low = $reorder > 0 && $onHand <= $reorder;
                            ?>
                        <tr class="<?php echo $low ? 'dispensary-row-low' : ''; ?>" data-item-id="<?php echo (int) ($row['id'] ?? 0); ?>">
                            <td><?php echo htmlspecialchars($row['item_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['category'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($row['unit_of_measure'] ?? 'pcs'); ?></td>
                            <td>
                                <?php echo htmlspecialchars(rtrim(rtrim(number_format($onHand, 2, '.', ''), '0'), '.')); ?>
                                <?php if ($low): ?><span class="badge badge-warning" title="At or below reorder level">Low</span><?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(rtrim(rtrim(number_format($reorder, 2, '.', ''), '0'), '.')); ?></td>
                            <td><?php echo ! empty($row['expiration_date']) ? htmlspecialchars(substr((string) $row['expiration_date'], 0, 10)) : '—'; ?></td>
                            <?php if ($canManageDispensary): ?>
                            <td>
                                <button type="button" class="btn btn-secondary btn-sm js-dispensary-stock"
                                    data-id="<?php echo (int) ($row['id'] ?? 0); ?>"
                                    data-name="<?php echo htmlspecialchars($row['item_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">Adjust stock</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($schemaReady && $canManageDispensary): ?>
<div id="dispensaryStockModal" class="modal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="dispensaryStockModalTitle">
    <div class="modal-dialog" style="max-width:480px">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="dispensaryStockModalTitle">Adjust stock</h3>
                <button type="button" class="close" data-dispensary-modal-close aria-label="Close">&times;</button>
            </div>
            <form id="dispensaryStockForm">
                <input type="hidden" name="item_id" value="">
                <div class="modal-body settings-form-grid-2">
                    <div class="form-group full-width">
                        <label for="disp_move_type">Movement type</label>
                        <select class="form-control" id="disp_move_type" name="movement_type">
                            <option value="receive">Receive (add stock)</option>
                            <option value="dispense">Dispense (remove)</option>
                            <option value="expired">Expired (remove)</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="form-group full-width" data-adjustment-direction hidden>
                        <label for="disp_adjust_dir">Adjustment direction</label>
                        <select class="form-control" id="disp_adjust_dir" name="adjustment_direction">
                            <option value="increase">Increase quantity</option>
                            <option value="decrease">Decrease quantity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="disp_move_qty">Quantity *</label>
                        <input type="number" class="form-control" id="disp_move_qty" name="quantity" required min="0.01" step="0.01">
                    </div>
                    <div class="form-group full-width">
                        <label for="disp_move_notes">Notes</label>
                        <textarea class="form-control" id="disp_move_notes" name="notes" rows="2" maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dispensary-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-success">Record movement</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($schemaReady): ?>
<style>
.dispensary-inline-status[hidden] {
    display: none !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    box-shadow: none !important;
}
.dispensary-inline-status:not([hidden]) {
    margin-bottom: 1rem;
}
.dispensary-inline-status.alert::after {
    display: none;
}
.dispensary-add-panel { margin-top: 1rem; border: 1px solid var(--his-border, #e2e8f0); border-radius: 8px; padding: 0.75rem 1rem; }
.dispensary-add-summary { cursor: pointer; font-weight: 600; list-style: none; }
.dispensary-add-summary::-webkit-details-marker { display: none; }
.dispensary-row-low td:nth-child(4) { color: var(--his-warning-text, #b45309); }
.badge.badge-warning { font-size: 0.75rem; margin-left: 0.35rem; padding: 0.1rem 0.4rem; border-radius: 4px; background: #fef3c7; color: #92400e; }
</style>
<script src="<?php echo htmlspecialchars($his_assets ?? '../assets', ENT_QUOTES, 'UTF-8'); ?>/js/dispensary-inventory.js?v=<?php echo (int) $dispensary_js_v; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.WpuDispensaryInventory) {
        window.WpuDispensaryInventory.init({
            canManage: <?php echo $canManageDispensary ? 'true' : 'false'; ?>
        });
    }
});
</script>
<?php endif; ?>
