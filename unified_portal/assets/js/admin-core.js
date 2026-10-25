/**
 * WPU Medical Admin — core application JavaScript
 * Extracted from admin.php for browser caching.
 * Boot config: window.WPU_HIS_CONFIG (autoLock.enabled, autoLock.timeout)
 */

   // ============================================
// FIXED AUTO-LOCK FUNCTIONALITY
// ============================================

// ============================================
// COMPLETE FIXED AUTO-LOCK IMPLEMENTATION
// ============================================

let idleTimer = {
    time: 0,
    enabled: !!(window.WPU_HIS_CONFIG && window.WPU_HIS_CONFIG.autoLock && window.WPU_HIS_CONFIG.autoLock.enabled),
    timeout: (window.WPU_HIS_CONFIG && window.WPU_HIS_CONFIG.autoLock && window.WPU_HIS_CONFIG.autoLock.timeout) || 300000,
    interval: null,
    
    start: function() {
        if (!this.enabled) return;
        
        console.log('Auto-lock started. Timeout: ' + this.timeout + 'ms');
        
        this.interval = setInterval(() => {
            this.time += 1000;
            
            // Debug: log every 30 seconds
            if (this.time % 30000 === 0) {
                console.log('Auto-lock countdown: ' + (this.timeout - this.time) + 'ms remaining');
            }
            
            if (this.time >= this.timeout) {
                this.lock();
            }
        }, 1000);
    },
    
    reset: function() {
        this.time = 0;
    },
    
    lock: function() {
        console.log('Auto-lock triggered');
        clearInterval(this.interval);

        const cfg = window.WPU_HIS_CONFIG || {};
        const lockUrl = cfg.lockUrl || (window.location.href.split('?')[0] + '?action=lock');
        window.location.href = lockUrl;
    },
    
    stop: function() {
        clearInterval(this.interval);
    }
};

// Start the idle timer
idleTimer.start();

// Reset on user activity
const activityEvents = [
    'mousemove', 'mousedown', 'click', 'scroll',
    'keypress', 'keydown', 'touchstart', 'touchmove',
    'resize', 'focus'
];

activityEvents.forEach(event => {
    document.addEventListener(event, () => {
        idleTimer.reset();
    }, { passive: true });
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, consider this as inactivity
        console.log('Page hidden - continuing idle timer');
    } else {
        // Page is visible again
        idleTimer.reset();
    }
});

// Clean up when leaving the page
window.addEventListener('beforeunload', function() {
    idleTimer.stop();
});

// Enhanced Alert System
const AlertSystem = {
    // Show alert
    show: function(message, type = 'info', title = null, duration = 5000) {
        const alertContainer = document.querySelector('.alert-container');
        if (!alertContainer) {
            this.createContainer();
        }
        
        const alertId = 'alert-' + Date.now();
        const icon = this.getIcon(type);
        const alertTitle = title || this.getDefaultTitle(type);
        
        const alertHtml = `
            <div class="alert alert-${type}" id="${alertId}">
                <i class="fas ${icon} alert-icon"></i>
                <div class="alert-content">
                    <div class="alert-title">${alertTitle}</div>
                    <div class="alert-message">${message}</div>
                </div>
                <button class="alert-close" onclick="AlertSystem.close('${alertId}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.querySelector('.alert-container').insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                this.close(alertId);
            }, duration);
        }
        
        return alertId;
    },
    
    // Close alert
    close: function(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }
    },
    
    // Close all alerts
    closeAll: function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        });
    },
    
    // Create alert container if it doesn't exist
    createContainer: function() {
        const container = document.createElement('div');
        container.className = 'alert-container';
        document.body.appendChild(container);
    },
    
    // Get icon for alert type
    getIcon: function(type) {
        const icons = {
            success: 'fa-check-circle',
            // error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    },
    
    // Get default title for alert type
    getDefaultTitle: function(type) {
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };
        return titles[type] || 'Information';
    },
    
    // Show toast notification (simpler, less intrusive)
    toast: function(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${this.getIcon(type)}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    },
    
    // Show progress indicator
    showProgress: function(message, progressId) {
        const progressHtml = `
            <div class="alert alert-info" id="${progressId}">
                <i class="fas fa-spinner fa-spin alert-icon"></i>
                <div class="alert-content">
                    <div class="alert-title">Processing</div>
                    <div class="alert-message">${message}</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        `;
        
        this.createContainer();
        document.querySelector('.alert-container').insertAdjacentHTML('beforeend', progressHtml);
        
        return progressId;
    },
    
    // Update progress
    updateProgress: function(progressId, percent, message = null) {
        const alert = document.getElementById(progressId);
        if (alert) {
            const progressFill = alert.querySelector('.progress-fill');
            if (progressFill) {
                progressFill.style.width = percent + '%';
            }
            
            if (message) {
                const messageEl = alert.querySelector('.alert-message');
                if (messageEl) {
                    messageEl.textContent = message;
                }
            }
            
            if (percent >= 100) {
                setTimeout(() => {
                    this.close(progressId);
                }, 500);
            }
        }
    }
};

// Simple alert replacement (Swal compatible)
const Swal = {
    fire: function(config) {
        return new Promise((resolve) => {
            if (typeof config === 'string') {
                alert(config);
                resolve({ isConfirmed: true });
            } else if (config.title) {
                let message = config.title;
                if (config.text) message += '\n\n' + config.text;
                
                if (config.showCancelButton) {
                    const result = confirm(message);
                    resolve({ isConfirmed: result });
                } else {
                    alert(message);
                    resolve({ isConfirmed: true });
                }
            }
        });
    },
    showLoading: function() {
        console.log('Loading...');
    }
};

// Enhanced Modal Manager
const ModalManager = {
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            // Focus first input
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) firstInput.focus();
        }
    },
    
    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    },
    
    closeAll: function() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
};

// Enhanced Form Manager
const FormManager = {
    showLoading: function(button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<div class="btn-loading">Processing...</div>';
        button.disabled = true;
        return originalText;
    },
    
    hideLoading: function(button, originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
    },
    
    validate: function(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                
                let errorMsg = field.parentNode.querySelector('.field-error');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'field-error';
                    errorMsg.innerHTML = 'This field is required';
                    field.parentNode.appendChild(errorMsg);
                }
                isValid = false;
            } else {
                field.classList.remove('error');
                const errorMsg = field.parentNode.querySelector('.field-error');
                if (errorMsg) errorMsg.remove();
            }
        });
        
        return isValid;
    },
    
    reset: function(form) {
        form.reset();
        form.querySelectorAll('.field-error').forEach(error => error.remove());
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.classList.remove('error');
        });
    }
};

// Enhanced form submission
async function submitForm(form, url, successMessage) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<div class="btn-loading">Processing...</div>';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            AlertSystem.show(successMessage || 'Operation completed successfully', 'success');
            return data;
        } else {
            throw new Error(data.message || 'Operation failed');
        }
    } catch (error) {
        AlertSystem.show(error.message, 'error');
        throw error;
    } finally {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Enhanced form validation
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // Clear previous errors
    form.querySelectorAll('.field-error').forEach(error => error.remove());
    form.querySelectorAll('.form-control.error').forEach(field => {
        field.classList.remove('error');
    });
    
    // Validate required fields
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            
            const errorMsg = document.createElement('div');
            errorMsg.className = 'field-error';
            errorMsg.innerHTML = `This field is required`;
            field.parentNode.appendChild(errorMsg);
            
            isValid = false;
            
            // Scroll to first error
            if (isValid === false) {
                field.focus();
                isValid = null; // Prevent changing back to true
            }
        }
    });
    
    // Custom validations
    const ageFields = form.querySelectorAll('input[name*="age"]');
    ageFields.forEach(field => {
        if (field.value) {
            const age = parseInt(field.value);
            if (age < 0 || age > 150) {
                field.classList.add('error');
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'field-error';
                errorMsg.innerHTML = `Age must be between 0 and 150`;
                field.parentNode.appendChild(errorMsg);
                
                isValid = false;
            }
        }
    });
    
    // Show validation result
    if (isValid) {
        AlertSystem.toast('Form validation passed', 'success', 2000);
    } else if (isValid === false) {
        AlertSystem.show('Please fix the errors in the form', 'error', 'Form Validation Failed');
    }
    
    return isValid;
}

// Replace old showNotification function for compatibility
function showNotification(message, type = 'info') {
    AlertSystem.show(message, type);
}

// ============================================
// SIDEBAR & NAVIGATION - FIXED SECTION PERSISTENCE
// ============================================

// Toggle sidebar for mobile / narrow viewports
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const btn = document.querySelector('.mobile-menu-btn');
    if (!sidebar) return;
    const open = sidebar.classList.toggle('active');
    if (btn) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    }
    document.body.classList.toggle('nav-open', open);
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-menu-btn');
    const backdrop = document.getElementById('sidebar-backdrop');
    
    if (!sidebar || window.innerWidth > 992) return;
    if (!sidebar.classList.contains('active')) return;
    
    const onToggle = mobileToggle && mobileToggle.contains(event.target);
    const onBackdrop = backdrop && backdrop.contains(event.target);
    if (!sidebar.contains(event.target) && !onToggle && !onBackdrop) {
        sidebar.classList.remove('active');
        document.body.classList.remove('nav-open');
        if (mobileToggle) {
            mobileToggle.setAttribute('aria-expanded', 'false');
            mobileToggle.setAttribute('aria-label', 'Open navigation menu');
        }
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    if (window.innerWidth > 992) return;
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-menu-btn');
    if (!sidebar || !sidebar.classList.contains('active')) return;
    sidebar.classList.remove('active');
    document.body.classList.remove('nav-open');
    if (mobileToggle) {
        mobileToggle.setAttribute('aria-expanded', 'false');
        mobileToggle.setAttribute('aria-label', 'Open navigation menu');
    }
});

// Show section - FIXED: Only update URL, don't force redirect
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.data-section').forEach(section => {
        section.classList.remove('active');
    });

    // Remove active class from all menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });

    // Show selected section
    document.getElementById(sectionName).classList.add('active');
    
    // Update breadcrumb
    const sectionNames = {
        'dashboard': 'Dashboard',
        'certificates': 'Medical Certificates',
        'referrals': 'Referrals'
    };
    document.getElementById('currentSection').textContent = sectionNames[sectionName] || sectionName;

    // Activate correct menu item
    const menuItems = document.querySelectorAll('.menu-item');
    if (sectionName === 'dashboard') menuItems[0].classList.add('active');
    else if (sectionName === 'certificates') menuItems[1].classList.add('active');
    else if (sectionName === 'referrals') menuItems[2].classList.add('active');

    // Close sidebar on mobile after selection
    const sb = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.mobile-menu-btn');
    if (sb && window.innerWidth <= 992) {
        sb.classList.remove('active');
        document.body.classList.remove('nav-open');
        if (menuBtn) {
            menuBtn.setAttribute('aria-expanded', 'false');
            menuBtn.setAttribute('aria-label', 'Open navigation menu');
        }
    }
    
    // Save active section to localStorage
    localStorage.setItem('activeSection', sectionName);
    
    // Update URL without page reload - FIXED: Use replaceState to avoid redirects
    const url = new URL(window.location);
    url.searchParams.set('section', sectionName);
    window.history.replaceState({}, '', url);
}

// ============================================
// MODAL FUNCTIONS
// ============================================

// Close modal
function closeModal(modalId) {
    ModalManager.close(modalId);
}

function formatSnapshotVisitDate(iso) {
    if (!iso) return '';
    var d = new Date(String(iso).replace(/-/g, '/') + 'T12:00:00');
    if (isNaN(d.getTime())) return String(iso);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatSnapshotDateTime(value) {
    if (!value) return '';
    var raw = String(value).trim();
    var d = new Date(raw.replace(/-/g, '/'));
    if (isNaN(d.getTime())) {
        d = new Date(raw);
    }
    if (isNaN(d.getTime())) return raw;
    return d.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });
}

function buildHistorySnapshotHtml(rec) {
    var esc = escapeHtml;
    function block(label, val) {
        var v = (val === null || val === undefined || String(val).trim() === '') ? '—' : String(val);
        return '<div class="history-snapshot-field"><strong>' + esc(label) + '</strong><div class="history-snapshot-value">' + esc(v).replace(/\n/g, '<br>') + '</div></div>';
    }
    var html = '<div class="history-snapshot-meta">';
    html += block('Full name', rec.full_name);
    html += block('Patient / student ID', rec.student_id);
    html += block('Visit date', formatSnapshotVisitDate(rec.visit_date));
    html += block('Patient type', rec.type_name);
    html += block('Department', rec.department);
    html += '</div>';
    html += '<h3 class="history-snapshot-section-title">Medical</h3><div class="history-snapshot-meta">';
    html += block('Attending doctor', rec.doctor);
    html += block('Case type', rec.case_name);
    html += block('Diagnosis', rec.diagnosis);
    html += '</div>';
    html += '<h3 class="history-snapshot-section-title">Treatment</h3>';
    html += '<div class="history-snapshot-prose">' + esc(rec.treatment ? String(rec.treatment) : '—').replace(/\n/g, '<br>') + '</div>';
    var soapPairs = [['subjective', 'Subjective'], ['objectives', 'Objective'], ['diagnostics', 'Diagnostics'], ['assessment', 'Assessment'], ['plan', 'Plan']];
    var hasSoap = soapPairs.some(function (pair) {
        return rec[pair[0]] && String(rec[pair[0]]).trim() !== '';
    });
    if (hasSoap) {
        html += '<h3 class="history-snapshot-section-title">Clinical notes (SOAP)</h3>';
        soapPairs.forEach(function (pair) {
            var k = pair[0];
            var label = pair[1];
            if (rec[k] && String(rec[k]).trim() !== '') {
                html += '<div class="history-snapshot-soap"><span class="history-snapshot-soap-label">' + esc(label) + '</span>';
                html += '<div class="history-snapshot-prose">' + esc(String(rec[k])).replace(/\n/g, '<br>') + '</div></div>';
            }
        });
    }
    html += '<h3 class="history-snapshot-section-title">Personal details</h3><div class="history-snapshot-meta">';
    html += block('Gender', rec.gender);
    var ageStr = (rec.age !== '' && rec.age !== null && rec.age !== undefined) ? String(rec.age) + ' years' : '';
    html += block('Age', ageStr);
    html += block('Marital status', rec.marital_status);
    html += block('Religion', rec.religion);
    html += block('Minor', rec.is_minor);
    if (String(rec.is_minor || '') === 'Yes' && rec.guardian_name) {
        html += block('Guardian', rec.guardian_name);
    }
    html += block('Phone', rec.phone_number);
    html += block('Address', rec.address);
    html += '</div>';
    return html;
}

function openHistoryRecordSnapshotModal(recordId, module, revisionId) {
    var body = document.getElementById('historyRecordSnapshotBody');
    var titleEl = document.getElementById('historyRecordSnapshotTitle');
    var openFull = document.getElementById('historyRecordSnapshotOpenFull');
    if (!body || !titleEl || !openFull) return;
    revisionId = revisionId ? parseInt(revisionId, 10) : 0;
    titleEl.textContent = revisionId ? 'Loading version…' : 'Loading visit…';
    body.innerHTML = '<p style="color:var(--gray-600);margin:0;">Loading…</p>';
    openFull.textContent = revisionId ? 'Open current record' : 'Open full record';

    var u = new URL(window.location.href);
    u.search = '';
    u.searchParams.set('page', 'view_record');
    u.searchParams.set('id', String(recordId));
    u.searchParams.set('module', module);
    ['dental_search', 'health_search', 'dental_page', 'health_page', 'search'].forEach(function (k) {
        var v = new URLSearchParams(window.location.search).get(k);
        if (v) u.searchParams.set(k, v);
    });
    openFull.href = u.pathname + '?' + u.searchParams.toString();

    ModalManager.open('historyRecordSnapshotModal');

    var snapUrl = 'components/record_snapshot.php?id=' + encodeURIComponent(recordId) + '&module=' + encodeURIComponent(module);
    if (revisionId) {
        snapUrl += '&revision_id=' + encodeURIComponent(revisionId);
    }
    snapUrl += '&_=' + Date.now();

    fetch(snapUrl, {
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success || !data.record) {
                body.innerHTML = '<p class="alert alert-error" role="alert">' + escapeHtml(data.message || 'Could not load record.') + '</p>';
                titleEl.textContent = 'Visit snapshot';
                return;
            }
            var rec = data.record;
            var vd = formatSnapshotVisitDate(rec.visit_date);
            var namePart = rec.full_name ? String(rec.full_name) : 'Visit snapshot';
            var priorHint = '';
            if (data.is_revision) {
                var when = data.edited_at ? formatSnapshotDateTime(data.edited_at) : '';
                var by = data.edited_by ? String(data.edited_by) : '';
                titleEl.textContent = (when ? when + ' — ' : '') + namePart + ' (saved version)';
                priorHint = '<p class="history-snapshot-prior-hint" role="note">Showing a saved version from before an edit'
                    + (when ? ' on ' + escapeHtml(when) : '')
                    + (by ? ' by ' + escapeHtml(by) : '')
                    + '. The full record page shows the current version.</p>';
            } else {
                titleEl.textContent = (vd ? vd + ' — ' : '') + namePart;
            }
            body.innerHTML = priorHint + buildHistorySnapshotHtml(rec);
        })
        .catch(function () {
            titleEl.textContent = 'Visit snapshot';
            body.innerHTML = '<p class="alert alert-error" role="alert">Failed to load record.</p>';
        });
}

// Open create certificate modal
// Call this function only when the modal is opened
function openCreateCertificateModal() {
    document.getElementById('createCertificateForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('#createCertificateForm input[name="date"]').value = today;
    document.querySelector('#createCertificateForm input[name="date_issued"]').value = today;
    
    // Generate initial receipt number
    generateNewReceipt();
    
    // Reset impression checkbox and textarea - WITH NULL CHECK
    setupImpressionCheckbox();
    
    // Remove ID field if exists (for edit mode)
    const idInput = document.querySelector('#createCertificateForm input[name="id"]');
    if (idInput) idInput.remove();
    
    // Reset modal title and button
    document.querySelector('#createCertificateModal .modal-title').textContent = 'Create Medical Certificate';
    document.querySelector('#createCertificateForm button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Generate Certificate';
    
    ModalManager.open('createCertificateModal');
}



// Open create referral modal
function openCreateReferralModal() {
    document.getElementById('createReferralForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('#createReferralForm input[name="referral-date"]').value = today;
    
    // Remove ID field if exists (for edit mode)
    const idInput = document.querySelector('#createReferralForm input[name="id"]');
    if (idInput) idInput.remove();
    
    // Reset modal title and button
    document.querySelector('#createReferralModal .modal-title').textContent = 'Create Two Way Referral Form';
    document.querySelector('#createReferralForm button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Generate Referral';
    
    ModalManager.open('createReferralModal');
}

// Open certificate code modal
function openCertificateCodeModal() {
    ModalManager.open('certificateCodeModal');
}

// Open staff signature modal
function openStaffSignatureModal() {
    fetch('get_staff_signature.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('staff_name').value = data.staff.name || '';
                document.getElementById('staff_position').value = data.staff.position || '';
                document.getElementById('staff_license').value = data.staff.license_no || '';
            }
            ModalManager.open('staffSignatureModal');
        })
        .catch(error => {
            console.error('Error:', error);
            ModalManager.open('staffSignatureModal');
        });
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        ModalManager.closeAll();
    }
}

// ============================================
// PAGINATION & SEARCH - FIXED SECTION PERSISTENCE
// ============================================

// Change page - FIXED: Preserve current section without forcing dashboard
function changePage(section, page) {
    const url = new URL(window.location);
    
    // Preserve the current section from URL or use the provided section
    const currentSection = url.searchParams.get('page') || section;
    url.searchParams.set('page', currentSection);
    
    if (section === 'certificates') {
        url.searchParams.set('cert_page', page);
    } else if (section === 'referrals') {
        url.searchParams.set('ref_page', page);
    } else if (section === 'user_logs') {
        url.searchParams.set('logs_page', page);
    }
    
    // Save to localStorage
    localStorage.setItem('activeSection', currentSection);
    
    window.location.href = url.toString();
}

// Search records - FIXED: Preserve current section without forcing dashboard
function searchRecords(searchTerm, section) {
    const url = new URL(window.location);
    url.searchParams.set('search', searchTerm);
    
    // Preserve the current section from URL
    const currentSection = url.searchParams.get('page') || section;
    url.searchParams.set('page', currentSection);
    
    // Reset to first page when searching
    if (section === 'certificates') {
        url.searchParams.set('cert_page', 1);
    } else if (section === 'referrals') {
        url.searchParams.set('ref_page', 1);
    }
    
    // Save to localStorage
    localStorage.setItem('activeSection', currentSection);
    
    window.location.href = url.toString();
}

// Tab switching function for combined certificates/referrals page
function switchTab(tab) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'certificates_referrals');
    url.searchParams.set('tab', tab);
    
    // Preserve search terms
    const certSearch = document.getElementById('cert-search-input')?.value || '';
    const refSearch = document.getElementById('ref-search-input')?.value || '';
    
    if (certSearch) {
        url.searchParams.set('cert_search', certSearch);
    }
    if (refSearch) {
        url.searchParams.set('ref_search', refSearch);
    }
    
    // Remove old page parameters if they exist
    url.searchParams.delete('cert_page');
    url.searchParams.delete('ref_page');
    
    window.location.href = url.toString();
}

// Search function for tab-based page
function searchRecordsTab(searchTerm, tab) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'certificates_referrals');
    url.searchParams.set('tab', tab);
    
    // Set tab-specific search parameter
    if (tab === 'certificates') {
        url.searchParams.set('cert_search', searchTerm);
        url.searchParams.delete('ref_search');
        url.searchParams.set('cert_page', 1); // Reset to first page
    } else if (tab === 'referrals') {
        url.searchParams.set('ref_search', searchTerm);
        url.searchParams.delete('cert_search');
        url.searchParams.set('ref_page', 1); // Reset to first page
    }
    
    // Remove old search parameter
    url.searchParams.delete('search');
    
    window.location.href = url.toString();
}

function submitCertSearch() {
    const el = document.getElementById('cert-search-input');
    if (el) {
        searchRecordsTab(el.value, 'certificates');
    }
}

function submitRefSearch() {
    const el = document.getElementById('ref-search-input');
    if (el) {
        searchRecordsTab(el.value, 'referrals');
    }
}

// Pagination function for tab-based page
function changePageTab(tab, page) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'certificates_referrals');
    url.searchParams.set('tab', tab);
    
    // Preserve search terms
    const certSearch = document.getElementById('cert-search-input')?.value || '';
    const refSearch = document.getElementById('ref-search-input')?.value || '';
    
    if (certSearch) {
        url.searchParams.set('cert_search', certSearch);
    }
    if (refSearch) {
        url.searchParams.set('ref_search', refSearch);
    }
    
    // Set tab-specific page parameter
    if (tab === 'certificates') {
        url.searchParams.set('cert_page', page);
    } else if (tab === 'referrals') {
        url.searchParams.set('ref_page', page);
    }
    
    window.location.href = url.toString();
}

// Tab switching function for combined health/dental records page
function switchRecordsTab(tab) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'health_dental_records');
    url.searchParams.set('records_tab', tab);
    
    // Preserve search terms
    const dentalSearch = document.getElementById('dental-search-input')?.value || '';
    const healthSearch = document.getElementById('health-search-input')?.value || '';
    
    if (dentalSearch) {
        url.searchParams.set('dental_search', dentalSearch);
    }
    if (healthSearch) {
        url.searchParams.set('health_search', healthSearch);
    }
    
    // Remove old page parameters if they exist
    url.searchParams.delete('dental_page');
    url.searchParams.delete('health_page');
    
    window.location.href = url.toString();
}

// Search function for records tab-based page
function searchRecordsTabRecords(searchTerm, tab) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'health_dental_records');
    url.searchParams.set('records_tab', tab);
    
    // Set tab-specific search parameter
    if (tab === 'dental') {
        url.searchParams.set('dental_search', searchTerm);
        url.searchParams.delete('health_search');
        url.searchParams.set('dental_page', 1); // Reset to first page
    } else if (tab === 'health') {
        url.searchParams.set('health_search', searchTerm);
        url.searchParams.delete('dental_search');
        url.searchParams.set('health_page', 1); // Reset to first page
    }
    
    // Remove old search parameter
    url.searchParams.delete('search');
    
    window.location.href = url.toString();
}

function submitDentalSearch() {
    const el = document.getElementById('dental-search-input');
    if (el) {
        searchRecordsTabRecords(el.value, 'dental');
    }
}

function submitHealthSearch() {
    const el = document.getElementById('health-search-input');
    if (el) {
        searchRecordsTabRecords(el.value, 'health');
    }
}

// Pagination function for records tab-based page
function changePageTabRecords(tab, page) {
    const url = new URL(window.location);
    url.searchParams.set('page', 'health_dental_records');
    url.searchParams.set('records_tab', tab);
    
    // Preserve search terms
    const dentalSearch = document.getElementById('dental-search-input')?.value || '';
    const healthSearch = document.getElementById('health-search-input')?.value || '';
    
    if (dentalSearch) {
        url.searchParams.set('dental_search', dentalSearch);
    }
    if (healthSearch) {
        url.searchParams.set('health_search', healthSearch);
    }
    
    // Set tab-specific page parameter
    if (tab === 'dental') {
        url.searchParams.set('dental_page', page);
    } else if (tab === 'health') {
        url.searchParams.set('health_page', page);
    }
    
    window.location.href = url.toString();
}

// Perform search (for search button)
function performSearch() {
    const searchInput = document.querySelector('.data-section.active .search-input');
    if (searchInput) {
        const searchTerm = searchInput.value;
        const currentSection = document.querySelector('.data-section.active').id;
        searchRecords(searchTerm, currentSection);
    }
}

// ============================================
// FORM SUBMISSIONS - FIXED VERSION
// ============================================

// ============================================
// FORM SUBMISSIONS - FIXED VERSION WITH NULL CHECKS
// ============================================

// Medical Certificate Form Submission - WITH NULL CHECK
const certificateForm = document.getElementById('createCertificateForm');
if (certificateForm) {
    certificateForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        const fitCheckbox = document.getElementById('cert_fit');
        const impressionCheckbox = document.getElementById('cert_impression');
        const impressionText = document.getElementById('impression_text');
        
        // Validation
        if (!fitCheckbox.checked && !impressionCheckbox.checked) {
            AlertSystem.show('Please select at least one assessment result (Physically fit or Impression)', 'error');
            return false;
        }
        
        if (impressionCheckbox.checked && !impressionText.value.trim()) {
            AlertSystem.show('Please provide impression details when "With the impression of" is selected', 'error');
            impressionText.focus();
            return false;
        }
        
        const originalText = FormManager.showLoading(submitBtn);
        
        try {
            const formData = new FormData(form);
            
            // Add findings as array for proper processing
            const findings = [];
            if (fitCheckbox.checked) findings.push('fit');
            if (impressionCheckbox.checked) findings.push('impression');
            
            // Clear existing findings and add new ones
            formData.delete('findings[]');
            findings.forEach(finding => {
                formData.append('findings[]', finding);
            });
            
            const isUpdate = formData.has('id');
            const url = isUpdate ? './components/update_medical_certificate.php' : './components/process_medical_certificate.php';

            console.log('Submitting to:', url);
            console.log('Form data:', Object.fromEntries(formData));

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 200));
                throw new Error('Server returned non-JSON response. Check for PHP errors.');
            }
            
            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                const action = isUpdate ? 'updated' : 'created';
                AlertSystem.show(data.message || `Medical certificate ${action} successfully!`, 'success');
                
                setTimeout(() => {
                    ModalManager.close('createCertificateModal');
                    // Refresh the page to show updated data
                    window.location.href = `?page=certificates_referrals&tab=certificates&refresh=${Date.now()}`;
                }, 1500);
            } else {
                throw new Error(data.message || `Failed to ${isUpdate ? 'update' : 'create'} medical certificate`);
            }
        } catch (error) {
            console.error('Error:', error);
            AlertSystem.show(error.message, 'error');
        } finally {
            FormManager.hideLoading(submitBtn, originalText);
        }
    });
} else {
    console.log('Medical certificate form not found on this page');
}

// Referral Form Submission - WITH NULL CHECK
const referralForm = document.getElementById('createReferralForm');
if (referralForm) {
    referralForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        // Debug: Log all form data
        console.log('=== REFERRAL FORM DATA ===');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        const originalText = FormManager.showLoading(submitBtn);
        
        try {
            const isUpdate = formData.has('id');
            const url = isUpdate ? 'update_referral.php' : './components/process_referral.php';

            console.log('Submitting to:', url);

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 200));
                throw new Error('Server returned non-JSON response. Check for PHP errors.');
            }
            
            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                const action = isUpdate ? 'updated' : 'created';
                AlertSystem.show(data.message || `Referral form ${action} successfully!`, 'success');
                
                setTimeout(() => {
                    ModalManager.close('createReferralModal');
                    window.location.href = `?page=certificates_referrals&tab=referrals&refresh=${Date.now()}`;
                }, 1500);
            } else {
                throw new Error(data.message || `Failed to ${isUpdate ? 'update' : 'create'} referral form`);
            }
        } catch (error) {
            console.error('Error:', error);
            AlertSystem.show(error.message, 'error');
        } finally {
            FormManager.hideLoading(submitBtn, originalText);
        }
    });
} else {
    console.log('Referral form not found on this page');
}

// Staff Signature Form Submission - ENHANCED
const staffSignatureForm = document.getElementById('staffSignatureForm');
if (staffSignatureForm) {
    staffSignatureForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        const originalText = FormManager.showLoading(submitBtn);
        
        try {
            const response = await fetch('update_staff_signature.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                AlertSystem.show('Physician information updated successfully.', 'success');
                setTimeout(() => {
                    ModalManager.close('staffSignatureModal');
                    window.location.href = '?page=settings&refresh=' + Date.now();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update physician information.');
            }
        } catch (error) {
            console.error('Error:', error);
            AlertSystem.show(error.message, 'error');
        } finally {
            FormManager.hideLoading(submitBtn, originalText);
        }
    });
} else {
    console.log('Staff signature form not found on this page');
}

// Certificate Code Form Submission
const certificateCodeForm = document.getElementById('certificateCodeForm');
if (certificateCodeForm) {
    certificateCodeForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        
        const originalText = FormManager.showLoading(submitBtn);
        
        try {
            const response = await fetch('update_certificate_code.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                AlertSystem.show('Document codes updated successfully.', 'success');
                setTimeout(() => {
                    ModalManager.close('certificateCodeModal');
                    window.location.href = '?page=settings&refresh=' + Date.now();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update document codes.');
            }
        } catch (error) {
            console.error('Error:', error);
            AlertSystem.show(error.message, 'error');
        } finally {
            FormManager.hideLoading(submitBtn, originalText);
        }
    });
} else {
    console.log('Certificate code form not found on this page');
}

// ============================================
// VIEW FUNCTIONS
// ============================================

// View certificate in modal - ENHANCED
function viewCertificate(id) {
    fetch(`get_certificate.php?id=${id}&t=${Date.now()}`) // Add cache busting
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cert = data.certificate;
                showCertificateViewModal(cert);
            } else {
                AlertSystem.show('Failed to load certificate', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AlertSystem.show('Failed to load certificate', 'error');
        });
}

// View referral in modal - ENHANCED
function viewReferral(id) {
    fetch(`get_referral.php?id=${id}&t=${Date.now()}`) // Add cache busting
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ref = data.referral;
                showReferralViewModal(ref);
            } else {
                AlertSystem.show('Failed to load referral', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AlertSystem.show('Failed to load referral', 'error');
        });
}

// Show certificate view modal - FIXED MEDICAL FINDINGS DISPLAY
function showCertificateViewModal(cert) {
    console.log('Certificate data for view:', cert);
    
    // Determine medical findings display - USING CORRECT DATABASE FIELDS
    let medicalFindingsHtml = '';
    
    // Check the actual database fields for medical findings
    const findingsFit = cert.findings_fit || 0;
    const findingsImpression = cert.findings_impression || 0;
    const impressionText = cert.impression_text || '';
    
    console.log('Medical Findings Data:', {
        findings_fit: findingsFit,
        findings_impression: findingsImpression,
        impression_text: impressionText
    });

    // Handle all possible combinations
    if (findingsFit == 1 && findingsImpression == 1) {
        // Both are checked
        medicalFindingsHtml = `
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <div style="width: 16px; height: 16px; border: 2px solid #10b981; background: #10b981; border-radius: 3px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
                    ✓
                </div>
                <span><strong>Physically and mentally fit</strong></span>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <div style="width: 16px; height: 16px; border: 2px solid #10b981; background: #10b981; border-radius: 3px; display: flex; align-items: center; justify-content: center; margin-top: 2px; color: white; font-size: 12px;">
                    ✓
                </div>
                <div style="flex: 1;">
                    <span style="font-weight: 500; margin-bottom: 8px; display: block;"><strong>With the impression of:</strong></span>
                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                        ${impressionText || 'No impression details provided'}
                    </div>
                </div>
            </div>
        `;
    } else if (findingsFit == 1) {
        // Only "Physically fit" is checked
        medicalFindingsHtml = `
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div style="width: 16px; height: 16px; border: 2px solid #10b981; background: #10b981; border-radius: 3px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
                    ✓
                </div>
                <span><strong>Physically and mentally fit</strong></span>
            </div>
        `;
    } else if (findingsImpression == 1) {
        // Only "With impression" is checked
        medicalFindingsHtml = `
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <div style="width: 16px; height: 16px; border: 2px solid #10b981; background: #10b981; border-radius: 3px; display: flex; align-items: center; justify-content: center; margin-top: 2px; color: white; font-size: 12px;">
                    ✓
                </div>
                <div style="flex: 1;">
                    <span style="font-weight: 500; margin-bottom: 8px; display: block;"><strong>With the impression of:</strong></span>
                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                        ${impressionText || 'No impression details provided'}
                    </div>
                </div>
            </div>
        `;
    } else {
        // Neither is checked - show empty state
        medicalFindingsHtml = `
            <div style="color: var(--gray-500); font-style: italic;">
                No medical findings recorded
            </div>
        `;
    }

    const modalHtml = `
        <div id="viewCertificateModal" class="modal">
            <div class="modal-dialog" style="max-width: 900px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">View Medical Certificate</h2>
                        <button class="close" onclick="closeViewModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-section-header">Personal Information</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.name || 'N/A'}
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Age</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.age || 'N/A'}
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.gender || 'N/A'}
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Civil Status</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.civil_status || 'N/A'}
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label class="form-label">Complete Address</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                                    ${cert.address || 'N/A'}
                                </div>
                            </div>
                        </div>

                        <div class="form-section-header">Medical Examination</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Date of Examination</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.examination_date ? new Date(cert.examination_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A'}
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Reason for Examination</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.reason || 'N/A'}
                                </div>
                            </div>
                        </div>

                        <!-- FIXED: Medical Findings Section -->
                        <div class="form-section-header">Medical Findings *</div>
                        <div class="form-group full-width">
                            <label class="form-label">Assessment Results (Select at least one) *</label>
                            <div style="margin-bottom: 16px;">
                                ${medicalFindingsHtml}
                            </div>
                        </div>

                        ${cert.advice ? `
                        <div class="form-section-header">Medical Advice</div>
                        <div class="form-group full-width">
                            <label class="form-label">Medical Advice and Recommendations</label>
                            <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                                ${cert.advice}
                            </div>
                        </div>
                        ` : ''}

                        <div class="form-section-header">Administrative Details</div>
<div class="form-grid">
    <div class="form-group">
        <label class="form-label">Date Issued</label>
        <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
            ${cert.date_issued ? new Date(cert.date_issued).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A'}
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label">Receipt Number</label>
        <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-family: monospace; font-weight: bold;">
            ${cert.receipt_no ? `<span class="badge badge-info" style="font-size: 14px; padding: 6px 14px;">${cert.receipt_no}</span>` : 'N/A'}
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label">MC Number</label>
        <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
            ${cert.mc_no ? `<span class="badge badge-success" style="font-size: 14px; padding: 6px 14px;">${cert.mc_no}</span>` : 'N/A'}
        </div>
    </div>
                            
                            <div class="form-group">
                                <label class="form-label">Created At</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${cert.created_at ? new Date(cert.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}
                                </div>
                            </div>
                            
                            ${cert.updated_at ? `
                            <div class="form-group">
                                <label class="form-label">Last Updated</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${new Date(cert.updated_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'})}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-print" onclick="window.open('print_certificate.php?id=${cert.id}', '_blank')">
                            <i class="fas fa-print"></i>
                            Print Certificate
                        </button>
                        <button type="button" class="btn" style="background: var(--gray-300); color: var(--gray-700);" onclick="closeViewModal()">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing view modal if any
    const existing = document.getElementById('viewCertificateModal');
    if (existing) existing.remove();
    
    // Add to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Use the ModalManager to open it properly
    ModalManager.open('viewCertificateModal');
}   

// Show referral view modal - COMPLETE VERSION WITH ALL FIELDS
function showReferralViewModal(ref) {
    console.log('Referral data for view:', ref);
    
    // Helper function to display patient types
    function displayPatientTypes(ref) {
        const types = [];
        if (ref.patient_type_occupation == 1) types.push('OCCUPATION');
        if (ref.patient_type_faculty == 1) types.push('FACULTY');
        if (ref.patient_type_staff == 1) types.push('STAFF');
        if (ref.patient_type_student == 1) types.push('STUDENT');
        return types.length > 0 ? types.join(', ') : 'N/A';
    }

    const modalHtml = `
        <div id="viewReferralModal" class="modal">
            <div class="modal-dialog" style="width: 1000px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">View Two Way Referral Form</h2>
                        <button class="close" onclick="closeViewModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <!-- Referral Header -->
                        <div class="form-section-header">Referral Header</div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="form-label">To: HOSPITAL/CLINIC OF CHOICE</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.hospital_clinic || 'N/A'}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.referral_date ? new Date(ref.referral_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A'}
                                </div>
                            </div>
                        </div>

                        <!-- Patient Information -->
                        <div class="form-section-header">Patient Information</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Patient Name</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.patient_name || 'N/A'}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Age</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.patient_age || 'N/A'}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sex</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.patient_sex || 'N/A'}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Patient Type</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 60px;">
                                    ${displayPatientTypes(ref)}
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Complete Address</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                                    ${ref.patient_address || 'N/A'}
                                </div>
                            </div>
                        </div>

                        <!-- Medical Information -->
                        ${ref.case_summary || ref.reason_for_referral ? `
                        <div class="form-section-header">Medical Information</div>
                        ${ref.case_summary ? `
                        <div class="form-group full-width">
                            <label class="form-label">CASE SUMMARY</label>
                            <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                                ${ref.case_summary}
                            </div>
                        </div>
                        ` : ''}

                        ${ref.reason_for_referral ? `
                        <div class="form-group full-width">
                            <label class="form-label">REASON FOR REFERRAL/SERVICES REQUESTED</label>
                            <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                                ${ref.reason_for_referral}
                            </div>
                        </div>
                        ` : ''}
                        ` : ''}

                        <!-- Return Information -->
                        ${ref.send_back_agency || ref.return_date || ref.return_patient_name || ref.return_patient_age || ref.return_patient_sex ? `
                        <div class="form-section-header">Return Information</div>
                        <div class="form-grid">
                            ${ref.send_back_agency ? `
                            <div class="form-group">
                                <label class="form-label">Send Back to Referring Agency</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.send_back_agency}
                                </div>
                            </div>
                            ` : ''}
                            
                            ${ref.return_date ? `
                            <div class="form-group">
                                <label class="form-label">Return Date</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${new Date(ref.return_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}
                                </div>
                            </div>
                            ` : ''}

                            ${ref.return_patient_name ? `
                            <div class="form-group">
                                <label class="form-label">Return Patient Name</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.return_patient_name}
                                </div>
                            </div>
                            ` : ''}

                            ${ref.return_patient_age ? `
                            <div class="form-group">
                                <label class="form-label">Return Patient Age</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.return_patient_age}
                                </div>
                            </div>
                            ` : ''}

                            ${ref.return_patient_sex ? `
                            <div class="form-group">
                                <label class="form-label">Return Patient Sex</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.return_patient_sex}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}

                        <!-- Send Back Information (Additional Fields) -->
                        ${ref.send_back_patient_name || ref.send_back_patient_age || ref.send_back_patient_sex ? `
                        <div class="form-section-header">Send Back Information</div>
                        <div class="form-grid">
                            ${ref.send_back_patient_name ? `
                            <div class="form-group">
                                <label class="form-label">Send Back Patient Name</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.send_back_patient_name}
                                </div>
                            </div>
                            ` : ''}

                            ${ref.send_back_patient_age ? `
                            <div class="form-group">
                                <label class="form-label">Send Back Patient Age</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.send_back_patient_age}
                                </div>
                            </div>
                            ` : ''}

                            ${ref.send_back_patient_sex ? `
                            <div class="form-group">
                                <label class="form-label">Send Back Patient Sex</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.send_back_patient_sex}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}

                        <!-- Services and Findings -->
                        ${ref.services_findings ? `
                        <div class="form-section-header">Services and Findings</div>
                        <div class="form-group full-width">
                            <label class="form-label">SERVICES DONE/FINDINGS/RECOMMENDATIONS</label>
                            <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; min-height: 80px; white-space: pre-wrap;">
                                ${ref.services_findings}
                            </div>
                        </div>
                        ` : ''}

                        <!-- Signature & Authorization -->
                        ${ref.signature_name || ref.designation ? `
                        <div class="form-section-header">Signature & Authorization</div>
                        <div class="form-grid">
                            ${ref.signature_name ? `
                            <div class="form-group">
                                <label class="form-label">Name and Signature</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.signature_name}
                                </div>
                            </div>
                            ` : ''}
                            
                            ${ref.designation ? `
                            <div class="form-group">
                                <label class="form-label">Designation</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.designation}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        ` : ''}

                        <!-- Administrative Details -->
                        <div class="form-section-header">Administrative Details</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Created At</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${ref.created_at ? new Date(ref.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}
                                </div>
                            </div>
                            
                            ${ref.updated_at ? `
                            <div class="form-group">
                                <label class="form-label">Last Updated</label>
                                <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px;">
                                    ${new Date(ref.updated_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'})}
                                </div>
                            </div>
                            ` : ''}
                        </div>

                       
                    </div>
                    <div class="modal-footer">
                        <div style="display: flex; gap: 10px; justify-content: space-between; width: 100%; align-items: center;">
                            <div style="display: flex; gap: 10px;">
                                <button type="button" class="btn btn-print" onclick="window.open('print_referral.php?id=${ref.id}', '_blank')">
                                    <i class="fas fa-print"></i>
                                    Print Referral
                                </button>
                                
                            </div>
                            <button type="button" class="btn" style="background: var(--gray-300); color: var(--gray-700);" onclick="closeViewModal()">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing view modal if any
    const existing = document.getElementById('viewReferralModal');
    if (existing) existing.remove();
    
    // Add to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Use the ModalManager to open it properly
    ModalManager.open('viewReferralModal');
}

// ============================================
// EDIT FUNCTIONS - FIXED
// ============================================

// Edit certificate - IMPROVED VERSION
function editCertificate(id) {
    fetch(`./get_certificate.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cert = data.certificate;
                
                // Populate form fields
                document.querySelector('#createCertificateForm input[name="name"]').value = cert.name || '';
                document.querySelector('#createCertificateForm input[name="age"]').value = cert.age || '';
                document.querySelector('#createCertificateForm select[name="gender"]').value = cert.gender || '';
                document.querySelector('#createCertificateForm select[name="civil_status"]').value = cert.civil_status || '';
                document.querySelector('#createCertificateForm textarea[name="address"]').value = cert.address || '';
                document.querySelector('#createCertificateForm input[name="date"]').value = cert.examination_date || '';
                document.querySelector('#createCertificateForm input[name="reason"]').value = cert.reason || '';
                document.querySelector('#createCertificateForm input[name="date_issued"]').value = cert.date_issued || '';
                document.querySelector('#createCertificateForm input[name="receipt_no"]').value = cert.receipt_no || '';
                document.querySelector('#createCertificateForm input[name="mc_no"]').value = cert.mc_no || '';
                
                // Handle medical findings - FIXED
                const fitCheckbox = document.getElementById('cert_fit');
                const impressionCheckbox = document.getElementById('cert_impression');
                const impressionText = document.getElementById('impression_text');
                
                // Reset first
                fitCheckbox.checked = false;
                impressionCheckbox.checked = false;
                impressionText.value = '';
                impressionText.disabled = true;
                
                // Set based on actual database fields
                if (cert.findings_fit == 1) {
                    fitCheckbox.checked = true;
                }
                
                if (cert.findings_impression == 1) {
                    impressionCheckbox.checked = true;
                    impressionText.disabled = false;
                    impressionText.value = cert.impression_text || '';
                }
                
                // Handle medical advice
                document.querySelector('#createCertificateForm textarea[name="advice"]').value = cert.advice || '';
                
                // Make receipt number editable in edit mode
                const receiptInput = document.querySelector('#createCertificateForm input[name="receipt_no"]');
                receiptInput.readOnly = false;
                receiptInput.style.background = 'white';
                
                // Add regenerate button if not exists
                let regenerateBtn = document.querySelector('#createCertificateForm button[onclick="generateNewReceipt()"]');
                if (!regenerateBtn) {
                    const receiptGroup = receiptInput.parentElement;
                    regenerateBtn = document.createElement('button');
                    regenerateBtn.type = 'button';
                    regenerateBtn.className = 'btn btn-sm';
                    regenerateBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Regenerate';
                    regenerateBtn.style.cssText = 'background: var(--gray-200); color: var(--gray-700); white-space: nowrap;';
                    regenerateBtn.onclick = generateNewReceipt;
                    receiptGroup.appendChild(regenerateBtn);
                }
                
                // Add hidden ID field for update
                let idInput = document.querySelector('#createCertificateForm input[name="id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    document.getElementById('createCertificateForm').appendChild(idInput);
                }
                idInput.value = cert.id;
                
                // Change modal title and button
                document.querySelector('#createCertificateModal .modal-title').textContent = 'Edit Medical Certificate';
                document.querySelector('#createCertificateForm button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Update Certificate';
                
                // Open modal
                ModalManager.open('createCertificateModal');
            } else {
                AlertSystem.show('Failed to load certificate data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AlertSystem.show('Failed to load certificate data', 'error');
        });
}

// Edit referral - IMPROVED VERSION
function editReferral(id) {
    fetch(`./get_referral.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ref = data.referral;
                console.log('Editing referral:', ref);
                
                // Populate form fields
                document.querySelector('#createReferralForm input[name="referral-hospital"]').value = ref.hospital_clinic || '';
                document.querySelector('#createReferralForm input[name="referral-date"]').value = ref.referral_date || '';
                document.querySelector('#createReferralForm input[name="referral-name"]').value = ref.patient_name || '';
                document.querySelector('#createReferralForm input[name="referral-age"]').value = ref.patient_age || '';
                
                // Set gender radio buttons
                if (ref.patient_sex) {
                    const genderInput = document.querySelector(`#createReferralForm input[name="referral-sex"][value="${ref.patient_sex.toUpperCase()}"]`);
                    if (genderInput) {
                        genderInput.checked = true;
                    }
                }
                
                // Set patient type checkboxes
                if (ref.patient_type) {
                    const types = Array.isArray(ref.patient_type) ? ref.patient_type : ref.patient_type.split(',').map(t => t.trim());
                    document.querySelectorAll('#createReferralForm input[name="referral-type[]"]').forEach(checkbox => {
                        checkbox.checked = types.includes(checkbox.value);
                    });
                }
                
                document.querySelector('#createReferralForm textarea[name="referral-address"]').value = ref.patient_address || '';
                document.querySelector('#createReferralForm textarea[name="referral-case"]').value = ref.case_summary || '';
                document.querySelector('#createReferralForm textarea[name="referral-reason"]').value = ref.referral_reason || '';
                document.querySelector('#createReferralForm input[name="referral-send-back"]').value = ref.send_back_agency || '';
                document.querySelector('#createReferralForm input[name="referral-send-back-date"]').value = ref.send_back_date || '';
                document.querySelector('#createReferralForm input[name="referral-send-back-name"]').value = ref.send_back_name || '';
                document.querySelector('#createReferralForm input[name="referral-send-back-age"]').value = ref.send_back_age || '';
                document.querySelector('#createReferralForm select[name="referral-send-back-sex"]').value = ref.send_back_sex || '';
                document.querySelector('#createReferralForm textarea[name="referral-services"]').value = ref.services_done || '';
                document.querySelector('#createReferralForm input[name="referral-signature"]').value = ref.signature_name || '';
                document.querySelector('#createReferralForm input[name="referral-designation"]').value = ref.designation || '';
                
                // Add hidden ID field for update
                let idInput = document.querySelector('#createReferralForm input[name="id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.id = 'referral-id';
                    document.getElementById('createReferralForm').appendChild(idInput);
                }
                idInput.value = ref.id;
                
                // Change modal title and button
                document.querySelector('#createReferralModal .modal-title').textContent = 'Edit Referral Form';
                document.querySelector('#createReferralForm button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> Update Referral';
                
                // Open modal
                ModalManager.open('createReferralModal');
            } else {
                AlertSystem.show('Failed to load referral data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AlertSystem.show('Failed to load referral data', 'error');
        });
}   

// ============================================
// DELETE FUNCTIONS - UPDATED WITH SECTION PERSISTENCE
// ============================================

// Delete certificate - UPDATED WITH NEW CONFIRMATION
async function deleteCertificate(id, name) {
    const confirmed = await confirmAction(
        'Delete Medical Certificate?',
        `Are you sure you want to permanently delete the medical certificate for <strong>${name}</strong>?<br><br>This action cannot be undone and all associated data will be lost.`,
        'Delete',
        'Cancel',
        'danger'
    );
    
    if (confirmed) {
        const progressId = AlertSystem.showProgress('Deleting certificate...', 'delete-progress');
        
        try {
            const response = await fetch(`delete_certificate.php?id=${id}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            
            AlertSystem.updateProgress(progressId, 100, 'Deletion complete');
            
            if (data.success) {
                AlertSystem.show('Certificate has been deleted successfully', 'success');
                setTimeout(() => {
                    window.location.href = `?page=certificates_referrals&tab=certificates&refresh=${Date.now()}`;
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to delete certificate');
            }
        } catch (error) {
            AlertSystem.close(progressId);
            AlertSystem.show(error.message, 'error');
        }
    }
}

// Delete referral - UPDATED WITH NEW CONFIRMATION
async function deleteDentalRecord(id, name) {
    try {
        const confirmed = await confirmAction(
            'Delete Dental Record',
            `Are you sure you want to delete the dental record for <strong>${name}</strong>?<br><br>This action cannot be undone.`,
            'Delete',
            'Cancel',
            'danger'
        );
        
        if (confirmed) {
            const formData = new FormData();
            formData.append('action', 'delete_dental_record');
            formData.append('record_id', id);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                AlertSystem.show('Dental record deleted successfully', 'success');
                setTimeout(() => {
                    window.location.href = '?page=health_dental_records&records_tab=dental';
                }, 1500);
            } else {
                throw new Error('Failed to delete dental record');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        AlertSystem.show('An error occurred while deleting the dental record.', 'error');
    }
}

async function deleteHealthRecord(id, name) {
    try {
        const confirmed = await confirmAction(
            'Delete Health Record',
            `Are you sure you want to delete the health record for <strong>${name}</strong>?<br><br>This action cannot be undone.`,
            'Delete',
            'Cancel',
            'danger'
        );
        
        if (confirmed) {
            const formData = new FormData();
            formData.append('action', 'delete_health_record');
            formData.append('record_id', id);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                AlertSystem.show('Health record deleted successfully', 'success');
                setTimeout(() => {
                    window.location.href = '?page=health_dental_records&records_tab=health';
                }, 1500);
            } else {
                throw new Error('Failed to delete health record');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        AlertSystem.show('An error occurred while deleting the health record.', 'error');
    }
}

async function deleteReferral(id, name) {
    const confirmed = await confirmAction(
        'Delete Referral Form?',
        `Are you sure you want to permanently delete the referral form for <strong>${name}</strong>?<br><br>This action cannot be undone and all associated data will be lost.`,
        'Delete',
        'Cancel',
        'danger'
    );
    
    if (confirmed) {
        const progressId = AlertSystem.showProgress('Deleting referral...', 'delete-progress');
        
        try {
            const response = await fetch(`delete_referral.php?id=${id}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            
            AlertSystem.updateProgress(progressId, 100, 'Deletion complete');
            
            if (data.success) {
                AlertSystem.show('Referral has been deleted successfully', 'success');
                setTimeout(() => {
                    window.location.href = `?page=certificates_referrals&tab=referrals&refresh=${Date.now()}`;
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to delete referral');
            }
        } catch (error) {
            AlertSystem.close(progressId);
            AlertSystem.show(error.message, 'error');
        }
    }
}

// Delete admin - USING NEW CONFIRMATION
async function deleteAdmin(id, username) {
    const confirmed = await confirmAction(
        'Delete Admin User?',
        `Are you sure you want to permanently delete the admin account <strong>${username}</strong>?<br><br>This action cannot be undone and the user will lose all access to the system.`,
        'Delete',
        'Cancel',
        'danger'
    );
    
    if (confirmed) {
        const progressId = AlertSystem.showProgress('Deleting admin...', 'delete-progress');
        
        try {
            // Create form data for submission
            const formData = new FormData();
            formData.append('action', 'delete_admin');
            formData.append('admin_id', id);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            // Check if response is HTML (page reload) or JSON
            const contentType = response.headers.get('content-type');
            let data;
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                // If it's HTML, assume success and reload
                data = { success: true, message: 'Admin deleted successfully' };
            }
            
            AlertSystem.updateProgress(progressId, 100, 'Deletion complete');
            
            if (data.success) {
                AlertSystem.show(data.message || 'Admin has been deleted successfully.', 'success');
                setTimeout(() => {
                    window.location.href = `?page=admin_management&refresh=${Date.now()}`;
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to delete admin.');
            }
        } catch (error) {
            AlertSystem.close(progressId);
            AlertSystem.show(error.message, 'error');
        }
    }
}

// Logout confirmation - USING NEW CONFIRMATION
// Logout confirmation - FIXED VERSION
async function confirmLogout(event) {
    if (event) {
        event.preventDefault();
    }
    
    try {
        const confirmed = await confirmAction(
            'Confirm Logout',
            'Are you sure you want to logout from the admin panel?<br><br>You will need to log in again to access the system.',
            'Logout',
            'Cancel',
            'warning'
        );
        
        if (confirmed) {
            window.location.href = '?logout';
        }
    } catch (error) {
        console.error('Error in logout confirmation:', error);
        // Fallback: direct logout if confirmation fails
        window.location.href = '?logout';
    }
}
// Enhanced confirmation dialog
async function confirmAction(title, message, confirmText = 'Confirm', cancelText = 'Cancel', type = 'warning') {
    return new Promise((resolve) => {
        const modalId = 'confirmation-modal-' + Date.now();
        const icon = type === 'warning' ? 'fa-exclamation-triangle' : 
                    type === 'danger' ? 'fa-exclamation-circle' : 'fa-question-circle';
        
        const modalHtml = `
            <div id="${modalId}" class="modal">
                <div class="modal-dialog" style="max-width: 500px;">
                    <div class="modal-content">
                        <div class="modal-header" style="background: ${type === 'danger' ? '#dc2626' : type === 'warning' ? '#f59e0b' : '#3b82f6'};">
                            <h2 class="modal-title" style="color: white;">
                                <i class="fas ${icon}"></i>
                                ${title}
                            </h2>
                            <button class="close" data-action="cancel" style="color: white;">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div style="text-align: center; padding: 20px 0;">
                                <div style="font-size: 48px; color: ${type === 'danger' ? '#dc2626' : type === 'warning' ? '#f59e0b' : '#3b82f6'}; margin-bottom: 16px;">
                                    ${type === 'danger' ? '⚠️' : type === 'warning' ? '⚠️' : '❓'}
                                </div>
                                <h3 style="color: #374151; margin-bottom: 12px;">${title}</h3>
                                <p style="color: #6b7280; line-height: 1.5;">${message}</p>
                            </div>
                        </div>
                        <div class="modal-footer" style="justify-content: center; gap: 12px;">
                            <button type="button" class="btn" data-action="cancel" style="background: #6b7280; color: white; min-width: 100px;">
                                ${cancelText}
                            </button>
                            <button type="button" class="btn" data-action="confirm" style="background: ${type === 'danger' ? '#dc2626' : type === 'warning' ? '#f59e0b' : '#3b82f6'}; color: white; min-width: 100px;">
                                <i class="fas fa-check"></i>
                                ${confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = document.getElementById(modalId);
        
        // Add event listeners to buttons
        modal.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                modal.remove();
                document.body.style.overflow = 'auto';
                resolve(action === 'confirm');
            });
        });
        
        // Click outside to cancel
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
                document.body.style.overflow = 'auto';
                resolve(false);
            }
        });
        
        ModalManager.open(modalId);
    });
}


// ============================================
// FORM ENHANCEMENTS
// ============================================

// Safe impression checkbox setup
function setupImpressionCheckbox() {
    const impressionCheckbox = document.getElementById('cert_impression');
    const impressionText = document.getElementById('impression_text');
    
    // Only proceed if both elements exist
    if (impressionCheckbox && impressionText) {
        // Set initial state
        impressionText.disabled = !impressionCheckbox.checked;
        
        // Add event listener
        impressionCheckbox.addEventListener('change', function() {
            impressionText.disabled = !this.checked;
            if (!this.checked) {
                impressionText.value = '';
            } else {
                impressionText.focus();
            }
        });
        
        console.log('Impression checkbox setup completed');
    } else {
        console.log('Impression checkbox elements not found on this page');
    }
}


// Auto-resize textareas
document.querySelectorAll('textarea').forEach(textarea => {
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});

// ============================================
// KEYBOARD SHORTCUTS
// ============================================

document.addEventListener('keydown', function(e) {
    // Ctrl + N for new certificate
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        openCreateCertificateModal();
    }
    
    // Ctrl + Shift + N for new referral
    if (e.ctrlKey && e.shiftKey && e.key === 'N') {
        e.preventDefault();
        openCreateReferralModal();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        ModalManager.closeAll();
        closeViewModal();
    }
    
    // Enter key in search box
    if (e.key === 'Enter' && e.target.classList.contains('search-input')) {
        e.preventDefault();
        performSearch();
    }
});

// ============================================
// INITIALIZATION - FIXED VERSION
// ============================================

// Safe form validation initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set today's date as default for date inputs - WITH NULL CHECK
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (input && !input.value && !input.hasAttribute('readonly')) {
            input.value = today;
        }
    });
    
    // Initialize search on Enter key for all search inputs - WITH NULL CHECK
    document.querySelectorAll('.search-input').forEach(input => {
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
        }
    });
    
    // Add form validation on submit - ONLY FOR EXISTING FORMS
    document.querySelectorAll('form').forEach(form => {
        if (form && form.isConnected) {
            form.addEventListener('submit', function(e) {
                if (!this.classList.contains('no-validate')) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
    
    // Real-time form validation - ONLY FOR EXISTING FIELDS
    document.querySelectorAll('input, select, textarea').forEach(field => {
        if (field && field.isConnected) {
            field.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }
    });
    // Auto-remove PHP alerts after 5 seconds
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(() => {
            AlertSystem.close('success-alert');
        }, 5000);
    }
    
    // Auto-remove error alerts after 8 seconds
    const errorAlert = document.getElementById('error-alert');
    if (errorAlert) {
        setTimeout(() => {
            AlertSystem.close('error-alert');
        }, 8000);
    }

    // Check if we need to refresh table data
    if (urlParams.has('refresh')) {
        // Remove the refresh parameter
        const newUrl = window.location.pathname + '?page=' + (urlParams.get('page') || 'certificates');
        window.history.replaceState({}, '', newUrl);
        
        // Show refresh notification
        setTimeout(() => {
            AlertSystem.toast('Data refreshed successfully', 'success');
        }, 500);
    }
    
    // Refresh table data every 30 seconds if on certificates or referrals page
    const currentPage = urlParams.get('page');
    if (currentPage === 'certificates' || currentPage === 'referrals' || currentPage === 'certificates_referrals') {
        setInterval(() => {
            refreshTableData(currentPage);
        }, 30000);
    }
});




// ============================================
// ADDITIONAL HELPER FUNCTIONS
// ============================================

// Generate new receipt number
function generateNewReceipt() {
    const currentYear = new Date().getFullYear();
    const randomNum = Math.floor(Math.random() * 9000) + 1000; // 1000-9999
    const newReceipt = `WPU-MC-${currentYear}-${randomNum}`;
    
    document.getElementById('receipt_no').value = newReceipt;
    AlertSystem.toast('New receipt number generated: ' + newReceipt, 'success');
}

// Refresh table data without page reload
function refreshTableData(page) {
    console.log('Auto-refreshing table data for:', page);
    
    // You can implement AJAX table refresh here if needed
    // For now, we'll just update the timestamp to prevent caching
    const links = document.querySelectorAll('a[href*="page=' + page + '"]');
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && !href.includes('refresh')) {
            link.setAttribute('href', href + '&refresh=' + Date.now());
        }
    });
}

// Universal close function for all modals
function closeViewModal() {
    console.log('Closing view modal...');
    
    // Method 1: Remove by specific IDs
    const modalIds = ['viewCertificateModal', 'viewReferralModal'];
    modalIds.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            console.log('Removing modal:', modalId);
            modal.remove();
        }
    });
    
    // Method 2: Remove any modal with view in the ID
    document.querySelectorAll('.modal').forEach(modal => {
        if (modal.id && modal.id.includes('view')) {
            console.log('Removing view modal:', modal.id);
            modal.remove();
        }
    });
    
    // Method 3: Use ModalManager for any remaining modals
    ModalManager.closeAll();
    
    // Always reset body overflow
    document.body.style.overflow = 'auto';
    
    console.log('View modal close completed');
}

// Enhanced event listener for close buttons
document.addEventListener('click', function(e) {
    // Close buttons
    if (e.target.classList.contains('close') || e.target.closest('.close')) {
        const closeBtn = e.target.classList.contains('close') ? e.target : e.target.closest('.close');
        const modal = closeBtn.closest('.modal');
        if (modal) {
            console.log('Close button clicked for modal:', modal.id);
            modal.remove();
            document.body.style.overflow = 'auto';
        }
    }
    
    // Click outside to close
    if (e.target.classList.contains('modal')) {
        console.log('Clicked outside modal, closing:', e.target.id);
        e.target.remove();
        document.body.style.overflow = 'auto';
    }
});

// Escape key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        console.log('Escape key pressed, closing modals');
        closeViewModal();
    }
});

// Make AlertSystem globally available
window.AlertSystem = AlertSystem;
window.validateForm = validateForm;
window.confirmAction = confirmAction;
window.submitForm = submitForm;
window.ModalManager = ModalManager;

// ============================================
// DENTAL RECORDS FUNCTIONS
// ============================================

function filterDentalRecords() {
    const input = document.getElementById('dentalSearchInput');
    if (!input) return;
    const filter = input.value.toLowerCase();
    const table = document.getElementById('dentalRecordsTable');
    if (!table) return;
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const text = rows[i].textContent.toLowerCase();
        rows[i].style.display = text.includes(filter) ? '' : 'none';
    }
}

function openDentalAddRecordModal() {
    const modal = document.getElementById('dentalAddRecordModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeDentalAddRecordModal() {
    const modal = document.getElementById('dentalAddRecordModal');
    const form = document.getElementById('dentalAddRecordForm');
    if (modal) modal.style.display = 'none';
    if (form) form.reset();
    document.getElementById('dentalGuardianGroup').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openDentalEditModal(recordId) {
    const modal = document.getElementById('dentalEditRecordModal');
    if (!modal) return;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    fetch('components/edit_record.php?id=' + recordId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('dental_edit_record_id').value = data.id;
        document.getElementById('dental_edit_patient_type').value = data.patient_type_id;
        document.getElementById('dental_edit_student_id').value = data.student_id;
        document.getElementById('dental_edit_full_name').value = data.full_name;
        document.getElementById('dental_edit_gender').value = data.gender;
        document.getElementById('dental_edit_age').value = data.age;
        document.getElementById('dental_edit_marital_status').value = data.marital_status;
        document.getElementById('dental_edit_religion').value = data.religion || '';
        document.getElementById('dental_edit_is_minor').value = data.is_minor;
        document.getElementById('dental_edit_guardian_name').value = data.guardian_name || '';
        document.getElementById('dental_edit_phone_number').value = data.phone_number || '';
        document.getElementById('dental_edit_address').value = data.address || '';
        document.getElementById('dental_edit_department').value = data.department_id;
        document.getElementById('dental_edit_visit_date').value = data.visit_date;
        document.getElementById('dental_edit_case_type').value = data.case_type_id;
        document.getElementById('dental_edit_doctor').value = data.doctor;
        document.getElementById('dental_edit_diagnosis').value = data.diagnosis;
        document.getElementById('dental_edit_treatment').value = data.treatment;
        document.getElementById('dental_edit_subjective').value = data.subjective || '';
        document.getElementById('dental_edit_objectives').value = data.objectives || '';
        document.getElementById('dental_edit_diagnostics').value = data.diagnostics || '';
        document.getElementById('dental_edit_assessment').value = data.assessment || '';
        document.getElementById('dental_edit_plan').value = data.plan || '';
        var dtx = document.getElementById('dental_edit_transaction_type');
        if (dtx) dtx.value = data.transaction_type || 'Dental';
        var dcid = document.getElementById('dental_edit_consent_record_id');
        if (dcid) dcid.value = data.consent_record_id || '';
        toggleDentalEditGuardian();
    })
    .catch(error => {
        console.error('Error:', error);
        AlertSystem.show('Failed to load record data. Please try again.', 'error');
        closeDentalEditModal();
    });
}

function closeDentalEditModal() {
    const modal = document.getElementById('dentalEditRecordModal');
    const form = document.getElementById('dentalEditRecordForm');
    if (modal) modal.style.display = 'none';
    if (form) form.reset();
    document.getElementById('dentalEditGuardianGroup').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleDentalGuardian() {
    const isMinor = document.getElementById('dental_is_minor');
    const guardianGroup = document.getElementById('dentalGuardianGroup');
    if (isMinor && guardianGroup) {
        guardianGroup.style.display = isMinor.value === 'Yes' ? 'block' : 'none';
    }
}

function toggleDentalEditGuardian() {
    const isMinor = document.getElementById('dental_edit_is_minor');
    const guardianGroup = document.getElementById('dentalEditGuardianGroup');
    if (isMinor && guardianGroup) {
        guardianGroup.style.display = isMinor.value === 'Yes' ? 'block' : 'none';
    }
}

function viewDentalRecordDetails(recordId) {
    var u = new URL(window.location.href);
    u.searchParams.set('page', 'view_record');
    u.searchParams.set('id', String(recordId));
    u.searchParams.set('module', 'dental');
    var inp = document.getElementById('dental-search-input');
    if (inp && inp.value.trim() !== '') {
        u.searchParams.set('dental_search', inp.value.trim());
    }
    window.location.href = u.pathname + '?' + u.searchParams.toString();
}

function wpuHandleClinicalRecordSaveResponse(data) {
    if (!data.success && data.consent_required) {
        var msg = data.message || 'Medical consent is required.';
        AlertSystem.show(msg, 'error');
        if (data.consent_start_url && confirm('Open the Medical Consent Form in a new tab?')) {
            window.open(data.consent_start_url, '_blank');
        }
        return;
    }
    AlertSystem.show(data.message || (data.success ? 'Saved.' : 'Error.'), data.success ? 'success' : 'error');
}

function wpuSuggestConsentIdForStudent(studentId, targetInputId) {
    if (!studentId || !targetInputId) return;
    var input = document.getElementById(targetInputId);
    if (!input || input.value) return;
    fetch('components/consent_lookup.php?student_id=' + encodeURIComponent(studentId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && data.consents && data.consents.length === 1) {
                input.value = data.consents[0].id;
            }
        })
        .catch(function () { /* ignore */ });
}

function wpuBindConsentStudentLookup(studentFieldId, consentFieldId) {
    var sid = document.getElementById(studentFieldId);
    if (!sid) return;
    sid.addEventListener('blur', function () {
        wpuSuggestConsentIdForStudent(sid.value.trim(), consentFieldId);
    });
}

document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || t.name !== 'consent_emergency_bypass') return;
    var form = t.closest('form');
    if (!form) return;
    var consentInput = form.querySelector('.consent-record-id-input');
    if (!consentInput) return;
    if (t.checked) {
        consentInput.removeAttribute('required');
    } else {
        consentInput.setAttribute('required', 'required');
    }
});

// Dental form submission
document.addEventListener('DOMContentLoaded', function() {
    wpuBindConsentStudentLookup('dental_student_id', 'dental_consent_record_id');
    wpuBindConsentStudentLookup('dental_edit_student_id', 'dental_edit_consent_record_id');
    wpuBindConsentStudentLookup('health_student_id', 'health_consent_record_id');
    wpuBindConsentStudentLookup('health_edit_student_id', 'health_edit_consent_record_id');

    const dentalAddForm = document.getElementById('dentalAddRecordForm');
    if (dentalAddForm) {
        dentalAddForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_record');
            
            fetch('components/process_dental_record.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wpuHandleClinicalRecordSaveResponse(data);
                    setTimeout(() => {
                        window.location.href = '?page=health_dental_records&records_tab=dental';
                    }, 1500);
                } else {
                    wpuHandleClinicalRecordSaveResponse(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                AlertSystem.show('An error occurred while saving the record.', 'error');
            });
        });
    }
    
    const dentalEditForm = document.getElementById('dentalEditRecordForm');
    if (dentalEditForm) {
        dentalEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_record');
            
            fetch('components/edit_record.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wpuHandleClinicalRecordSaveResponse(data);
                    setTimeout(() => {
                        window.location.href = '?page=health_dental_records&records_tab=dental';
                    }, 1500);
                } else {
                    wpuHandleClinicalRecordSaveResponse(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                AlertSystem.show('An error occurred while updating the record.', 'error');
            });
        });
    }
});

// ============================================
// HEALTH RECORDS FUNCTIONS
// ============================================

function filterHealthRecords() {
    const input = document.getElementById('healthSearchInput');
    if (!input) return;
    const filter = input.value.toLowerCase();
    const table = document.getElementById('healthRecordsTable');
    if (!table) return;
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const text = rows[i].textContent.toLowerCase();
        rows[i].style.display = text.includes(filter) ? '' : 'none';
    }
}

function openHealthAddRecordModal() {
    const modal = document.getElementById('healthAddRecordModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeHealthAddRecordModal() {
    const modal = document.getElementById('healthAddRecordModal');
    const form = document.getElementById('healthAddRecordForm');
    if (modal) modal.style.display = 'none';
    if (form) form.reset();
    document.getElementById('healthGuardianGroup').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openHealthEditModal(recordId) {
    const modal = document.getElementById('healthEditRecordModal');
    if (!modal) return;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    fetch('components/edit_record.php?id=' + recordId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('health_edit_record_id').value = data.id;
        document.getElementById('health_edit_patient_type').value = data.patient_type_id;
        document.getElementById('health_edit_student_id').value = data.student_id;
        document.getElementById('health_edit_full_name').value = data.full_name;
        document.getElementById('health_edit_gender').value = data.gender;
        document.getElementById('health_edit_age').value = data.age;
        document.getElementById('health_edit_marital_status').value = data.marital_status;
        document.getElementById('health_edit_religion').value = data.religion || '';
        document.getElementById('health_edit_is_minor').value = data.is_minor;
        document.getElementById('health_edit_guardian_name').value = data.guardian_name || '';
        document.getElementById('health_edit_phone_number').value = data.phone_number || '';
        document.getElementById('health_edit_address').value = data.address || '';
        document.getElementById('health_edit_department').value = data.department_id;
        document.getElementById('health_edit_visit_date').value = data.visit_date;
        document.getElementById('health_edit_case_type').value = data.case_type_id;
        document.getElementById('health_edit_doctor').value = data.doctor;
        document.getElementById('health_edit_diagnosis').value = data.diagnosis;
        document.getElementById('health_edit_treatment').value = data.treatment;
        document.getElementById('health_edit_subjective').value = data.subjective || '';
        document.getElementById('health_edit_objectives').value = data.objectives || '';
        document.getElementById('health_edit_diagnostics').value = data.diagnostics || '';
        document.getElementById('health_edit_assessment').value = data.assessment || '';
        document.getElementById('health_edit_plan').value = data.plan || '';
        var htx = document.getElementById('health_edit_transaction_type');
        if (htx) htx.value = data.transaction_type || '';
        var hcid = document.getElementById('health_edit_consent_record_id');
        if (hcid) hcid.value = data.consent_record_id || '';
        toggleHealthEditGuardian();
    })
    .catch(error => {
        console.error('Error:', error);
        AlertSystem.show('Failed to load record data. Please try again.', 'error');
        closeHealthEditModal();
    });
}

function closeHealthEditModal() {
    const modal = document.getElementById('healthEditRecordModal');
    const form = document.getElementById('healthEditRecordForm');
    if (modal) modal.style.display = 'none';
    if (form) form.reset();
    document.getElementById('healthEditGuardianGroup').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleHealthGuardian() {
    const isMinor = document.getElementById('health_is_minor');
    const guardianGroup = document.getElementById('healthGuardianGroup');
    if (isMinor && guardianGroup) {
        guardianGroup.style.display = isMinor.value === 'Yes' ? 'block' : 'none';
    }
}

function toggleHealthEditGuardian() {
    const isMinor = document.getElementById('health_edit_is_minor');
    const guardianGroup = document.getElementById('healthEditGuardianGroup');
    if (isMinor && guardianGroup) {
        guardianGroup.style.display = isMinor.value === 'Yes' ? 'block' : 'none';
    }
}

function viewHealthRecordDetails(recordId) {
    var u = new URL(window.location.href);
    u.searchParams.set('page', 'view_record');
    u.searchParams.set('id', String(recordId));
    u.searchParams.set('module', 'health');
    var inp = document.getElementById('health-search-input');
    if (inp && inp.value.trim() !== '') {
        u.searchParams.set('health_search', inp.value.trim());
    }
    window.location.href = u.pathname + '?' + u.searchParams.toString();
}

// Health form submission
document.addEventListener('DOMContentLoaded', function() {
    const healthAddForm = document.getElementById('healthAddRecordForm');
    if (healthAddForm) {
        healthAddForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_record');
            
            fetch('components/process_health_record.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wpuHandleClinicalRecordSaveResponse(data);
                    setTimeout(() => {
                        window.location.href = '?page=health_dental_records&records_tab=health';
                    }, 1500);
                } else {
                    wpuHandleClinicalRecordSaveResponse(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                AlertSystem.show('An error occurred while saving the record.', 'error');
            });
        });
    }
    
    const healthEditForm = document.getElementById('healthEditRecordForm');
    if (healthEditForm) {
        healthEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_record');
            
            fetch('components/edit_record.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wpuHandleClinicalRecordSaveResponse(data);
                    setTimeout(() => {
                        window.location.href = '?page=health_dental_records&records_tab=health';
                    }, 1500);
                } else {
                    wpuHandleClinicalRecordSaveResponse(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                AlertSystem.show('An error occurred while updating the record.', 'error');
            });
        });
    }
});

// ============================================
// FILE UPLOAD/VIEW/DELETE FUNCTIONS
// ============================================

function uploadFile(patientRecordId) {
    const fileInput = document.getElementById('file_upload');
    const file = fileInput.files[0];
    
    if (!file) {
        AlertSystem.show('Please select a file to upload', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_file');
    formData.append('patient_record_id', patientRecordId);
    formData.append('file', file);
    
    AlertSystem.show('Uploading file...', 'info');
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            AlertSystem.show(data.message, 'success');
            fileInput.value = '';
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            AlertSystem.show(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        AlertSystem.show('Error uploading file. Please try again.', 'error');
    });
}

function viewFile(fileId, fileName, fileType) {
    const viewUrl = 'admin.php?action=view_file&file_id=' + fileId;
    
    // For PDFs, open in new tab to view
    if (fileType === 'application/pdf') {
        window.open(viewUrl, '_blank');
    }
    // For images, show in modal
    else if (fileType.startsWith('image/')) {
        showImageModal(viewUrl, fileName);
    } 
    // For text files, display in a modal
    else if (fileType === 'text/plain') {
        fetch(viewUrl)
        .then(response => response.text())
        .then(content => {
            showFileContentModal(fileName, content, 'text');
        })
        .catch(error => {
            console.error('Error:', error);
            AlertSystem.show('Error loading file content', 'error');
        });
    }
    // For other files (doc, docx), open in new tab
    else {
        window.open(viewUrl, '_blank');
    }
}

function showImageModal(imageUrl, fileName) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 95%; width: auto; max-height: 90vh;">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h2 class="modal-title">🖼️ ${escapeHtml(fileName)}</h2>
            <div style="text-align: center; max-height: 70vh; overflow: auto;">
                <img src="${imageUrl}" alt="${escapeHtml(fileName)}" style="max-width: 100%; height: auto; border-radius: 5px;">
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <a href="${imageUrl}" target="_blank" class="btn btn-primary">🔗 Open in New Tab</a>
                <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function showFileContentModal(fileName, content, contentType) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    let displayContent = '';
    if (contentType === 'text') {
        displayContent = '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 500px; overflow: auto; white-space: pre-wrap; word-wrap: break-word;">' + 
                         escapeHtml(content) + '</pre>';
    }
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 900px;">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h2 class="modal-title">📄 ${escapeHtml(fileName)}</h2>
            <div style="margin: 20px 0;">
                ${displayContent}
            </div>
            <div style="text-align: right;">
                <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function deleteFile(fileId, patientRecordId) {
    if (!confirm('Are you sure you want to delete this file?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_file');
    formData.append('file_id', fileId);
    
    AlertSystem.show('Deleting file...', 'info');
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            AlertSystem.show(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            AlertSystem.show(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        AlertSystem.show('Error deleting file. Please try again.', 'error');
    });
}

// Console welcome message
console.log('%c🏥 WPU Medical Admin System', 'color: #1e40af; font-size: 20px; font-weight: bold;');
console.log('%cSystem initialized successfully', 'color: #10b981; font-size: 14px;');
console.log('%cFor support, contact your system administrator', 'color: #6b7280; font-size: 12px;');
