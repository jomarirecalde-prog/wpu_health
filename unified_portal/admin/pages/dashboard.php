<?php
/**
 * Modular page: dashboard
 * Included by wpu_page_router.php — do not access directly.
 */
                            $dash_hour = (int) date('G');
                            $dash_greeting = $dash_hour < 12 ? 'Good morning' : ($dash_hour < 17 ? 'Good afternoon' : 'Good evening');
                            $dash_total_records = (int) $cert_total + (int) $ref_total + (int) $dental_total + (int) $health_total;
                            $dash_recent_certs = [];
                            $dash_recent_refs = [];
                            $dash_recent_patients = [];
                            if ($certificates_result instanceof mysqli_result) {
                                $certificates_result->data_seek(0);
                                $i = 0;
                                while ($i < 5 && ($r = $certificates_result->fetch_assoc())) {
                                    $dash_recent_certs[] = $r;
                                    $i++;
                                }
                                $certificates_result->data_seek(0);
                            }
                            if ($referrals_result instanceof mysqli_result) {
                                $referrals_result->data_seek(0);
                                $i = 0;
                                while ($i < 5 && ($r = $referrals_result->fetch_assoc())) {
                                    $dash_recent_refs[] = $r;
                                    $i++;
                                }
                                $referrals_result->data_seek(0);
                            }
                            if ($dental_result instanceof mysqli_result) {
                                $dental_result->data_seek(0);
                                $i = 0;
                                while ($i < 3 && ($r = $dental_result->fetch_assoc())) {
                                    $r['_module'] = 'Dental';
                                    $dash_recent_patients[] = $r;
                                    $i++;
                                }
                                $dental_result->data_seek(0);
                            }
                            if ($health_result instanceof mysqli_result) {
                                $health_result->data_seek(0);
                                $i = 0;
                                while ($i < 2 && ($r = $health_result->fetch_assoc())) {
                                    $r['_module'] = 'Health';
                                    $dash_recent_patients[] = $r;
                                    $i++;
                                }
                                $health_result->data_seek(0);
                            }
                            $dash_appt_today = 0;
                            $dash_appt_pending = 0;
                            $dash_appt_confirmed = 0;
                            if (isset($conn) && $conn instanceof mysqli) {
                                $today = date('Y-m-d');
                                $tbl = @$conn->query("SHOW TABLES LIKE 'appointments'");
                                if ($tbl && $tbl->num_rows > 0) {
                                    $r = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE appointment_date = '".$conn->real_escape_string($today)."'");
                                    if ($r) { $dash_appt_today = (int) ($r->fetch_assoc()['c'] ?? 0); }
                                    $r = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status = 'pending'");
                                    if ($r) { $dash_appt_pending = (int) ($r->fetch_assoc()['c'] ?? 0); }
                                    $r = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE status = 'confirmed' AND appointment_date >= '".$conn->real_escape_string($today)."'");
                                    if ($r) { $dash_appt_confirmed = (int) ($r->fetch_assoc()['c'] ?? 0); }
                                }
                            }
                            $wpu_calendar_base = (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('url'))
                                ? url('/admin/calendar')
                                : '/admin/calendar';
                            ?>
                            <div class="dashboard-home">
                                <div class="dashboard-welcome">
                                    <p class="dashboard-greeting"><?php echo htmlspecialchars($dash_greeting); ?>, <strong><?php echo htmlspecialchars($current_user); ?></strong></p>
                                    <h2 class="dashboard-lead">Clinical operations overview</h2>
                                    <p class="dashboard-sub">
                                        <strong><?php echo number_format($dash_total_records); ?></strong> total items on file.
                                        Use the cards below to jump to a module, or quick actions to create new entries.
                                    </p>
                                </div>

                                <h3 class="dashboard-section-title">Key performance indicators</h3>
                                <div class="stats-grid dashboard-stats">
                                    <a class="stat-card stat-card-link" href="?page=certificates_referrals&amp;tab=certificates" aria-label="Open medical certificates list, <?php echo (int) $cert_total; ?> records">
                                        <div class="stat-header">
                                            <div>
                                                <div class="stat-value"><?php echo number_format((int) $cert_total); ?></div>
                                                <div class="stat-label">Medical Certificates</div>
                                            </div>
                                            <div class="stat-icon blue" aria-hidden="true">
                                                <i class="fas fa-file-medical"></i>
                                            </div>
                                        </div>
                                        <div class="stat-spark" aria-hidden="true"><span style="height:40%"></span><span style="height:55%"></span><span style="height:35%"></span><span style="height:70%"></span><span style="height:50%"></span><span style="height:80%"></span><span style="height:65%"></span></div>
                                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> Active caseload</span>
                                        <span class="stat-card-hint">View &amp; manage</span>
                                    </a>

                                    <a class="stat-card stat-card-link" href="?page=certificates_referrals&amp;tab=referrals" aria-label="Open referrals list, <?php echo (int) $ref_total; ?> records">
                                        <div class="stat-header">
                                            <div>
                                                <div class="stat-value"><?php echo number_format((int) $ref_total); ?></div>
                                                <div class="stat-label">Referrals</div>
                                            </div>
                                            <div class="stat-icon green" aria-hidden="true">
                                                <i class="fas fa-ambulance"></i>
                                            </div>
                                        </div>
                                        <div class="stat-spark" aria-hidden="true"><span style="height:30%"></span><span style="height:45%"></span><span style="height:60%"></span><span style="height:40%"></span><span style="height:75%"></span><span style="height:55%"></span><span style="height:90%"></span></div>
                                        <span class="stat-trend"><i class="fas fa-arrow-up"></i> Continuity of care</span>
                                        <span class="stat-card-hint">View &amp; manage</span>
                                    </a>

                                    <a class="stat-card stat-card-link" href="?page=health_dental_records&amp;records_tab=dental" aria-label="Open dental records, <?php echo (int) $dental_total; ?> records">
                                        <div class="stat-header">
                                            <div>
                                                <div class="stat-value"><?php echo number_format((int) $dental_total); ?></div>
                                                <div class="stat-label">Dental Patients</div>
                                            </div>
                                            <div class="stat-icon orange" aria-hidden="true">
                                                <i class="fas fa-tooth"></i>
                                            </div>
                                        </div>
                                        <div class="stat-spark" aria-hidden="true"><span style="height:50%"></span><span style="height:40%"></span><span style="height:65%"></span><span style="height:45%"></span><span style="height:70%"></span><span style="height:60%"></span><span style="height:85%"></span></div>
                                        <span class="stat-trend"><i class="fas fa-chart-bar"></i> Clinic volume</span>
                                        <span class="stat-card-hint">View &amp; manage</span>
                                    </a>

                                    <a class="stat-card stat-card-link" href="?page=health_dental_records&amp;records_tab=health" aria-label="Open health records, <?php echo (int) $health_total; ?> records">
                                        <div class="stat-header">
                                            <div>
                                                <div class="stat-value"><?php echo number_format((int) $health_total); ?></div>
                                                <div class="stat-label">Health Patients</div>
                                            </div>
                                            <div class="stat-icon red" aria-hidden="true">
                                                <i class="fas fa-heartbeat"></i>
                                            </div>
                                        </div>
                                        <div class="stat-spark" aria-hidden="true"><span style="height:35%"></span><span style="height:55%"></span><span style="height:45%"></span><span style="height:80%"></span><span style="height:50%"></span><span style="height:70%"></span><span style="height:95%"></span></div>
                                        <span class="stat-trend"><i class="fas fa-chart-bar"></i> Outpatient load</span>
                                        <span class="stat-card-hint">View &amp; manage</span>
                                    </a>

                                    <a class="stat-card stat-card-link" href="<?php echo htmlspecialchars($wpu_calendar_base); ?>" aria-label="Today's appointments, <?php echo (int) $dash_appt_today; ?>">
                                        <div class="stat-header">
                                            <div>
                                                <div class="stat-value"><?php echo number_format((int) $dash_appt_today); ?></div>
                                                <div class="stat-label">Today's Appointments</div>
                                            </div>
                                            <div class="stat-icon blue" aria-hidden="true">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                        </div>
                                        <span class="stat-trend"><i class="fas fa-clock"></i> <?php echo (int) $dash_appt_pending; ?> pending</span>
                                        <span class="stat-card-hint">View calendar</span>
                                    </a>

                                    <a class="stat-card stat-card-link" href="<?php echo htmlspecialchars($wpu_calendar_base.'/appointments?status=pending'); ?>" aria-label="Pending appointments, <?php echo (int) $dash_appt_pending; ?>">
                                        <div class="stat-header">
                                            <div>
                                                <div class="stat-value"><?php echo number_format((int) $dash_appt_pending); ?></div>
                                                <div class="stat-label">Pending Appointments</div>
                                            </div>
                                            <div class="stat-icon orange" aria-hidden="true">
                                                <i class="fas fa-hourglass-half"></i>
                                            </div>
                                        </div>
                                        <span class="stat-trend"><i class="fas fa-check"></i> <?php echo (int) $dash_appt_confirmed; ?> confirmed upcoming</span>
                                        <span class="stat-card-hint">Manage appointments</span>
                                    </a>
                                </div>

                                <h3 class="dashboard-section-title">Quick actions</h3>
                                <div class="quick-actions-grid" role="list">
                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-file-medical"></i></div>
                                            <div>
                                                <div class="dash-action__title">Create Certificate</div>
                                                <p class="dash-action__desc">Issue a medical certificate with exam details and print-ready output.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <button type="button" class="btn btn-primary" onclick="openCreateCertificateModal()">
                                                <i class="fas fa-plus"></i> Create certificate
                                            </button>
                                        </div>
                                    </div>

                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-ambulance"></i></div>
                                            <div>
                                                <div class="dash-action__title">Create Referral</div>
                                                <p class="dash-action__desc">Record a referral with destination facility and patient information.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <button type="button" class="btn btn-primary" onclick="openCreateReferralModal()">
                                                <i class="fas fa-plus"></i> Create referral
                                            </button>
                                        </div>
                                    </div>

                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-tooth"></i></div>
                                            <div>
                                                <div class="dash-action__title">Add Dental Record</div>
                                                <p class="dash-action__desc">Open dental records to add or update patient clinical files.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <a href="?page=health_dental_records&amp;records_tab=dental" class="btn btn-info">
                                                <i class="fas fa-notes-medical"></i> Open dental
                                            </a>
                                        </div>
                                    </div>

                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-heartbeat"></i></div>
                                            <div>
                                                <div class="dash-action__title">Add Health Record</div>
                                                <p class="dash-action__desc">Browse health module, search patients, and manage SOAP notes.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <a href="?page=health_dental_records&amp;records_tab=health" class="btn btn-info">
                                                <i class="fas fa-clipboard-list"></i> Open health
                                            </a>
                                        </div>
                                    </div>

                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-chart-bar"></i></div>
                                            <div>
                                                <div class="dash-action__title">Generate Reports</div>
                                                <p class="dash-action__desc">Print daily, monthly, and departmental clinical summaries.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <a href="?page=reports" class="btn btn-info">
                                                <i class="fas fa-chart-bar"></i> Open reports
                                            </a>
                                        </div>
                                    </div>

                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-user-shield"></i></div>
                                            <div>
                                                <div class="dash-action__title">Manage Users</div>
                                                <p class="dash-action__desc">Administer accounts, credentials, and station access controls.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <a href="?page=admin_management" class="btn btn-secondary">
                                                <i class="fas fa-users-cog"></i> User management
                                            </a>
                                        </div>
                                    </div>

                                    <div class="dash-action" role="listitem">
                                        <div class="dash-action__top">
                                            <div class="dash-action__icon" aria-hidden="true"><i class="fas fa-cog"></i></div>
                                            <div>
                                                <div class="dash-action__title">Settings</div>
                                                <p class="dash-action__desc">Clinic branding, signing physician, and auto-lock preferences.</p>
                                            </div>
                                        </div>
                                        <div class="dash-action__footer">
                                            <a href="?page=settings" class="btn btn-secondary">
                                                <i class="fas fa-cog"></i> Open settings
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <h3 class="dashboard-section-title">Operations &amp; insights</h3>
                                <div class="dash-widgets">
                                    <section class="dash-widget" aria-labelledby="dash-recent-certs-title">
                                        <div class="dash-widget__head">
                                            <h3 id="dash-recent-certs-title"><i class="fas fa-file-medical" aria-hidden="true"></i> Latest Certificates</h3>
                                            <a class="dash-widget__link" href="?page=certificates_referrals&amp;tab=certificates">View all</a>
                                        </div>
                                        <div class="dash-widget__body">
                                            <?php if (count($dash_recent_certs) === 0): ?>
                                                <div class="table-empty" style="padding:28px 16px!important;">
                                                    <p class="table-empty__title">No certificates yet</p>
                                                    <p class="table-empty__hint">Create the first medical certificate to populate this feed.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($dash_recent_certs as $rc): ?>
                                                    <div class="recent-row">
                                                        <div class="activity-ico" aria-hidden="true"><i class="fas fa-file-medical"></i></div>
                                                        <div class="meta">
                                                            <strong><?php echo htmlspecialchars($rc['name'] ?? 'Patient'); ?></strong>
                                                            <span><?php echo htmlspecialchars(($rc['mc_no'] ?? $rc['receipt_no'] ?? 'Certificate') . ' · ' . ($rc['created_at'] ?? $rc['examination_date'] ?? '')); ?></span>
                                                        </div>
                                                        <span class="status-pill">On file</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </section>

                                    <section class="dash-widget" aria-labelledby="dash-sys-title">
                                        <div class="dash-widget__head">
                                            <h3 id="dash-sys-title"><i class="fas fa-server" aria-hidden="true"></i> System Status</h3>
                                        </div>
                                        <div class="system-status-grid">
                                            <div class="sys-chip">
                                                <div class="k">Session</div>
                                                <div class="v">Authenticated</div>
                                            </div>
                                            <div class="sys-chip">
                                                <div class="k">Auto-lock</div>
                                                <div class="v"><?php echo $enabled ? 'Enabled' : 'Disabled'; ?></div>
                                            </div>
                                            <div class="sys-chip">
                                                <div class="k">Total records</div>
                                                <div class="v"><?php echo number_format($dash_total_records); ?></div>
                                            </div>
                                            <div class="sys-chip">
                                                <div class="k">Station</div>
                                                <div class="v">Secure</div>
                                            </div>
                                        </div>
                                        <div class="mini-calendar">
                                            <div class="day"><?php echo date('j'); ?></div>
                                            <div class="month"><?php echo htmlspecialchars(date('F Y')); ?></div>
                                            <p style="margin:8px 0 0;font-size:12px;color:var(--his-muted);"><?php echo (int) $dash_appt_today; ?> appointment(s) today</p>
                                            <a href="<?php echo htmlspecialchars($wpu_calendar_base); ?>" style="font-size:12px;margin-top:6px;display:inline-block;">View Calendar →</a>
                                        </div>
                                    </section>
                                </div>

                                <div class="dash-widgets">
                                    <section class="dash-widget" aria-labelledby="dash-recent-refs-title">
                                        <div class="dash-widget__head">
                                            <h3 id="dash-recent-refs-title"><i class="fas fa-ambulance" aria-hidden="true"></i> Recent Referrals</h3>
                                            <a class="dash-widget__link" href="?page=certificates_referrals&amp;tab=referrals">View all</a>
                                        </div>
                                        <div class="dash-widget__body">
                                            <?php if (count($dash_recent_refs) === 0): ?>
                                                <div class="table-empty" style="padding:28px 16px!important;">
                                                    <p class="table-empty__title">No referrals yet</p>
                                                    <p class="table-empty__hint">New referrals will appear here automatically.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($dash_recent_refs as $rr): ?>
                                                    <div class="recent-row">
                                                        <div class="activity-ico" aria-hidden="true"><i class="fas fa-ambulance"></i></div>
                                                        <div class="meta">
                                                            <strong><?php echo htmlspecialchars($rr['patient_name'] ?? 'Patient'); ?></strong>
                                                            <span><?php echo htmlspecialchars(($rr['hospital_clinic'] ?? 'Referral') . ' · ' . ($rr['created_at'] ?? '')); ?></span>
                                                        </div>
                                                        <span class="status-pill">Logged</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </section>

                                    <section class="dash-widget" aria-labelledby="dash-recent-patients-title">
                                        <div class="dash-widget__head">
                                            <h3 id="dash-recent-patients-title"><i class="fas fa-user-md" aria-hidden="true"></i> Recent Patients</h3>
                                            <a class="dash-widget__link" href="?page=health_dental_records&amp;records_tab=dental">Open records</a>
                                        </div>
                                        <div class="dash-widget__body">
                                            <?php if (count($dash_recent_patients) === 0): ?>
                                                <div class="table-empty" style="padding:28px 16px!important;">
                                                    <p class="table-empty__title">No patient records</p>
                                                    <p class="table-empty__hint">Dental and health visits will show up here.</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($dash_recent_patients as $rp): ?>
                                                    <div class="recent-row">
                                                        <div class="activity-ico" aria-hidden="true"><i class="fas fa-notes-medical"></i></div>
                                                        <div class="meta">
                                                            <strong><?php echo htmlspecialchars($rp['full_name'] ?? 'Patient'); ?></strong>
                                                            <span><?php echo htmlspecialchars(($rp['_module'] ?? 'Record') . ' · ' . ($rp['created_at'] ?? $rp['visit_date'] ?? '')); ?></span>
                                                        </div>
                                                        <span class="status-pill"><?php echo htmlspecialchars($rp['_module'] ?? 'Record'); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </section>
                                </div>

                                <h3 class="dashboard-section-title">Analytics placeholders</h3>
                                <div class="analytics-row">
                                    <section class="dash-widget">
                                        <div class="dash-widget__head">
                                            <h3><i class="fas fa-chart-bar" aria-hidden="true"></i> Monthly Certificates</h3>
                                        </div>
                                        <div class="chart-placeholder" aria-hidden="true">
                                            <span class="label">Trend visualization · coming online</span>
                                            <span class="bar" style="height:35%"></span>
                                            <span class="bar" style="height:48%"></span>
                                            <span class="bar" style="height:42%"></span>
                                            <span class="bar" style="height:68%"></span>
                                            <span class="bar" style="height:55%"></span>
                                            <span class="bar" style="height:78%"></span>
                                            <span class="bar" style="height:62%"></span>
                                            <span class="bar" style="height:88%"></span>
                                        </div>
                                    </section>
                                    <section class="dash-widget">
                                        <div class="dash-widget__head">
                                            <h3><i class="fas fa-chart-pie" aria-hidden="true"></i> Patient Distribution</h3>
                                        </div>
                                        <div class="chart-placeholder" aria-hidden="true">
                                            <span class="label">Dental <?php echo (int) $dental_total; ?> · Health <?php echo (int) $health_total; ?></span>
                                            <span class="bar" style="height:70%;background:linear-gradient(180deg,#F59E0B,#FBBF24)"></span>
                                            <span class="bar" style="height:55%;background:linear-gradient(180deg,#EF4444,#F87171)"></span>
                                            <span class="bar" style="height:40%"></span>
                                            <span class="bar" style="height:85%"></span>
                                            <span class="bar" style="height:60%"></span>
                                            <span class="bar" style="height:45%"></span>
                                        </div>
                                    </section>
                                </div>
                            </div>
