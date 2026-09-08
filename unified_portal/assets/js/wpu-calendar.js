/**
 * WPU Medical — shared FullCalendar configuration
 */
(function (window) {
    'use strict';

    const MOBILE_BREAK = 640;

    function isMobile() {
        return window.innerWidth <= MOBILE_BREAK;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function appUrl(path) {
        const base = document.querySelector('meta[name="app-base"]')?.content || '';
        const normalized = String(path || '').startsWith('/') ? path : `/${path}`;
        return `${base}${normalized}`;
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).then((r) => {
            if (!r.ok) {
                throw new Error('Request failed');
            }
            return r.json();
        });
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body || {}),
        }).then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                throw new Error(data.message || 'Action failed');
            }
            return data;
        });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatTimeFromIso(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '';
        return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function buildFilterParams(filterMap) {
        const p = new URLSearchParams();
        Object.entries(filterMap).forEach(([elId, key]) => {
            const el = document.getElementById(elId);
            if (el?.value) {
                p.set(key, el.value);
            }
        });
        return p;
    }

    function renderAppointmentContent(arg, options) {
        const p = arg.event.extendedProps;
        const mobile = isMobile() || arg.view.type === 'dayGridMonth';

        if (mobile && arg.view.type === 'dayGridMonth') {
            const label = options.eventDisplay === 'portal'
                ? (p.physician_name || p.patient_name)
                : p.patient_name;
            return {
                html: `<div class="wpu-cal-event wpu-cal-event--compact">
                    <span class="wpu-cal-event__dot"></span>
                    <span class="wpu-cal-event__time">${escapeHtml(p.time_label)}</span>
                    <span class="wpu-cal-event__name">${escapeHtml(label)}</span>
                </div>`,
            };
        }

        if (options.eventDisplay === 'portal') {
            return {
                html: `<div class="wpu-cal-event">
                    <div class="wpu-cal-event__time">${escapeHtml(p.time_label)}</div>
                    <div class="wpu-cal-event__name">Consultation with</div>
                    <div class="wpu-cal-event__physician">${escapeHtml(p.physician_name)}</div>
                    <div class="wpu-cal-event__status">${escapeHtml(p.status_label)}</div>
                </div>`,
            };
        }

        if (arg.view.type === 'listWeek' || arg.view.type === 'listMonth' || arg.view.type === 'listDay') {
            return true;
        }

        return {
            html: `<div class="wpu-cal-event">
                <div class="wpu-cal-event__time">${escapeHtml(p.time_label)}</div>
                <div class="wpu-cal-event__name">${escapeHtml(p.patient_name)}</div>
                ${p.physician_name ? `<div class="wpu-cal-event__physician">${escapeHtml(p.physician_name)}</div>` : ''}
                <div class="wpu-cal-event__status">${escapeHtml(p.status_label)}</div>
            </div>`,
        };
    }

    function renderSlotContent(arg) {
        if (arg.view.type === 'dayGridMonth') {
            return { domNodes: [] };
        }

        return {
            html: `<div class="wpu-cal-event wpu-cal-event--slot">
                <div class="wpu-cal-event__time">${formatTimeFromIso(arg.event.startStr)}</div>
                <div class="wpu-cal-event__name">Available</div>
            </div>`,
        };
    }

    function eventContent(arg, options) {
        const kind = arg.event.extendedProps.kind;
        if (kind === 'appointment') {
            return renderAppointmentContent(arg, options);
        }
        if (kind === 'slot_available') {
            return renderSlotContent(arg);
        }
        return true;
    }

    function openModal(id) {
        if (window.ModalManager) {
            ModalManager.open(id);
        } else {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('active');
                el.setAttribute('aria-hidden', 'false');
            }
        }
    }

    function closeModal(id) {
        if (window.ModalManager) {
            ModalManager.close(id);
        } else {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('active');
                el.setAttribute('aria-hidden', 'true');
            }
        }
    }

    function toast(msg, type) {
        if (window.AlertSystem?.toast) {
            AlertSystem.toast(msg, type);
        }
    }

    function actionLabel(action) {
        const map = {
            view: 'View',
            confirm: 'Confirm',
            reject: 'Reject',
            cancel: 'Cancel',
            reschedule: 'Reschedule',
            complete: 'Complete',
            no_show: 'Mark No-show',
        };
        return map[action] || action;
    }

    function actionIcon(action) {
        const map = {
            confirm: 'check',
            reject: 'times',
            cancel: 'ban',
            reschedule: 'calendar-alt',
            complete: 'check-double',
            no_show: 'user-slash',
        };
        return map[action] || 'circle';
    }

    function actionClass(action) {
        if (action === 'confirm' || action === 'complete') return 'btn-primary';
        if (action === 'cancel' || action === 'reject' || action === 'no_show') return 'btn-danger';
        return 'btn-secondary';
    }

    function init(options) {
        const el = typeof options.el === 'string' ? document.querySelector(options.el) : options.el;
        if (!el) return null;

        if (typeof FullCalendar === 'undefined') {
            el.innerHTML = '<div class="wpu-cal-error alert alert-error" style="margin:0"><i class="fas fa-circle-exclamation alert-icon"></i><div class="alert-content"><div class="alert-title">Calendar unavailable</div><div class="alert-message">The calendar library failed to load. Hard-refresh the page (Ctrl+F5). If the problem continues, contact your administrator.</div></div></div>';
            console.error('WpuCalendar: FullCalendar is not defined. Check that vendor/fullcalendar/index.global.min.js loaded.');
            return null;
        }

        const filterMap = options.filterMap || {};
        const feedUrl = options.feedUrl || appUrl('/api/calendar/feed');
        const detailUrl = options.detailUrl || appUrl('/api/calendar/date-detail');
        const role = options.role || 'admin';
        const canBook = !!options.canBook;
        const bookUrl = options.bookUrl || appUrl('/api/calendar/appointments');
        const scheduleUrl = options.scheduleUrl || null;

        let calendar = null;

        function getFilters() {
            return buildFilterParams(filterMap);
        }

        function loadEvents(info, ok, fail) {
            const p = getFilters();
            p.set('start', info.startStr.slice(0, 10));
            p.set('end', info.endStr.slice(0, 10));
            fetchJson(`${feedUrl}?${p}`)
                .then((d) => ok(d.data))
                .catch(fail);
        }

        function showDateDetail(dateStr, clickTime) {
            const body = document.getElementById('dateDetailBody');
            const footer = document.getElementById('dateDetailFooter');
            if (!body) return;

            body.innerHTML = '<p class="wpu-cal-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</p>';
            openModal('dateDetailModal');

            const p = getFilters();
            p.set('date', dateStr);

            fetchJson(`${detailUrl}?${p}`)
                .then(({ data }) => {
                    let html = `<h3 class="wpu-cal-date-title">${escapeHtml(data.date_label)}</h3>`;

                    if (data.schedule) {
                        html += '<div class="wpu-cal-detail-section"><h4>Available schedule</h4>';
                        if (data.schedule.available_windows?.length) {
                            html += '<ul class="wpu-cal-detail-list">';
                            data.schedule.available_windows.forEach((w) => {
                                html += `<li>${escapeHtml(w.label)}</li>`;
                            });
                            html += '</ul>';
                        } else {
                            html += '<p class="wpu-cal-muted">No consultation windows for this day.</p>';
                        }
                        html += '</div>';

                        if (data.schedule.breaks?.length) {
                            html += '<div class="wpu-cal-detail-section"><h4>Breaks</h4><ul class="wpu-cal-detail-list">';
                            data.schedule.breaks.forEach((b) => {
                                html += `<li>${escapeHtml(b.label)}${b.reason ? ' — ' + escapeHtml(b.reason) : ''}</li>`;
                            });
                            html += '</ul></div>';
                        }

                        if (data.schedule.blocks?.length) {
                            html += '<div class="wpu-cal-detail-section"><h4>Blocked</h4><ul class="wpu-cal-detail-list wpu-cal-detail-list--blocked">';
                            data.schedule.blocks.forEach((b) => {
                                const label = b.all_day ? escapeHtml(b.label) : escapeHtml(b.label);
                                html += `<li>${label}${b.reason ? ' — ' + escapeHtml(b.reason) : ''}</li>`;
                            });
                            html += '</ul></div>';
                        }
                    }

                    html += '<div class="wpu-cal-detail-section"><h4>Appointments</h4>';
                    if (data.appointments?.length) {
                        html += '<ul class="wpu-cal-detail-list wpu-cal-detail-list--appointments">';
                        data.appointments.forEach((a) => {
                            html += `<li><button type="button" class="wpu-cal-detail-apt" data-apt-id="${a.id}">
                                <strong>${escapeHtml(a.time)}</strong> — ${escapeHtml(a.patient_name)}
                                ${a.physician_name ? `<span class="wpu-cal-muted"> · ${escapeHtml(a.physician_name)}</span>` : ''}
                            </button></li>`;
                        });
                        html += '</ul>';
                    } else {
                        html += '<p class="wpu-cal-muted">No appointments on this date.</p>';
                    }
                    html += '</div>';

                    body.innerHTML = html;

                    body.querySelectorAll('[data-apt-id]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            closeModal('dateDetailModal');
                            showAppointmentDetail(btn.dataset.aptId);
                        });
                    });

                    if (footer) {
                        footer.innerHTML = '';
                        if (canBook && role === 'admin') {
                            const bookBtn = document.createElement('button');
                            bookBtn.type = 'button';
                            bookBtn.className = 'btn btn-primary btn-sm';
                            bookBtn.innerHTML = '<i class="fas fa-plus"></i> Book Consultation';
                            bookBtn.addEventListener('click', () => {
                                closeModal('dateDetailModal');
                                openBookModal(dateStr, clickTime || '09:00:00');
                            });
                            footer.appendChild(bookBtn);
                        }
                        if (role === 'physician' && scheduleUrl) {
                            const schedBtn = document.createElement('a');
                            schedBtn.href = scheduleUrl;
                            schedBtn.className = 'btn btn-secondary btn-sm';
                            schedBtn.innerHTML = '<i class="fas fa-clock"></i> Manage Schedule';
                            footer.appendChild(schedBtn);
                        }
                        const closeBtn = document.createElement('button');
                        closeBtn.type = 'button';
                        closeBtn.className = 'btn btn-secondary btn-sm';
                        closeBtn.textContent = 'Close';
                        closeBtn.addEventListener('click', () => closeModal('dateDetailModal'));
                        footer.appendChild(closeBtn);
                    }
                })
                .catch(() => {
                    body.innerHTML = '<p class="wpu-cal-muted">Unable to load date details.</p>';
                });
        }

        function showAppointmentDetail(id) {
            const body = document.getElementById('appointmentDetailBody');
            const footer = document.getElementById('appointmentDetailActions');
            if (!body) return;

            body.innerHTML = '<p class="wpu-cal-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</p>';
            footer.innerHTML = '';
            openModal('appointmentDetailModal');

            fetchJson(appUrl(`/api/appointments/${id}`))
                .then(({ data, meta }) => {
                    const types = options.consultationTypes || {};
                    const typeLabel = meta?.consultation_type_label || types[data.consultation_type] || data.consultation_type;
                    const status = data.status?.value || data.status;
                    const statusLabel = data.status?.label || data.status_label || status;

                    body.innerHTML = `
                        <dl class="wpu-cal-detail-dl">
                            <dt>Patient</dt><dd>${escapeHtml(data.portal_user?.name || '')}</dd>
                            <dt>Physician</dt><dd>${escapeHtml(data.physician?.professional_title ? data.physician.professional_title + ' ' : '')}${escapeHtml(data.physician?.name || '')}</dd>
                            <dt>Date</dt><dd>${escapeHtml(data.appointment_date ? new Date(data.appointment_date).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' }) : '')}</dd>
                            <dt>Time</dt><dd>${escapeHtml(formatTimeLabel(data.start_time))} – ${escapeHtml(formatTimeLabel(data.end_time))}</dd>
                            <dt>Consultation</dt><dd>${escapeHtml(typeLabel)}</dd>
                            <dt>Reason</dt><dd>${escapeHtml(data.reason || '—')}</dd>
                            <dt>Status</dt><dd><span class="status-badge status-badge--${status}"><i class="fas fa-circle"></i> ${escapeHtml(statusLabel)}</span></dd>
                            <dt>Appointment #</dt><dd>${escapeHtml(data.appointment_number || '')}</dd>
                        </dl>`;

                    const actions = meta?.allowed_actions || [];
                    footer.innerHTML = actions
                        .filter((a) => a !== 'view')
                        .map((action) => `<button type="button" class="btn ${actionClass(action)} btn-sm" data-apt-action="${action}" data-apt-id="${id}">
                            <i class="fas fa-${actionIcon(action)}"></i> ${actionLabel(action)}
                        </button>`)
                        .join('');

                    footer.innerHTML += `<button type="button" class="btn btn-secondary btn-sm" onclick="WpuCalendar.closeModal('appointmentDetailModal')">Close</button>`;

                    footer.querySelectorAll('[data-apt-action]').forEach((btn) => {
                        btn.addEventListener('click', () => runAction(btn.dataset.aptId, btn.dataset.aptAction));
                    });
                })
                .catch(() => {
                    body.innerHTML = '<p class="wpu-cal-muted">Unable to load appointment.</p>';
                });
        }

        function formatTimeLabel(time) {
            if (!time) return '';
            const parts = String(time).split(':');
            const d = new Date();
            d.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0);
            return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        function runAction(id, action) {
            if (action === 'reschedule') {
                toast('Use the appointments page to reschedule.', 'info');
                return;
            }

            const body = action === 'cancel' || action === 'reject'
                ? JSON.stringify({ reason: `${actionLabel(action)} from calendar` })
                : '{}';

            fetch(appUrl(`/api/appointments/${id}/${(action === 'no_show' ? 'no-show' : action)}`), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body,
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data.message || 'Action failed');
                    toast(data.message || 'Updated.', 'success');
                    closeModal('appointmentDetailModal');
                    calendar?.refetchEvents();
                })
                .catch((e) => toast(e.message, 'error'));
        }

        function openBookModal(dateStr, timeStr) {
            const modal = document.getElementById('bookConsultationModal');
            if (!modal) return;

            const dateInput = modal.querySelector('[name="appointment_date"]');
            const timeInput = modal.querySelector('[name="start_time"]');
            const dateLabel = modal.querySelector('[data-book-date-label]');

            if (dateInput) dateInput.value = dateStr;
            if (timeInput) {
                const normalized = normalizeTime(timeStr);
                timeInput.value = normalized.slice(0, 5);
            }
            if (dateLabel) {
                dateLabel.textContent = new Date(dateStr + 'T12:00:00').toLocaleDateString(undefined, {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                });
            }

            const physicianFilter = document.getElementById('filter-physician');
            const physicianSelect = modal.querySelector('[name="physician_id"]');
            if (physicianFilter?.value && physicianSelect) {
                physicianSelect.value = physicianFilter.value;
            }

            openModal('bookConsultationModal');
        }

        function normalizeTime(timeStr) {
            if (!timeStr) return '09:00:00';
            if (/^\d{2}:\d{2}:\d{2}$/.test(timeStr)) return timeStr;
            if (/^\d{2}:\d{2}$/.test(timeStr)) return timeStr + ':00';
            const d = new Date(timeStr);
            if (!Number.isNaN(d.getTime())) {
                return [
                    String(d.getHours()).padStart(2, '0'),
                    String(d.getMinutes()).padStart(2, '0'),
                    '00',
                ].join(':');
            }
            return '09:00:00';
        }

        function bindBookForm() {
            const form = document.getElementById('bookConsultationForm');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const payload = Object.fromEntries(fd.entries());
                if (payload.start_time && /^\d{2}:\d{2}$/.test(payload.start_time)) {
                    payload.start_time = payload.start_time + ':00';
                }

                postJson(bookUrl, payload)
                    .then((data) => {
                        toast(data.message || 'Booked.', 'success');
                        closeModal('bookConsultationModal');
                        form.reset();
                        calendar?.refetchEvents();
                    })
                    .catch((err) => toast(err.message, 'error'));
            });
        }

        const initialView = options.initialView || 'dayGridMonth';

        calendar = new FullCalendar.Calendar(el, {
            initialView,
            height: options.height || 'auto',
            firstDay: 0,
            slotMinTime: '06:00:00',
            slotMaxTime: '20:00:00',
            nowIndicator: true,
            selectable: canBook,
            selectMirror: true,
            dayMaxEvents: isMobile() ? 2 : 4,
            moreLinkClick: 'popover',
            headerToolbar: options.headerToolbar || {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            },
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                day: 'Day',
                list: 'Agenda',
            },
            events: loadEvents,
            eventContent: (arg) => eventContent(arg, options),
            eventClick(info) {
                info.jsEvent.preventDefault();
                const kind = info.event.extendedProps.kind;

                if (kind === 'appointment') {
                    showAppointmentDetail(info.event.id);
                    return;
                }

                if (kind === 'slot_available' && canBook) {
                    const dateStr = info.event.startStr.slice(0, 10);
                    const timeStr = info.event.extendedProps.slot_start || normalizeTime(info.event.startStr);
                    openBookModal(dateStr, timeStr);
                }
            },
            dateClick(info) {
                const timeStr = info.view.type.includes('timeGrid') ? normalizeTime(info.dateStr) : null;
                showDateDetail(info.dateStr.slice(0, 10), timeStr);
            },
            select(info) {
                if (!canBook) return;
                const dateStr = info.startStr.slice(0, 10);
                const timeStr = normalizeTime(info.startStr);
                showDateDetail(dateStr, timeStr);
                calendar.unselect();
            },
            eventDidMount(info) {
                const kind = info.event.extendedProps.kind;
                if (kind === 'slot_available' && info.view.type === 'dayGridMonth') {
                    info.el.style.display = 'none';
                }
            },
        });

        calendar.render();
        bindBookForm();

        Object.keys(filterMap).forEach((id) => {
            const node = document.getElementById(id);
            if (node) {
                node.addEventListener('change', () => calendar.refetchEvents());
            }
        });

        window.addEventListener('resize', () => {
            calendar.setOption('dayMaxEvents', isMobile() ? 2 : 4);
        });

        return calendar;
    }

    window.WpuCalendar = {
        init,
        openModal,
        closeModal,
    };
})(window);
