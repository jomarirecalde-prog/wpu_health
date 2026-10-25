<?php
/**
 * Health & dental patient records list page (tabbed).
 */
if ($page === 'dental_records') {
    $records_tab = 'dental';
} elseif ($page === 'health_records') {
    $records_tab = 'health';
}

require_once __DIR__.'/../../includes/wpu_healthhub.php';
$hd_clinical_role = wpu_healthhub_admin_role($pdo);
$hd_show_health_tab = wpu_healthhub_can_access_records_tab($hd_clinical_role, 'health');
$hd_show_dental_tab = wpu_healthhub_can_access_records_tab($hd_clinical_role, 'dental');
if ($records_tab === 'health' && ! $hd_show_health_tab && $hd_show_dental_tab) {
    $records_tab = 'dental';
} elseif ($records_tab === 'dental' && ! $hd_show_dental_tab && $hd_show_health_tab) {
    $records_tab = 'health';
}
$hd_page_title = match ($hd_clinical_role) {
    'dentist' => 'Dental Records',
    'physician' => 'Medical & Dental Records',
    default => 'Medical and Dental Records',
};

$dental_offset = ($dental_page - 1) * $health_dental_items_per_page;
$health_offset = ($health_page - 1) * $health_dental_items_per_page;

if (! function_exists('wpu_his_diagnosis_preview')) {
    function wpu_his_diagnosis_preview(?string $text): string
    {
        $diag = trim((string) $text);
        if ($diag === '') {
            return '—';
        }
        if (strlen($diag) > 50) {
            return htmlspecialchars(substr($diag, 0, 50), ENT_QUOTES, 'UTF-8').'…';
        }

        return htmlspecialchars($diag, ENT_QUOTES, 'UTF-8');
    }
}
?>
<div class="content-card cr-combined">
    <div class="cr-tabs-wrap">
        <h2 class="card-title" style="margin:0 0 1rem;font-size:1.125rem"><?php echo htmlspecialchars($hd_page_title, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if ($hd_show_health_tab && $hd_show_dental_tab): ?>
        <div class="cr-tabs" role="tablist" aria-label="Medical and dental records">
            <button type="button" role="tab" id="hd-tab-health-btn"
                    aria-selected="<?php echo $records_tab === 'health' ? 'true' : 'false'; ?>"
                    aria-controls="health-records-tab"
                    class="cr-tab <?php echo $records_tab === 'health' ? 'is-active' : ''; ?>"
                    onclick="switchRecordsTab('health')">
                <i class="fas fa-heartbeat" aria-hidden="true"></i> Medical patients
            </button>
            <button type="button" role="tab" id="hd-tab-dental-btn"
                    aria-selected="<?php echo $records_tab === 'dental' ? 'true' : 'false'; ?>"
                    aria-controls="dental-records-tab"
                    class="cr-tab <?php echo $records_tab === 'dental' ? 'is-active' : ''; ?>"
                    onclick="switchRecordsTab('dental')">
                <i class="fas fa-tooth" aria-hidden="true"></i> Dental records
            </button>
        </div>
        <?php elseif ($hd_show_dental_tab): ?>
            <p class="cr-intro" style="margin:0 0 1rem;color:var(--his-text-muted,#64748B);font-size:0.9375rem">Dental patient files and treatment history.</p>
        <?php elseif ($hd_show_health_tab): ?>
            <p class="cr-intro" style="margin:0 0 1rem;color:var(--his-text-muted,#64748B);font-size:0.9375rem">Medical clinic visits and patient files.</p>
        <?php endif; ?>
    </div>

    <div id="dental-records-tab" class="cr-tab-panel" role="tabpanel" aria-labelledby="hd-tab-dental-btn"
         <?php echo $records_tab !== 'dental' ? 'hidden' : ''; ?>>
        <div class="cr-intro cr-intro--dental">
            <span class="cr-count-pill"><?php echo number_format((int) $dental_total); ?> total</span>
        </div>
        <div class="cr-toolbar">
            <button type="button" class="btn btn-primary" onclick="openDentalAddRecordModal()">
                <i class="fas fa-plus" aria-hidden="true"></i> Add dental record
            </button>
            <div class="cr-search-row">
                <label class="visually-hidden" for="dental-search-input">Search dental records</label>
                <input type="search" id="dental-search-input" class="form-control"
                       placeholder="Search by patient name…"
                       value="<?php echo htmlspecialchars($dental_search, ENT_QUOTES, 'UTF-8'); ?>"
                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); submitDentalSearch(); }">
                <button type="button" class="btn btn-secondary" onclick="submitDentalSearch()">Search</button>
                <span class="cr-search-hint">Press Enter or click Search</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="dentalRecordsTable">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Type</th>
                        <th scope="col">Department</th>
                        <th scope="col">Visit date</th>
                        <th scope="col">Case</th>
                        <th scope="col">Diagnosis</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dental_result instanceof mysqli_result && $dental_result->num_rows > 0): ?>
                        <?php $dental_counter = $dental_offset + 1; ?>
                        <?php while ($row = $dental_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo (int) $dental_counter; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <?php if (! empty($row['color_code'])): ?>
                                        <span class="badge" style="background:<?php echo htmlspecialchars($row['color_code'], ENT_QUOTES, 'UTF-8'); ?>;color:#fff;">
                                            <?php echo htmlspecialchars($row['type_name'] ?? ''); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($row['type_name'] ?? '—'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['department_name'] ?? '—'); ?></td>
                                <td><?php echo ! empty($row['visit_date']) ? date('M d, Y', strtotime($row['visit_date'])) : '—'; ?></td>
                                <td><?php echo htmlspecialchars($row['case_name'] ?? '—'); ?></td>
                                <td><?php echo wpu_his_diagnosis_preview($row['diagnosis'] ?? ''); ?></td>
                                <td class="actions-cell">
                                    <button type="button" class="btn btn-view btn-icon btn-sm" title="View"
                                            onclick="viewDentalRecordDetails(<?php echo (int) $row['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-edit btn-icon btn-sm" title="Edit"
                                            onclick="openDentalEditModal(<?php echo (int) $row['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-delete btn-icon btn-sm" title="Delete"
                                            onclick="deleteDentalRecord(<?php echo (int) $row['id']; ?>, <?php echo json_encode((string) $row['full_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php $dental_counter++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="table-empty" role="status">
                                    <span class="table-empty__icon" aria-hidden="true"><i class="fas fa-tooth"></i></span>
                                    <p class="table-empty__title">No dental records</p>
                                    <p class="table-empty__hint"><?php echo $dental_search !== '' ? 'No matches for your search.' : 'Add a dental visit to populate this list.'; ?></p>
                                    <?php if ($dental_search === ''): ?>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openDentalAddRecordModal()">Add dental record</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($dental_total > 0): ?>
        <div class="pagination">
            <div class="pagination-info">
                Showing <?php echo (int) ($dental_offset + 1); ?> to <?php echo (int) min($dental_offset + $health_dental_items_per_page, $dental_total); ?> of <?php echo (int) $dental_total; ?> entries
            </div>
            <div class="pagination-controls">
                <button type="button" <?php echo $dental_page <= 1 ? 'disabled' : ''; ?> onclick="changePageTabRecords('dental', <?php echo (int) ($dental_page - 1); ?>)">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i> Previous
                </button>
                <span class="pagination-page-label">Page <?php echo (int) $dental_page; ?> of <?php echo max(1, (int) $dental_total_pages); ?></span>
                <button type="button" <?php echo ($dental_total_pages < 2 || $dental_page >= $dental_total_pages) ? 'disabled' : ''; ?> onclick="changePageTabRecords('dental', <?php echo (int) ($dental_page + 1); ?>)">
                    Next <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div id="health-records-tab" class="cr-tab-panel" role="tabpanel" aria-labelledby="hd-tab-health-btn"
         <?php echo $records_tab !== 'health' ? 'hidden' : ''; ?>>
        <div class="cr-intro cr-intro--health">
            <p>General medical / health clinic visits — search, view SOAP notes, and manage patient files.</p>
            <span class="cr-count-pill"><?php echo number_format((int) $health_total); ?> total</span>
        </div>
        <div class="cr-toolbar">
            <button type="button" class="btn btn-primary" onclick="openHealthAddRecordModal()">
                <i class="fas fa-plus" aria-hidden="true"></i> Add health record
            </button>
            <div class="cr-search-row">
                <label class="visually-hidden" for="health-search-input">Search health records</label>
                <input type="search" id="health-search-input" class="form-control"
                       placeholder="Search by patient name…"
                       value="<?php echo htmlspecialchars($health_search, ENT_QUOTES, 'UTF-8'); ?>"
                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); submitHealthSearch(); }">
                <button type="button" class="btn btn-secondary" onclick="submitHealthSearch()">Search</button>
                <span class="cr-search-hint">Press Enter or click Search</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="healthRecordsTable">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Type</th>
                        <th scope="col">Department</th>
                        <th scope="col">Visit date</th>
                        <th scope="col">Case</th>
                        <th scope="col">Diagnosis</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($health_result instanceof mysqli_result && $health_result->num_rows > 0): ?>
                        <?php $health_counter = $health_offset + 1; ?>
                        <?php while ($row = $health_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo (int) $health_counter; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <?php if (! empty($row['color_code'])): ?>
                                        <span class="badge" style="background:<?php echo htmlspecialchars($row['color_code'], ENT_QUOTES, 'UTF-8'); ?>;color:#fff;">
                                            <?php echo htmlspecialchars($row['type_name'] ?? ''); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($row['type_name'] ?? '—'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['department_name'] ?? '—'); ?></td>
                                <td><?php echo ! empty($row['visit_date']) ? date('M d, Y', strtotime($row['visit_date'])) : '—'; ?></td>
                                <td><?php echo htmlspecialchars($row['case_name'] ?? '—'); ?></td>
                                <td><?php echo wpu_his_diagnosis_preview($row['diagnosis'] ?? ''); ?></td>
                                <td class="actions-cell">
                                    <button type="button" class="btn btn-view btn-icon btn-sm" title="View"
                                            onclick="viewHealthRecordDetails(<?php echo (int) $row['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-edit btn-icon btn-sm" title="Edit"
                                            onclick="openHealthEditModal(<?php echo (int) $row['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-delete btn-icon btn-sm" title="Delete"
                                            onclick="deleteHealthRecord(<?php echo (int) $row['id']; ?>, <?php echo json_encode((string) $row['full_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php $health_counter++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="table-empty" role="status">
                                    <span class="table-empty__icon" aria-hidden="true"><i class="fas fa-heartbeat"></i></span>
                                    <p class="table-empty__title">No health records</p>
                                    <p class="table-empty__hint"><?php echo $health_search !== '' ? 'No matches for your search.' : 'Add a health visit to populate this list.'; ?></p>
                                    <?php if ($health_search === ''): ?>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openHealthAddRecordModal()">Add health record</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($health_total > 0): ?>
        <div class="pagination">
            <div class="pagination-info">
                Showing <?php echo (int) ($health_offset + 1); ?> to <?php echo (int) min($health_offset + $health_dental_items_per_page, $health_total); ?> of <?php echo (int) $health_total; ?> entries
            </div>
            <div class="pagination-controls">
                <button type="button" <?php echo $health_page <= 1 ? 'disabled' : ''; ?> onclick="changePageTabRecords('health', <?php echo (int) ($health_page - 1); ?>)">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i> Previous
                </button>
                <span class="pagination-page-label">Page <?php echo (int) $health_page; ?> of <?php echo max(1, (int) $health_total_pages); ?></span>
                <button type="button" <?php echo ($health_total_pages < 2 || $health_page >= $health_total_pages) ? 'disabled' : ''; ?> onclick="changePageTabRecords('health', <?php echo (int) ($health_page + 1); ?>)">
                    Next <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
