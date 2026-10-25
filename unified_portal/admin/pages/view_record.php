<?php
/**
 * Modular page: view_record
 * Included by wpu_page_router.php — do not access directly.
 */
                            $record_id = $_GET['id'] ?? '';
                            $module = $_GET['module'] ?? '';
                            
                            if ($record_id):
                                $stmt = $pdo->prepare("SELECT pr.*, pt.type_name, pt.color_code, d.name as department, ct.case_name 
                                     FROM patient_records pr
                                     LEFT JOIN patient_types pt ON pr.patient_type_id = pt.id
                                     LEFT JOIN departments d ON pr.department_id = d.id
                                     LEFT JOIN case_types ct ON pr.case_type_id = ct.id
                                     WHERE pr.id = ? AND pr.module_type = ?");
                                $stmt->execute([$record_id, $module]);
                                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($record):
                                    require_once __DIR__.'/../../includes/wpu_healthhub.php';
                                    $viewerRole = wpu_healthhub_admin_role($pdo);
                                    if (strtolower((string) $module) === 'health') {
                                        if (! wpu_healthhub_can_access_records_tab($viewerRole, 'health')) {
                                            echo '<div class="alert alert-error">You do not have permission to view medical records.</div>';
                                            return;
                                        }
                                        $record = wpu_healthhub_filter_medical_record(
                                            $record,
                                            $viewerRole
                                        );
                                    } elseif (strtolower((string) $module) === 'dental') {
                                        if (! wpu_healthhub_can_access_records_tab($viewerRole, 'dental')) {
                                            echo '<div class="alert alert-error">You do not have permission to view dental records.</div>';
                                            return;
                                        }
                                    }
                                    $rv_name = trim((string) ($record['full_name'] ?? ''));
                                    $rv_parts = preg_split('/\s+/', $rv_name);
                                    if (count($rv_parts) >= 2) {
                                        $rv_initials = strtoupper(substr($rv_parts[0], 0, 1) . substr($rv_parts[count($rv_parts) - 1], 0, 1));
                                    } elseif (($rv_parts[0] ?? '') !== '') {
                                        $w = $rv_parts[0];
                                        $rv_initials = strtoupper(substr($w, 0, min(2, strlen($w))));
                                    } else {
                                        $rv_initials = '?';
                                    }
                                    $rv_type_hex = !empty($record['color_code']) && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $record['color_code'])
                                        ? $record['color_code']
                                        : '';
                                    $rv_type_lum = $rv_type_hex !== '' ? getLuminance($rv_type_hex) : 200;
                                    $rv_type_fg = ($rv_type_lum > 160) ? '#1f2937' : '#ffffff';
                                    $rv_module = strtolower((string) $module) === 'dental' ? 'dental' : 'health';
                                    $rv_dental_search = isset($_GET['dental_search']) ? trim((string) $_GET['dental_search']) : '';
                                    $rv_health_search = isset($_GET['health_search']) ? trim((string) $_GET['health_search']) : '';
                                    $rv_legacy_search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
                                    if ($rv_dental_search === '' && $rv_legacy_search !== '' && $rv_module === 'dental') {
                                        $rv_dental_search = $rv_legacy_search;
                                    }
                                    if ($rv_health_search === '' && $rv_legacy_search !== '' && $rv_module === 'health') {
                                        $rv_health_search = $rv_legacy_search;
                                    }
                                    $rv_dental_page = isset($_GET['dental_page']) ? max(1, (int) $_GET['dental_page']) : 0;
                                    $rv_health_page = isset($_GET['health_page']) ? max(1, (int) $_GET['health_page']) : 0;
                                    $rv_return = [
                                        'page' => 'health_dental_records',
                                        'records_tab' => $rv_module,
                                    ];
                                    if ($rv_dental_search !== '') {
                                        $rv_return['dental_search'] = $rv_dental_search;
                                    }
                                    if ($rv_health_search !== '') {
                                        $rv_return['health_search'] = $rv_health_search;
                                    }
                                    if ($rv_dental_page > 0) {
                                        $rv_return['dental_page'] = $rv_dental_page;
                                    }
                                    if ($rv_health_page > 0) {
                                        $rv_return['health_page'] = $rv_health_page;
                                    }
                                    $rv_list_href = '?' . http_build_query($rv_return);
                                    $rv_view_keep = [];
                                    if ($rv_dental_search !== '') {
                                        $rv_view_keep['dental_search'] = $rv_dental_search;
                                    }
                                    if ($rv_health_search !== '') {
                                        $rv_view_keep['health_search'] = $rv_health_search;
                                    }
                                    if ($rv_dental_page > 0) {
                                        $rv_view_keep['dental_page'] = $rv_dental_page;
                                    }
                                    if ($rv_health_page > 0) {
                                        $rv_view_keep['health_page'] = $rv_health_page;
                                    }
                                    if ($rv_legacy_search !== '' && $rv_dental_search === '' && $rv_health_search === '') {
                                        $rv_view_keep['search'] = $rv_legacy_search;
                                    }
                                    $rv_history = [];
                                    $rv_hist_sid = trim((string) ($record['student_id'] ?? ''));
                                    if ($rv_hist_sid !== '') {
                                        $rv_hist_stmt = $pdo->prepare(
                                            'SELECT pr.id, pr.visit_date, pr.doctor, d.name AS department, ct.case_name
                                             FROM patient_records pr
                                             LEFT JOIN departments d ON pr.department_id = d.id
                                             LEFT JOIN case_types ct ON pr.case_type_id = ct.id
                                             WHERE pr.module_type = ? AND pr.student_id = ?
                                             ORDER BY pr.visit_date DESC, pr.id DESC
                                             LIMIT 50'
                                        );
                                        $rv_hist_stmt->execute([$module, $rv_hist_sid]);
                                    } else {
                                        $rv_hist_stmt = $pdo->prepare(
                                            'SELECT pr.id, pr.visit_date, pr.doctor, d.name AS department, ct.case_name
                                             FROM patient_records pr
                                             LEFT JOIN departments d ON pr.department_id = d.id
                                             LEFT JOIN case_types ct ON pr.case_type_id = ct.id
                                             WHERE pr.module_type = ?
                                             AND (pr.student_id IS NULL OR pr.student_id = \'\')
                                             AND pr.full_name = ?
                                             ORDER BY pr.visit_date DESC, pr.id DESC
                                             LIMIT 50'
                                        );
                                        $rv_hist_stmt->execute([$module, $record['full_name']]);
                                    }
                                    $rv_history = $rv_hist_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    require_once __DIR__ . '/../components/patient_record_quick_view_lib.php';
                                    $rv_history_ids = array_map(static fn (array $h): int => (int) $h['id'], $rv_history);
                                    $rv_edit_history = [];
                                    try {
                                        $rv_edit_history = patient_record_edit_history_for_records($pdo, $rv_history_ids);
                                    } catch (Throwable $e) {
                                        $rv_edit_history = [];
                                    }
                            ?>
                            <div class="content-card record-view-page">
                                <div class="record-view-toolbar">
                                    <a href="<?php echo htmlspecialchars($rv_list_href, ENT_QUOTES, 'UTF-8'); ?>" class="record-view-back">
                                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                        Back to <?php echo htmlspecialchars(ucfirst((string) $module), ENT_QUOTES, 'UTF-8'); ?> records
                                    </a>
                                    <div class="record-view-toolbar-actions">
                                        <?php if (wpu_healthhub_can_access_workspace_page($viewerRole, 'consent_refusal_module')): ?>
                                        <a class="btn btn-success" href="?page=consent_refusal_module&amp;patient_record_id=<?php echo (int) $record['id']; ?>">
                                            <i class="fas fa-file-contract" aria-hidden="true"></i>
                                            Consent &amp; Refusal
                                        </a>
                                        <?php elseif (wpu_healthhub_can_access_workspace_page($viewerRole, 'secure_consent')): ?>
                                        <a class="btn btn-success" href="?page=secure_consent&amp;patient_record_id=<?php echo (int) $record['id']; ?>">
                                            <i class="fas fa-file-signature" aria-hidden="true"></i>
                                            Medical consent
                                        </a>
                                        <?php
                                        $rv_consent_id = (int) ($record['consent_record_id'] ?? 0);
                                        if ($rv_consent_id > 0): ?>
                                        <a class="btn btn-secondary" href="?page=view_consent&amp;id=<?php echo $rv_consent_id; ?>">
                                            View consent #<?php echo $rv_consent_id; ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-warning" onclick="open<?php echo ucfirst($module); ?>EditModal(<?php echo (int) $record['id']; ?>)">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                            Edit record
                                        </button>
                                        <button type="button" class="btn btn-info" onclick="window.print()">
                                            <i class="fas fa-print" aria-hidden="true"></i>
                                            Print
                                        </button>
                                    </div>
                                </div>
                                <div class="record-view-body">
                                    <header class="record-view-hero">
                                        <div class="record-view-hero-avatar" aria-hidden="true"><?php echo htmlspecialchars($rv_initials, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="record-view-hero-main">
                                            <h1 class="record-view-hero-title"><?php echo htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                                            <div class="record-view-badges">
                                                <span class="record-view-badge record-view-badge--<?php echo $rv_module === 'dental' ? 'dental' : 'health'; ?>">
                                                    <i class="fas <?php echo $rv_module === 'dental' ? 'fa-tooth' : 'fa-heartbeat'; ?>" aria-hidden="true"></i>
                                                    <?php echo htmlspecialchars(ucfirst((string) $module), ENT_QUOTES, 'UTF-8'); ?> clinic
                                                </span>
                                                <?php if (!empty($record['type_name'])): ?>
                                                <span class="record-view-badge record-view-badge--type"<?php echo $rv_type_hex !== '' ? ' style="background-color:' . htmlspecialchars($rv_type_hex, ENT_QUOTES, 'UTF-8') . ';color:' . htmlspecialchars($rv_type_fg, ENT_QUOTES, 'UTF-8') . ';"' : ''; ?>>
                                                    <?php echo htmlspecialchars((string) $record['type_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <ul class="record-view-quick-meta">
                                                <li>
                                                    <strong>Student / patient ID</strong>
                                                    <span><?php echo htmlspecialchars((string) $record['student_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </li>
                                                <li>
                                                    <strong>Visit date</strong>
                                                    <span><?php echo htmlspecialchars(date('M j, Y', strtotime($record['visit_date'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                                </li>
                                                <li>
                                                    <strong>Department</strong>
                                                    <span><?php echo htmlspecialchars((string) ($record['department'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </header>

                                    <div class="record-view-shell">
                                    <div class="record-view-main">
                                    <div class="record-view-grid">
                                        <section class="record-view-section" aria-labelledby="rv-personal-heading">
                                            <h2 class="record-view-section-title" id="rv-personal-heading">
                                                <i class="fas fa-user" aria-hidden="true"></i>
                                                Personal information
                                            </h2>
                                            <dl class="record-view-dl record-view-dl--two">
                                                <div class="record-view-field">
                                                    <dt>Gender</dt>
                                                    <dd><?php echo htmlspecialchars((string) ($record['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </div>
                                                <div class="record-view-field">
                                                    <dt>Age</dt>
                                                    <dd><?php echo htmlspecialchars((string) ($record['age'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php echo isset($record['age']) && $record['age'] !== '' ? ' years' : ''; ?></dd>
                                                </div>
                                                <div class="record-view-field">
                                                    <dt>Marital status</dt>
                                                    <dd><?php echo htmlspecialchars((string) ($record['marital_status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </div>
                                                <div class="record-view-field">
                                                    <dt>Religion</dt>
                                                    <dd><?php echo !empty($record['religion']) ? htmlspecialchars((string) $record['religion'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></dd>
                                                </div>
                                                <div class="record-view-field">
                                                    <dt>Minor status</dt>
                                                    <dd><?php echo htmlspecialchars((string) ($record['is_minor'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </div>
                                                <?php if (($record['is_minor'] ?? '') === 'Yes' && !empty($record['guardian_name'])): ?>
                                                <div class="record-view-field">
                                                    <dt>Guardian</dt>
                                                    <dd><?php echo htmlspecialchars((string) $record['guardian_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </div>
                                                <?php endif; ?>
                                                <div class="record-view-field">
                                                    <dt>Phone</dt>
                                                    <dd><?php echo !empty($record['phone_number']) ? htmlspecialchars((string) $record['phone_number'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></dd>
                                                </div>
                                                <div class="record-view-field record-view-field--full">
                                                    <dt>Address</dt>
                                                    <dd><?php echo !empty($record['address']) ? nl2br(htmlspecialchars((string) $record['address'], ENT_QUOTES, 'UTF-8')) : 'N/A'; ?></dd>
                                                </div>
                                            </dl>
                                        </section>

                                        <section class="record-view-section" aria-labelledby="rv-medical-heading">
                                            <h2 class="record-view-section-title" id="rv-medical-heading">
                                                <i class="fas fa-stethoscope" aria-hidden="true"></i>
                                                Medical information
                                            </h2>
                                            <dl class="record-view-dl">
                                                <div class="record-view-field">
                                                    <dt>Attending doctor</dt>
                                                    <dd><?php echo htmlspecialchars((string) ($record['doctor'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </div>
                                                <div class="record-view-field">
                                                    <dt>Case type</dt>
                                                    <dd><?php echo htmlspecialchars((string) ($record['case_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></dd>
                                                </div>
                                                <div class="record-view-field">
                                                    <dt>Diagnosis</dt>
                                                    <dd><?php echo nl2br(htmlspecialchars((string) ($record['diagnosis'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></dd>
                                                </div>
                                            </dl>
                                        </section>

                                        <section class="record-view-section record-view-section--wide" aria-labelledby="rv-treatment-heading">
                                            <h2 class="record-view-section-title" id="rv-treatment-heading">
                                                <i class="fas fa-notes-medical" aria-hidden="true"></i>
                                                Treatment
                                            </h2>
                                            <div class="record-view-prose"><?php echo nl2br(htmlspecialchars((string) ($record['treatment'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                                        </section>

                                        <?php if (!empty($record['subjective']) || !empty($record['objectives']) || !empty($record['diagnostics']) || !empty($record['assessment']) || !empty($record['plan'])): ?>
                                        <section class="record-view-section record-view-section--wide" aria-labelledby="rv-clinical-heading">
                                            <h2 class="record-view-section-title" id="rv-clinical-heading">
                                                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                                Clinical notes (SOAP)
                                            </h2>
                                            <?php if (!empty($record['subjective'])): ?>
                                            <div class="record-view-soap">
                                                <div class="record-view-soap__label"><i class="fas fa-comment-medical" aria-hidden="true"></i> Subjective</div>
                                                <div class="record-view-prose"><?php echo nl2br(htmlspecialchars((string) $record['subjective'], ENT_QUOTES, 'UTF-8')); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($record['objectives'])): ?>
                                            <div class="record-view-soap">
                                                <div class="record-view-soap__label"><i class="fas fa-eye" aria-hidden="true"></i> Objective</div>
                                                <div class="record-view-prose"><?php echo nl2br(htmlspecialchars((string) $record['objectives'], ENT_QUOTES, 'UTF-8')); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($record['diagnostics'])): ?>
                                            <div class="record-view-soap">
                                                <div class="record-view-soap__label"><i class="fas fa-microscope" aria-hidden="true"></i> Diagnostics</div>
                                                <div class="record-view-prose"><?php echo nl2br(htmlspecialchars((string) $record['diagnostics'], ENT_QUOTES, 'UTF-8')); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($record['assessment'])): ?>
                                            <div class="record-view-soap">
                                                <div class="record-view-soap__label"><i class="fas fa-diagnoses" aria-hidden="true"></i> Assessment</div>
                                                <div class="record-view-prose"><?php echo nl2br(htmlspecialchars((string) $record['assessment'], ENT_QUOTES, 'UTF-8')); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($record['plan'])): ?>
                                            <div class="record-view-soap">
                                                <div class="record-view-soap__label"><i class="fas fa-tasks" aria-hidden="true"></i> Plan</div>
                                                <div class="record-view-prose"><?php echo nl2br(htmlspecialchars((string) $record['plan'], ENT_QUOTES, 'UTF-8')); ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </section>
                                        <?php endif; ?>

                                        <section class="record-view-section record-view-section--wide" aria-labelledby="rv-files-heading">
                                            <div class="record-view-files-head">
                                                <div>
                                                    <h2 class="record-view-section-title" id="rv-files-heading">
                                                        <i class="fas fa-paperclip" aria-hidden="true"></i>
                                                        File attachments
                                                    </h2>
                                                    <p>Images, PDF, or documents linked to this visit. One file per record; remove the current file to replace it.</p>
                                                </div>
                                            </div>

                                            <?php
                                            $existingFilesStmt = $pdo->prepare("SELECT COUNT(*) FROM patient_files WHERE patient_record_id = ?");
                                            $existingFilesStmt->execute([$record_id]);
                                            $hasFile = $existingFilesStmt->fetchColumn() > 0;
                                            ?>

                                            <?php if (!$hasFile): ?>
                                            <form id="fileUploadForm" class="record-view-upload" enctype="multipart/form-data">
                                                <div class="record-view-upload-row">
                                                    <div class="record-view-upload-field">
                                                        <label for="file_upload">Choose file</label>
                                                        <input type="file" id="file_upload" name="file_upload" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.doc,.docx" class="form-control">
                                                    </div>
                                                    <button type="button" class="btn btn-success" onclick="uploadFile(<?php echo (int) $record['id']; ?>)">
                                                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                        Upload
                                                    </button>
                                                </div>
                                            </form>
                                            <?php else: ?>
                                            <div class="alert alert-warning record-view-file-alert" role="status">A file is already attached. Delete it first to upload a different one.</div>
                                            <?php endif; ?>

                                            <div id="filesList">
                                                <?php
                                                $filesStmt = $pdo->prepare("SELECT * FROM patient_files WHERE patient_record_id = ? ORDER BY id DESC");
                                                $filesStmt->execute([$record_id]);
                                                $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

                                                if (!empty($files)):
                                                ?>
                                                <div class="record-view-files-table-wrap">
                                                    <table class="record-view-files-table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">File name</th>
                                                                <th scope="col">Type</th>
                                                                <th scope="col">Size</th>
                                                                <th scope="col">Uploaded</th>
                                                                <th scope="col">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($files as $file):
                                                                $fileUploadedAt = $file['created_at'] ?? $file['uploaded_at'] ?? null;
                                                            ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars((string) $file['file_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo htmlspecialchars((string) $file['file_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?php echo number_format((float) (($file['file_size'] ?? 0) / 1024), 2); ?> KB</td>
                                                                <td><?php echo $fileUploadedAt ? htmlspecialchars(date('M j, Y g:i A', strtotime($fileUploadedAt)), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                                                <td>
                                                                    <div class="record-view-file-actions">
                                                                        <button type="button" class="btn btn-primary" onclick="viewFile(<?php echo (int) $file['id']; ?>, '<?php echo htmlspecialchars((string) $file['file_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars((string) $file['file_type'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                                                            View
                                                                        </button>
                                                                        <button type="button" class="btn btn-danger" onclick="deleteFile(<?php echo (int) $file['id']; ?>, <?php echo (int) $record['id']; ?>)" aria-label="Delete file <?php echo htmlspecialchars((string) $file['file_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <?php else: ?>
                                                <div class="record-view-files-empty" role="status">
                                                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                                                    No files uploaded yet.
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </section>
                                    </div>
                                    </div>
                                    </div>

                                    <section class="record-view-past-visits" aria-labelledby="rv-history-heading">
                                        <h2 class="record-view-past-visits__title" id="rv-history-heading">
                                            <i class="fas fa-history" aria-hidden="true"></i>
                                            Previous visits &amp; history
                                        </h2>
                                        <p class="record-view-past-visits__hint">
                                            <?php if ($rv_hist_sid !== ''): ?>
                                            All <?php echo htmlspecialchars(ucfirst((string) $module), ENT_QUOTES, 'UTF-8'); ?> visits for ID <?php echo htmlspecialchars($rv_hist_sid, ENT_QUOTES, 'UTF-8'); ?> (newest first). Open any row to view that visit. Under each visit, every saved prior version is listed — not only the last edit.
                                            <?php else: ?>
                                            All <?php echo htmlspecialchars(ucfirst((string) $module), ENT_QUOTES, 'UTF-8'); ?> visits for this name (no student ID on file). Open a row to switch visits. Edit history under a visit lists every saved prior version.
                                            <?php endif; ?>
                                        </p>
                                        <?php if (count($rv_history) >= 1): ?>
                                        <nav aria-label="Visits for this patient">
                                            <ul class="record-view-history">
                                            <?php foreach ($rv_history as $rv_hrow):
                                                $rv_hid = (int) $rv_hrow['id'];
                                                $is_rv_current = $rv_hid === (int) $record['id'];
                                                $rv_h_visit = $rv_hrow['visit_date'] ?? '';
                                                $rv_h_visit_fmt = $rv_h_visit !== '' ? date('M j, Y', strtotime($rv_h_visit)) : 'No visit date';
                                                $rv_h_url = '?' . http_build_query(array_merge([
                                                    'page' => 'view_record',
                                                    'id' => $rv_hid,
                                                    'module' => (string) $module,
                                                ], $rv_view_keep));
                                            ?>
                                            <li class="record-view-history__item<?php echo $is_rv_current ? ' is-current' : ''; ?>">
                                                <div class="record-view-history__row">
                                                    <a class="record-view-history__link record-view-history__link--main" href="<?php echo htmlspecialchars($rv_h_url, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $is_rv_current ? ' aria-current="page"' : ''; ?>>
                                                        <?php if ($is_rv_current): ?>
                                                        <span class="record-view-history__badge">Viewing</span>
                                                        <?php endif; ?>
                                                        <span class="record-view-history__date"><?php echo htmlspecialchars($rv_h_visit_fmt, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <span class="record-view-history__meta">
                                                            <?php echo htmlspecialchars((string) ($rv_hrow['department'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($rv_hrow['department']) && !empty($rv_hrow['doctor']) ? ' · ' : ''; ?><?php echo htmlspecialchars((string) ($rv_hrow['doctor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                        <?php if (!empty($rv_hrow['case_name'])): ?>
                                                        <span class="record-view-history__meta"><?php echo htmlspecialchars((string) $rv_hrow['case_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                    <button type="button" class="btn btn-secondary btn-sm record-view-history__peek" onclick="openHistoryRecordSnapshotModal(<?php echo (int) $rv_hid; ?>, '<?php echo htmlspecialchars((string) $module, ENT_QUOTES, 'UTF-8'); ?>')" aria-label="Quick view visit on <?php echo htmlspecialchars($rv_h_visit_fmt, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="fas fa-file-medical-alt" aria-hidden="true"></i>
                                                        Quick view
                                                    </button>
                                                </div>
                                                <?php
                                                $rv_h_edits = $rv_edit_history[$rv_hid] ?? [];
                                                if ($rv_h_edits !== []):
                                                ?>
                                                <ul class="record-view-history__edits" aria-label="Edit history for visit on <?php echo htmlspecialchars($rv_h_visit_fmt, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php foreach ($rv_h_edits as $rv_edit_i => $rv_edit):
                                                        $rv_edit_id = (int) $rv_edit['id'];
                                                        $rv_edit_at = $rv_edit['created_at'] ?? '';
                                                        $rv_edit_at_fmt = $rv_edit_at !== '' ? date('M j, Y g:i A', strtotime($rv_edit_at)) : 'Unknown time';
                                                        $rv_edit_by = trim((string) ($rv_edit['edited_by'] ?? ''));
                                                        $rv_edit_n = count($rv_h_edits) - (int) $rv_edit_i;
                                                    ?>
                                                    <li class="record-view-history__edit">
                                                        <div class="record-view-history__edit-main">
                                                            <span class="record-view-history__edit-badge">Version <?php echo (int) $rv_edit_n; ?></span>
                                                            <span class="record-view-history__edit-when"><?php echo htmlspecialchars($rv_edit_at_fmt, ENT_QUOTES, 'UTF-8'); ?></span>
                                                            <span class="record-view-history__edit-meta">
                                                                Saved before an edit<?php echo $rv_edit_by !== '' ? ' · '.htmlspecialchars($rv_edit_by, ENT_QUOTES, 'UTF-8') : ''; ?>
                                                            </span>
                                                        </div>
                                                        <button type="button" class="btn btn-secondary btn-sm record-view-history__peek" onclick="openHistoryRecordSnapshotModal(<?php echo (int) $rv_hid; ?>, '<?php echo htmlspecialchars((string) $module, ENT_QUOTES, 'UTF-8'); ?>', <?php echo $rv_edit_id; ?>)" aria-label="View saved version from <?php echo htmlspecialchars($rv_edit_at_fmt, ENT_QUOTES, 'UTF-8'); ?>">
                                                            <i class="fas fa-clock" aria-hidden="true"></i>
                                                            View version
                                                        </button>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php elseif ($is_rv_current): ?>
                                                <p class="record-view-history__edits-empty">No prior versions for this visit yet. Each time you edit and save, the previous version is added here.</p>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                            </ul>
                                        </nav>
                                        <?php if (count($rv_history) === 1): ?>
                                        <p class="record-view-past-visits__hint" style="margin-top:12px;margin-bottom:0;">Only one visit on file for this patient in <?php echo htmlspecialchars(ucfirst((string) $module), ENT_QUOTES, 'UTF-8'); ?>.</p>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <p class="record-view-past-visits__hint" style="margin-bottom:0;">No related visits found.</p>
                                        <?php endif; ?>
                                    </section>
                                </div>
                            </div>
                            <?php
                                else:
                                    $rv_bad_mod = preg_replace('/[^a-z]/', '', strtolower((string) $module));
                                    $rv_bad_href = in_array($rv_bad_mod, ['dental', 'health'], true)
                                        ? '?page=' . rawurlencode($rv_bad_mod . '_records')
                                        : '?page=dental_records';
                            ?>
                            <div class="content-card">
                                <div class="card-body">
                                    <div class="table-empty" role="alert">
                                        <span class="table-empty__icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                                        <p class="table-empty__title">Record not found</p>
                                        <p class="table-empty__hint">This ID may have been removed or the link is wrong. Open the records list and try again.</p>
                                        <a href="<?php echo htmlspecialchars($rv_bad_href, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to records</a>
                                    </div>
                                </div>
                            </div>
                            <?php
                                endif;
                            else:
                            ?>
                            <div class="content-card">
                                <div class="card-body">
                                    <div class="table-empty" role="alert">
                                        <span class="table-empty__icon" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                                        <p class="table-empty__title">Invalid record link</p>
                                        <p class="table-empty__hint">Open a record from the dental or health records list so the address includes a valid ID.</p>
                                        <div class="record-view-error-actions">
                                            <a href="?page=dental_records" class="btn btn-primary">Dental records</a>
                                            <a href="?page=health_records" class="btn btn-secondary">Health records</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            endif;
