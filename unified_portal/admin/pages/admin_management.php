<?php
/**
 * Modular page: admin_management
 * Included by wpu_page_router.php — do not access directly.
 */
                            $admins_list = [];
                            $admins_stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
                            if ($admins_stmt) {
                                $admins_list = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
                            }
                            ?>
                            <div class="content-card">
                                <div class="card-header">
                                    <h2 class="card-title">
                                        <i class="fas fa-users-cog" aria-hidden="true"></i>
                                        Admin management
                                    </h2>
                                </div>
                                <div class="card-body">
                                    <div class="settings-page">
                                        <p class="settings-intro">
                                            <strong>Administrator accounts</strong> can sign in to this panel. Create accounts for trusted staff only. You cannot remove your own account while signed in; another admin must delete it if needed.
                                        </p>

                                        <section class="settings-panel" aria-labelledby="admin-add-heading">
                                            <header class="settings-panel__head">
                                                <div class="settings-panel__icon" aria-hidden="true"><i class="fas fa-user-plus"></i></div>
                                                <div class="settings-panel__titles">
                                                    <h3 id="admin-add-heading">Add administrator</h3>
                                                    <p class="settings-panel__lead">Choose a unique username and initial password. Ask the new admin to change their password after first login.</p>
                                                </div>
                                            </header>
                                            <div class="settings-panel__body">
                                                <form method="post" action="">
                                                    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="save_admin">
                                                    <div class="settings-form-grid-2">
                                                        <div class="form-group">
                                                            <label for="new_admin_username">Username</label>
                                                            <input type="text" class="form-control" id="new_admin_username" name="username" autocomplete="username" required maxlength="190" placeholder="e.g. clinic.admin">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="new_admin_email">Authorized email (optional)</label>
                                                            <input type="email" class="form-control" id="new_admin_email" name="email" autocomplete="email" maxlength="190" placeholder="hso@wpu.edu.ph">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="new_admin_role">Clinical role</label>
                                                            <select class="form-control" id="new_admin_role" name="role">
                                                                <option value="admin">Admin</option>
                                                                <option value="physician">Physician</option>
                                                                <option value="dentist">Dentist</option>
                                                                <option value="nurse">Nurse</option>
                                                                <option value="staff">Staff</option>
                                                                <option value="super_admin">Super admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="new_admin_password">Initial password</label>
                                                            <input type="password" class="form-control" id="new_admin_password" name="password" autocomplete="new-password" required placeholder="Strong password">
                                                        </div>
                                                    </div>
                                                    <span class="form-helper">Passwords are stored securely when the system uses hashing; avoid sharing credentials in plain text.</span>
                                                    <div class="settings-form-actions">
                                                        <button type="submit" name="add" class="btn btn-success">
                                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                                            Create admin account
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </section>

                                        <section class="settings-panel" aria-labelledby="admin-roster-heading">
                                            <header class="settings-panel__head">
                                                <div class="settings-panel__icon" aria-hidden="true"><i class="fas fa-users"></i></div>
                                                <div class="settings-panel__titles">
                                                    <h3 id="admin-roster-heading">Administrator accounts</h3>
                                                    <p class="settings-panel__lead"><?php
                                                        $admin_count = count($admins_list);
                                                        if ($admin_count === 0) {
                                                            echo 'No accounts yet. Create one above.';
                                                        } elseif ($admin_count === 1) {
                                                            echo '1 account has access.';
                                                        } else {
                                                            echo (int) $admin_count . ' accounts have access.';
                                                        }
                                                    ?></p>
                                                </div>
                                            </header>
                                            <div class="settings-panel__body">
                                                <?php if (empty($admins_list)): ?>
                                                    <p class="admin-roster__empty" role="status">No administrator accounts were found. Add one above.</p>
                                                <?php else: ?>
                                                <ul class="admin-roster">
                                                    <?php foreach ($admins_list as $admin): ?>
                                                    <li class="admin-roster__item">
                                                        <div class="admin-roster__main">
                                                            <span class="admin-roster__name"><?php echo htmlspecialchars($admin['username']); ?></span>
                                                            <?php
                                                            $created_raw = $admin['created_at'] ?? '';
                                                            if ($created_raw !== '' && $created_raw !== null):
                                                                $created_ts = strtotime((string) $created_raw);
                                                                if ($created_ts !== false):
                                                            ?>
                                                            <span class="admin-roster__meta">Added <?php echo htmlspecialchars(date('M j, Y', $created_ts)); ?></span>
                                                            <?php
                                                                endif;
                                                            endif;
                                                            ?>
                                                        </div>
                                                        <div class="admin-roster__actions">
                                                            <?php if ($admin['username'] !== $current_user): ?>
                                                                <button type="button" class="btn btn-danger btn-sm" onclick='deleteAdmin(<?php echo (int) $admin['id']; ?>, <?php echo json_encode((string) $admin['username'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                                    Remove
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="admin-roster__you"><i class="fas fa-user-md" aria-hidden="true"></i> You</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php endif; ?>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
