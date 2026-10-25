(function () {
    function notify(message, type) {
        if (typeof AlertSystem !== 'undefined' && AlertSystem.show) {
            AlertSystem.show(message, type || 'info');
            return;
        }
        alert(message);
    }

    /**
     * @param {{ formId?: string, canvasId: string, hiddenId: string, clearId: string, redoId?: string, optional?: boolean }} cfg
     * @returns {{ canvas: HTMLCanvasElement, hidden: HTMLInputElement, isOptional: boolean, hasInk: function, clear: function, syncHidden: function }}
     */
    function createSignaturePad(cfg) {
        var canvas = document.getElementById(cfg.canvasId);
        var hidden = document.getElementById(cfg.hiddenId);
        if (!canvas || !hidden) {
            return null;
        }

        var ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';

        var drawing = false;
        var hasInk = false;

        function pos(e) {
            var rect = canvas.getBoundingClientRect();
            var clientX = e.clientX;
            var clientY = e.clientY;
            if (e.touches && e.touches[0]) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }
            return {
                x: ((clientX - rect.left) / rect.width) * canvas.width,
                y: ((clientY - rect.top) / rect.height) * canvas.height,
            };
        }

        function clearPad() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasInk = false;
            hidden.value = '';
        }

        function start(e) {
            drawing = true;
            var p = pos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }

        function move(e) {
            if (!drawing) return;
            var p = pos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            hasInk = true;
            e.preventDefault();
        }

        function end() {
            drawing = false;
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        var clearBtn = document.getElementById(cfg.clearId);
        if (clearBtn) {
            clearBtn.addEventListener('click', clearPad);
        }
        var redoBtn = cfg.redoId ? document.getElementById(cfg.redoId) : null;
        if (redoBtn) {
            redoBtn.addEventListener('click', clearPad);
        }

        return {
            canvas: canvas,
            hidden: hidden,
            isOptional: !!cfg.optional,
            hasInk: function () {
                return hasInk;
            },
            clear: clearPad,
            syncHidden: function () {
                if (hasInk) {
                    hidden.value = canvas.toDataURL('image/png');
                }
            },
        };
    }

    var padsByForm = {};

    document.querySelectorAll('[data-healthhub-signature]').forEach(function (el) {
        try {
            var cfg = JSON.parse(el.getAttribute('data-healthhub-signature') || '{}');
            var formId = cfg.formId || 'healthhubConsentForm';
            var pad = createSignaturePad(cfg);
            if (!pad) return;
            if (!padsByForm[formId]) {
                padsByForm[formId] = [];
            }
            padsByForm[formId].push(pad);
        } catch (err) {
            // ignore
        }
    });

    Object.keys(padsByForm).forEach(function (formId) {
        var form = document.getElementById(formId);
        if (!form) return;
        var pads = padsByForm[formId];

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            for (var i = 0; i < pads.length; i++) {
                var pad = pads[i];
                if (!pad.isOptional && !pad.hasInk()) {
                    notify('Please sign in the required digital signature box before saving.', 'error');
                    pad.canvas.focus();
                    return;
                }
                pad.syncHidden();
            }

            var body = new FormData(form);
            var actionUrl = form.getAttribute('action') || '';
            if (!actionUrl) {
                notify('Form is missing a submit URL.', 'error');
                return;
            }
            var submitBtn = e.submitter || form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            fetch(actionUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) {
                    return r.text().then(function (text) {
                        var data = null;
                        try {
                            data = text ? JSON.parse(text) : null;
                        } catch (err) {
                            data = null;
                        }
                        if (!data) {
                            throw new Error(r.ok ? 'Unexpected server response.' : 'Request failed (' + r.status + ').');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    notify(data.message || (data.success ? 'Saved.' : 'Error.'), data.success ? 'success' : 'error');
                    if (data.success) {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        form.reset();
                        pads.forEach(function (p) {
                            p.clear();
                        });
                    }
                })
                .catch(function (err) {
                    notify(err && err.message ? err.message : 'Could not save.', 'error');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        });
    });

    window.healthhubSubmitConsentDraft = function (formId) {
        var form = document.getElementById(formId || 'healthhubConsentForm');
        if (!form) return;
        var actionInput = form.querySelector('input[name="action"]');
        if (!actionInput) return;
        var prev = actionInput.value;
        actionInput.value = 'save_draft';
        var body = new FormData(form);
        actionInput.value = prev;
        var actionUrl = form.getAttribute('action') || '';
        fetch(actionUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                notify(data.message || 'Draft saved.', data.success ? 'success' : 'error');
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(function () {
                notify('Could not save draft.', 'error');
            });
    };
})();
