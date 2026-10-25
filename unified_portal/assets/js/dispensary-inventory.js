/**
 * Dispensary inventory — list, add items, and stock movements via Laravel API.
 */
(function (window) {
    'use strict';

    function appUrl(path) {
        const base = document.querySelector('meta[name="app-base"]')?.content || '';
        const normalized = String(path || '').startsWith('/') ? path : `/${path}`;
        return `${base}${normalized}`;
    }

    function formatQty(value) {
        const n = Number(value);
        if (Number.isNaN(n)) {
            return '0';
        }
        return n % 1 === 0 ? String(n) : n.toFixed(2);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    async function parseJsonResponse(response) {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const msg = data.message
                || (data.errors && Object.values(data.errors).flat().join(' '))
                || 'Request failed.';
            throw new Error(msg);
        }
        return data;
    }

    function setStatus(el, message, type) {
        if (!el) {
            return;
        }
        if (!message) {
            el.hidden = true;
            el.textContent = '';
            el.className = 'dispensary-inline-status';
            el.setAttribute('role', 'status');
            return;
        }
        el.hidden = false;
        el.textContent = message;
        el.className = 'dispensary-inline-status alert '
            + (type === 'error' ? 'alert-error' : 'alert-success');
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    }

    function renderRows(tbody, items, canManage) {
        if (!tbody) {
            return;
        }
        const colSpan = canManage ? 7 : 6;
        if (!items || items.length === 0) {
            const hint = canManage ? ' Add one using the form above.' : '';
            tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-muted">No inventory items yet.${hint}</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((item) => {
            const onHand = Number(item.quantity_on_hand);
            const reorder = Number(item.reorder_threshold || 0);
            const low = reorder > 0 && onHand <= reorder;
            const exp = item.expiration_date ? String(item.expiration_date).slice(0, 10) : '—';
            const unit = item.unit_of_measure || 'pcs';
            const actions = canManage
                ? `<button type="button" class="btn btn-secondary btn-sm js-dispensary-stock" data-id="${item.id}" data-name="${escapeHtml(item.item_name)}">Adjust stock</button>`
                : '';

            const actionCell = canManage ? `<td>${actions}</td>` : '';
            return `<tr class="${low ? 'dispensary-row-low' : ''}" data-item-id="${item.id}">
                <td>${escapeHtml(item.item_name)}</td>
                <td>${escapeHtml(item.category || '—')}</td>
                <td>${escapeHtml(unit)}</td>
                <td>${escapeHtml(formatQty(onHand))}${low ? ' <span class="badge badge-warning" title="At or below reorder level">Low</span>' : ''}</td>
                <td>${escapeHtml(formatQty(reorder))}</td>
                <td>${escapeHtml(exp)}</td>
                ${actionCell}
            </tr>`;
        }).join('');
    }

    async function loadItems(config) {
        const response = await fetch(appUrl('/api/dispensary/items'), {
            credentials: 'same-origin',
        });
        const data = await parseJsonResponse(response);
        renderRows(config.tbody, data.data || [], config.canManage);
    }

    function resolveCategory(form) {
        const selected = form.category?.value?.trim() || '';
        if (selected === '__custom__') {
            return form.category_custom?.value?.trim() || null;
        }
        return selected || null;
    }

    function bindCategoryField(form) {
        const select = form.category;
        const wrap = document.getElementById('disp_category_custom_wrap');
        const custom = form.category_custom;
        if (!select || !wrap || !custom) {
            return;
        }
        const sync = () => {
            const isCustom = select.value === '__custom__';
            wrap.hidden = !isCustom;
            custom.required = isCustom;
            if (!isCustom) {
                custom.value = '';
            }
        };
        select.addEventListener('change', sync);
        sync();
    }

    async function createItem(form, config) {
        const payload = {
            item_name: form.item_name.value.trim(),
            category: resolveCategory(form),
            unit_of_measure: form.unit_of_measure.value.trim() || 'pcs',
            quantity_on_hand: form.quantity_on_hand.value === '' ? 0 : Number(form.quantity_on_hand.value),
            reorder_threshold: form.reorder_threshold.value === '' ? 0 : Number(form.reorder_threshold.value),
            expiration_date: form.expiration_date.value || null,
            supplier: form.supplier.value.trim() || null,
            remarks: form.remarks.value.trim() || null,
        };

        const response = await fetch(appUrl('/api/dispensary/items'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        await parseJsonResponse(response);
        form.reset();
        if (form.unit_of_measure) {
            form.unit_of_measure.value = 'pcs';
        }
        setStatus(config.statusEl, 'Item added to inventory.', 'success');
        await loadItems(config);
    }

    function openStockModal(config, itemId, itemName) {
        const modal = config.modal;
        const form = config.moveForm;
        if (!modal || !form) {
            return;
        }
        form.item_id.value = itemId;
        config.modalTitle.textContent = itemName ? `Adjust stock — ${itemName}` : 'Adjust stock';
        form.quantity.value = '';
        form.notes.value = '';
        form.movement_type.value = 'receive';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeStockModal(config) {
        const modal = config.modal;
        if (!modal) {
            return;
        }
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    async function submitMovement(form, config) {
        const itemId = form.item_id.value;
        const body = {
            movement_type: form.movement_type.value,
            quantity: Number(form.quantity.value),
            notes: form.notes.value.trim() || null,
        };
        if (form.movement_type.value === 'adjustment') {
            body.increase = form.adjustment_direction.value === 'increase';
        }

        const response = await fetch(appUrl(`/api/dispensary/items/${itemId}/movements`), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        await parseJsonResponse(response);
        closeStockModal(config);
        setStatus(config.statusEl, 'Stock movement recorded.', 'success');
        await loadItems(config);
    }

    function bindAdjustmentFields(form) {
        const typeSelect = form.movement_type;
        const directionWrap = form.querySelector('[data-adjustment-direction]');
        if (!typeSelect || !directionWrap) {
            return;
        }
        const sync = () => {
            directionWrap.hidden = typeSelect.value !== 'adjustment';
        };
        typeSelect.addEventListener('change', sync);
        sync();
    }

    window.WpuDispensaryInventory = {
        init(options) {
            const config = {
                tbody: document.getElementById(options.tbodyId || 'dispensaryInventoryBody'),
                statusEl: document.getElementById(options.statusId || 'dispensaryStatus'),
                addForm: document.getElementById(options.addFormId || 'dispensaryAddForm'),
                canManage: !!options.canManage,
                modal: document.getElementById(options.modalId || 'dispensaryStockModal'),
                moveForm: document.getElementById(options.moveFormId || 'dispensaryStockForm'),
                modalTitle: document.getElementById(options.modalTitleId || 'dispensaryStockModalTitle'),
            };

            if (config.addForm && config.canManage) {
                bindCategoryField(config.addForm);
                config.addForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    setStatus(config.statusEl, '', 'success');
                    createItem(config.addForm, config).catch((err) => {
                        setStatus(config.statusEl, err.message || 'Could not add item.', 'error');
                    });
                });
            }

            if (config.tbody) {
                config.tbody.addEventListener('click', (event) => {
                    const btn = event.target.closest('.js-dispensary-stock');
                    if (!btn) {
                        return;
                    }
                    openStockModal(config, btn.getAttribute('data-id'), btn.getAttribute('data-name'));
                });
            }

            if (config.moveForm && config.canManage) {
                bindAdjustmentFields(config.moveForm);
                config.moveForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    setStatus(config.statusEl, '', 'success');
                    submitMovement(config.moveForm, config).catch((err) => {
                        setStatus(config.statusEl, err.message || 'Could not record movement.', 'error');
                    });
                });
            }

            document.querySelectorAll('[data-dispensary-modal-close]').forEach((el) => {
                el.addEventListener('click', () => closeStockModal(config));
            });

            if (config.modal) {
                config.modal.addEventListener('click', (event) => {
                    if (event.target === config.modal) {
                        closeStockModal(config);
                    }
                });
            }

            loadItems(config).catch(() => {
                /* SSR table remains if API fails */
            });
        },
    };
})(window);
