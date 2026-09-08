<?php
require_once __DIR__.'/../includes/wpu_security.php';
wpu_secure_session_start();
wpu_send_security_headers();

if (defined('WPU_LARAVEL_ADMIN_USER') && WPU_LARAVEL_ADMIN_USER !== '') {
    $_SESSION['admin_username'] = WPU_LARAVEL_ADMIN_USER;
    if (empty($_SESSION['login_time'])) {
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
    }
}

// Database connection - Using unified database
require_once '../config/database.php';
require_once '../includes/pdo_activity_log.php';
require_once '../includes/wpu_cache.php';
require_once '../includes/wpu_page_router.php';
$pdo = getDBConnection();

$wpu_public_portal_href = (defined('WPU_PORTAL_INDEX_URL') && WPU_PORTAL_INDEX_URL !== '')
    ? WPU_PORTAL_INDEX_URL
    : '/portal/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'login') {
    wpu_abort_if_invalid_csrf();
}

// Generate CSRF token for form protection
wpu_ensure_csrf_token();

// ===== HELPER FUNCTIONS =====
function calculateDuration($start, $end) {
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    $diff = $end_time - $start_time;
    $minutes = floor($diff / 60);
    $seconds = $diff % 60;
    return "{$minutes}m {$seconds}s";
}

function getLuminance($hexColor) {
    $hex = str_replace('#', '', $hexColor);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return ($r * 299 + $g * 587 + $b * 114) / 1000;
}

function generateRandomColor() {
    $hue = rand(0, 359);
    $saturation = rand(70, 100);
    $lightness = rand(45, 65);
    
    $c = (1 - abs(2 * $lightness / 100 - 1)) * $saturation / 100;
    $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
    $m = $lightness / 100 - $c / 2;
    
    if ($hue < 60) { $r = $c; $g = $x; $b = 0; }
    elseif ($hue < 120) { $r = $x; $g = $c; $b = 0; }
    elseif ($hue < 180) { $r = 0; $g = $c; $b = $x; }
    elseif ($hue < 240) { $r = 0; $g = $x; $b = $c; }
    elseif ($hue < 300) { $r = $x; $g = 0; $b = $c; }
    else { $r = $c; $g = 0; $b = $x; }
    
    $r = dechex(round(($r + $m) * 255));
    $g = dechex(round(($g + $m) * 255));
    $b = dechex(round(($b + $m) * 255));
    
    return '#' . str_pad($r, 2, '0', STR_PAD_LEFT) . str_pad($g, 2, '0', STR_PAD_LEFT) . str_pad($b, 2, '0', STR_PAD_LEFT);
}

function generateReceiptNumber($pdo) {
    $prefix = 'WPU-MC-';
    $year = date('Y');
    
    // Get the last receipt number for this year
    $stmt = $pdo->prepare("SELECT receipt_no FROM medical_certificates 
                          WHERE receipt_no LIKE ? 
                          ORDER BY id DESC LIMIT 1");
    $likePattern = $prefix . $year . '%';
    $stmt->execute([$likePattern]);
    $lastReceipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastReceipt && preg_match('/' . $prefix . $year . '-(\d+)/', $lastReceipt['receipt_no'], $matches)) {
        $nextNumber = intval($matches[1]) + 1;
    } else {
        $nextNumber = 1;
    }
    
    return $prefix . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
}
// ===== END HELPER FUNCTIONS =====

// Pagination settings
$items_per_page = 10;
/** Certificates & referrals combined page: fixed page size */
$cert_ref_items_per_page = 10;
/** Health & dental records combined page: fixed page size */
$health_dental_items_per_page = 10;

// Check if user is logged in
$logged_in = isset($_SESSION['admin_username']);
$current_user = $_SESSION['admin_username'] ?? '';

/* --------------------- LOGIN --------------------- */
if ((! defined('WPU_LARAVEL_BRIDGE') || ! WPU_LARAVEL_BRIDGE)
    && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    if (! wpu_rate_limit('legacy_admin_login', 8, 60)) {
        $login_error = 'Too many login attempts. Please wait and try again.';
    } elseif (! wpu_verify_csrf_request(false)) {
        wpu_security_audit($pdo, 'csrf_failure', 'legacy login');
        $login_error = 'Invalid security token. Refresh the page and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $login_error = 'Invalid username or password.';
            wpu_record_login_attempt($pdo, $username ?: 'unknown', false);
        } elseif (wpu_is_account_locked($pdo, $username)) {
            $login_error = 'Account temporarily locked due to too many failed attempts. Try again later.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && wpu_verify_admin_password($pdo, $admin, $password)) {
                wpu_record_login_attempt($pdo, $username, true);
                session_regenerate_id(true);
                $_SESSION['admin_username'] = $username;
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
                $_SESSION['_wpu_session_created'] = time();
                $_SESSION['_wpu_session_fingerprint'] = wpu_session_fingerprint();
                wpu_rotate_csrf_token();

                $login_time = $_SESSION['login_time'];
                $stmt = $pdo->prepare('INSERT INTO user_logs (username, activity_type, login_time, status)
                                       VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, 'login', $login_time, 'Logged In']);
                wpu_security_audit($pdo, 'login_success', 'Legacy admin login');

                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
            }

            wpu_record_login_attempt($pdo, $username, false);
            $login_error = 'Invalid username or password.';
        }
    }
}

/* --------------------- LOGOUT --------------------- */
/* --------------------- LOGOUT --------------------- */
if (isset($_GET['logout'])) {
    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && defined('WPU_LARAVEL_LOGOUT_URL')) {
        header('Location: '.WPU_LARAVEL_LOGOUT_URL);
        exit;
    }

    $username = $_SESSION['admin_username'] ?? '';
    if ($username) {
        $logout_time = date('Y-m-d H:i:s');
        $login_time = $_SESSION['login_time'] ?? $logout_time;
        $duration = calculateDuration($login_time, $logout_time);

        $stmt = $pdo->prepare("UPDATE user_logs 
                               SET logout_time = ?, duration = ?, status = 'Logged Out'
                               WHERE username = ? AND activity_type = 'login' AND logout_time IS NULL
                               ORDER BY login_time DESC LIMIT 1");
        $stmt->execute([$logout_time, $duration, $username]);
        wpu_insert_activity_log($pdo, $username, 'Logout', 'Session duration: '.$duration);
    }

    session_destroy();
    header('Location: ?page=login');
    exit;
}

/* --------------------- LOCK --------------------- */
$lock_requested = isset($_GET['lock'])
    || (isset($_GET['action']) && $_GET['action'] === 'lock');

if ($lock_requested && $logged_in) {
    $_SESSION['locked'] = true;
    $_SESSION['lock_time'] = date('Y-m-d H:i:s');
    header('Location: '.wpu_admin_url());
    exit;
}

/* --------------------- UNLOCK --------------------- */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'unlock') {
    $password = $_POST['unlock_password'] ?? '';

    if (!isset($_SESSION['admin_username']) || empty($_SESSION['admin_username'])) {
        $unlock_error = "Session expired. Please log in again.";
    } else {
        $current_user = $_SESSION['admin_username'];

        $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->execute([$current_user]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && wpu_verify_admin_password($pdo, $admin, $password)) {
            unset($_SESSION['locked']);
            unset($_SESSION['lock_time']);
            session_regenerate_id(true);
            wpu_rotate_csrf_token();
            header('Location: '.wpu_admin_url(['page' => 'dashboard']));
            exit;
        } else {
            $unlock_error = "Invalid password.";
        }
    }
}

/* --------------------- LOCKED SESSION — clean URL --------------------- */
if ($logged_in && isset($_SESSION['locked']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $needs_lock_url = isset($_GET['page'])
        || isset($_GET['lock'])
        || (isset($_GET['action']) && $_GET['action'] === 'lock');

    if ($needs_lock_url) {
        header('Location: '.wpu_admin_url());
        exit;
    }
}

/* --------------------- SAVE AUTOLOCK SETTINGS --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_autolock' && $logged_in) {
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $timeout = (int)$_POST['timeout'] * 60000;

    $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value)
                   VALUES ('auto_lock_enabled', :v_ins)
                   ON DUPLICATE KEY UPDATE setting_value = :v_upd")
        ->execute(['v_ins' => $enabled, 'v_upd' => $enabled]);

    $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value)
                   VALUES ('auto_lock_timeout', :v_ins)
                   ON DUPLICATE KEY UPDATE setting_value = :v_upd")
        ->execute(['v_ins' => $timeout, 'v_upd' => $timeout]);

    wpu_cache_forget('settings:auto_lock');
    $success_message = "Auto-lock settings saved successfully!";
    wpu_insert_activity_log(
        $pdo,
        $current_user,
        'Auto-lock settings updated',
        'enabled='.(string) $enabled.', timeout_ms='.(string) $timeout
    );
}

/* --------------------- CHANGE PASSWORD --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password' && $logged_in) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :u");
    $stmt->execute(['u' => $_SESSION['admin_username']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !wpu_verify_admin_password($pdo, $admin, $current)) {
        $error_message = "Current password incorrect";
    } elseif ($policyError = wpu_validate_password_policy($new)) {
        $error_message = $policyError;
    } elseif (!wpu_check_password_history($pdo, (int) $admin['id'], $new)) {
        $error_message = "Cannot reuse a recent password";
    } elseif ($new !== $confirm) {
        $error_message = "Passwords do not match";
    } else {
        wpu_store_password_history($pdo, (int) $admin['id'], (string) $admin['password']);
        $newHash = wpu_hash_new_password($new);
        $stmt = $pdo->prepare("UPDATE admins SET password = :p, password_changed_at = NOW() WHERE username = :u");
        $stmt->execute([
            'p' => $newHash,
            'u' => $_SESSION['admin_username']
        ]);
        wpu_rotate_csrf_token();
        $success_message = "Password updated successfully";
        wpu_insert_activity_log($pdo, $_SESSION['admin_username'], 'Password changed', 'Own account');
        wpu_security_audit($pdo, 'password_changed', 'Self-service password change');
    }
}

/* --------------------- ADMIN MANAGEMENT --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_admin' && $logged_in) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error_message = "Username already exists";
        } elseif ($policyError = wpu_validate_password_policy($password)) {
            $error_message = $policyError;
        } else {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, password_changed_at) VALUES (?, ?, NOW())");
            $stmt->execute([$username, wpu_hash_new_password($password)]);
            $success_message = "Admin added successfully";
            wpu_insert_activity_log($pdo, $current_user, 'Admin account created', 'New username: '.$username);
            wpu_security_audit($pdo, 'admin_created', 'Username: '.$username);
        }
    } elseif (isset($_POST['update'])) {
        if ($policyError = wpu_validate_password_policy($password)) {
            $error_message = $policyError;
        } else {
            $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = ?");
            $stmt->execute([$current_user]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing && !wpu_check_password_history($pdo, (int) $existing['id'], $password)) {
                $error_message = "Cannot reuse a recent password";
            } else {
                if ($existing) {
                    wpu_store_password_history($pdo, (int) $existing['id'], (string) $existing['password']);
                }
                $stmt = $pdo->prepare("UPDATE admins SET password = ?, password_changed_at = NOW() WHERE username = ?");
                $stmt->execute([wpu_hash_new_password($password), $current_user]);
                $success_message = "Admin updated successfully";
                wpu_insert_activity_log($pdo, $current_user, 'Admin password updated', 'Own account');
            }
        }
    }
}

/* --------------------- DELETE ADMIN --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_admin' && $logged_in) {
    $admin_id = $_POST['admin_id'] ?? '';
    if ($admin_id) {
        $stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $admin['username'] !== $current_user) {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $success_message = "Admin deleted successfully";
            wpu_insert_activity_log($pdo, $current_user, 'Admin account removed', 'Removed: '.$admin['username']);
        } else {
            $error_message = "Cannot delete your own account";
        }
    }
}

/* --------------------- DELETE DENTAL RECORD --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_dental_record' && $logged_in) {
    $record_id = $_POST['record_id'] ?? '';
    if ($record_id) {
        // Verify it's a dental record before deleting
        $stmt = $pdo->prepare("SELECT module_type FROM patient_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record && $record['module_type'] === 'dental') {
            // Delete associated files first
            $fileStmt = $pdo->prepare("DELETE FROM patient_files WHERE patient_record_id = ?");
            $fileStmt->execute([$record_id]);
            
            // Delete the record
            $stmt = $pdo->prepare("DELETE FROM patient_records WHERE id = ?");
            $stmt->execute([$record_id]);
            $success_message = "Dental record deleted successfully";
            wpu_insert_activity_log($pdo, $current_user, 'Dental record deleted', 'Record ID: '.$record_id);
            header('Location: ?page=health_dental_records&records_tab=dental');
            exit;
        } else {
            $error_message = "Record not found or invalid";
        }
    }
}

/* --------------------- DELETE HEALTH RECORD --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_health_record' && $logged_in) {
    $record_id = $_POST['record_id'] ?? '';
    if ($record_id) {
        // Verify it's a health record before deleting
        $stmt = $pdo->prepare("SELECT module_type FROM patient_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record && $record['module_type'] === 'health') {
            // Delete associated files first
            $fileStmt = $pdo->prepare("DELETE FROM patient_files WHERE patient_record_id = ?");
            $fileStmt->execute([$record_id]);
            
            // Delete the record
            $stmt = $pdo->prepare("DELETE FROM patient_records WHERE id = ?");
            $stmt->execute([$record_id]);
            $success_message = "Health record deleted successfully";
            wpu_insert_activity_log($pdo, $current_user, 'Health record deleted', 'Record ID: '.$record_id);
            header('Location: ?page=health_dental_records&records_tab=health');
            exit;
        } else {
            $error_message = "Record not found or invalid";
        }
    }
}

/* --------------------- FILE UPLOAD HANDLER --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file' && $logged_in) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $patient_record_id = $_POST['patient_record_id'] ?? '';
        
        if (!$patient_record_id) {
            $response['message'] = 'Invalid patient record ID';
            echo json_encode($response);
            exit;
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $validation = wpu_validate_uploaded_file($_FILES['file'], $max_size);
        if (!$validation['ok']) {
            $response['message'] = $validation['message'];
        } else {
            $file_name = wpu_random_upload_filename($validation['extension']);
            $display_name = basename((string) $_FILES['file']['name']);
            $file_tmp = $_FILES['file']['tmp_name'];
            $file_size = (int) $_FILES['file']['size'];
            $file_type = $validation['mime'];
            // Read file content
            $file_content = file_get_contents($file_tmp);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO patient_files (patient_record_id, file_name, file_type, file_size, file_content, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$patient_record_id, $file_name, $file_type, $file_size, $file_content, $_SESSION['admin_username']]);
                wpu_insert_activity_log(
                    $pdo,
                    (string) ($_SESSION['admin_username'] ?? ''),
                    'Patient file uploaded',
                    'Record ID: '.$patient_record_id.', file: '.$display_name
                );
                $response['success'] = true;
                $response['message'] = 'File uploaded successfully!';
            } catch (PDOException $e) {
                error_log('[WPU Upload] '.$e->getMessage());
                $response['message'] = 'Upload failed. Please try again.';
            }
        }
    } else {
        $response['message'] = 'No file selected or invalid request';
    }
    
    echo json_encode($response);
    exit;
}

/* --------------------- FILE VIEW HANDLER --------------------- */
if (isset($_GET['action']) && $_GET['action'] === 'view_file' && isset($_GET['file_id']) && $logged_in) {
    $file_id = $_GET['file_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT file_name, file_type, file_content FROM patient_files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            header('Content-Type: ' . $file['file_type']);
            header('Content-Disposition: inline; filename="'.wpu_safe_download_filename((string) $file['file_name']).'"');
            header('Content-Length: ' . strlen($file['file_content']));
            echo $file['file_content'];
            exit;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found';
            exit;
        }
    } catch (PDOException $e) {
        error_log('[WPU File View] '.$e->getMessage());
        header('HTTP/1.0 500 Internal Server Error');
        echo 'Unable to retrieve file';
        exit;
    }
}

/* --------------------- FILE DELETE HANDLER --------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file' && $logged_in) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    $file_id = $_POST['file_id'] ?? '';
    
    if ($file_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM patient_files WHERE id = ?");
            $stmt->execute([$file_id]);
            wpu_insert_activity_log(
                $pdo,
                (string) ($_SESSION['admin_username'] ?? ''),
                'Patient file deleted',
                'File ID: '.$file_id
            );
            $response['success'] = true;
            $response['message'] = 'File deleted successfully!';
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid file ID';
    }
    
    echo json_encode($response);
    exit;
}

/* --------------------- PAGE CONTROL --------------------- */
$page = $_GET['page'] ?? 'dashboard';
if (!$logged_in && $page !== 'login') {
    $page = 'login';
}

// Page-aware data loading (cached reference data, queries only when logged in + per page)
require_once '../includes/wpu_page_data.php';

if (! isset($error_message) && isset($GLOBALS['error_message'])) {
    $error_message = $GLOBALS['error_message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $wpu_csrf_meta = function_exists('csrf_token') ? (string) csrf_token() : wpu_ensure_csrf_token();
    ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($wpu_csrf_meta, ENT_QUOTES, 'UTF-8'); ?>">
    <script>
    (function () {
        var m = document.querySelector('meta[name="csrf-token"]');
        var token = m && m.getAttribute('content');
        if (!token) return;
        var origFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var h = new Headers(init.headers || {});
            if (!h.has('Accept')) {
                h.set('Accept', 'application/json');
            }
            if (!h.has('X-Requested-With')) {
                h.set('X-Requested-With', 'XMLHttpRequest');
            }
            var method = String(init.method || 'GET').toUpperCase();
            if (method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS' && method !== 'TRACE') {
                if (!h.has('X-CSRF-TOKEN')) {
                    h.set('X-CSRF-TOKEN', token);
                }
            }
            init.headers = h;
            return origFetch.call(this, input, init);
        };
    })();
    </script>
    <title>WPU Medical - Admin Panel</title>
    <?php
    // Absolute asset base: serve from unified_portal/assets via Apache (not Laravel /portal/assets).
    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('url')) {
        $his_assets = rtrim(url('/unified_portal/assets'), '/');
    } else {
        $his_assets = '../assets';
    }
    $his_core_css_v = @filemtime(__DIR__.'/../assets/css/admin-core.css') ?: time();
    $his_ui_css_v = @filemtime(__DIR__.'/../assets/css/admin-his.css') ?: time();
    $his_ui_js_v = @filemtime(__DIR__.'/../assets/js/admin-his-ui.js') ?: time();
    $his_ajax_js_v = @filemtime(__DIR__.'/../assets/js/wpu-ajax.js') ?: time();
    $his_core_js_v = @filemtime(__DIR__.'/../assets/js/admin-core.js') ?: time();
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script>
    (function () {
        try {
            var t = localStorage.getItem('wpu_his_theme');
            if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
            if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        } catch (e) {}
    })();
    </script>
    <link rel="preload" href="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/css/admin-core.css?v=<?php echo (int) $his_core_css_v; ?>" as="style">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/css/admin-core.css?v=<?php echo (int) $his_core_css_v; ?>">
<?php if (in_array($page, ['settings','admin_management','user_logs'])): ?>
<style>
/* Compact mode overrides for Settings, Admin Management, and User logs */
.header { padding: 18px 22px; }
.header h1 { font-size: 22px; }
.container { padding: 16px; }
.content { padding: 20px; }
.content-card { margin-bottom: 18px; }
.card-header { padding: 16px 20px; }
.card-body { padding: 16px 20px; }
.settings-section { padding: 16px; }
.settings-section h3 { font-size: 16px; margin-bottom: 12px; }
.settings-page { gap: 16px; }
.settings-intro { padding: 14px 16px; font-size: 13px; }
.settings-panel__head { padding: 14px 16px; }
.settings-panel__body { padding: 14px 16px; gap: 14px; }
.settings-panel__titles h3 { font-size: 1rem; }
.settings-panel__icon { width: 44px; height: 44px; font-size: 1.2rem; }
.form-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
.settings-form-grid-2 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.logs-page { gap: 14px; }
.form-group { margin-bottom: 12px; }
label { font-size: 13px; margin-bottom: 6px; }
input[type="text"],
input[type="password"],
input[type="email"],
input[type="date"],
input[type="number"],
select,
textarea { padding: 10px 12px; font-size: 13px; }
table th { padding: 12px 14px; font-size: 13px; }
table td { padding: 10px 14px; font-size: 13px; }
.btn { padding: 8px 14px; font-size: 13px; }
.btn-sm { padding: 6px 10px; font-size: 12px; }
.pagination { padding: 14px 20px; }
</style>
<?php endif; ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/css/admin-his.css?v=<?php echo (int) $his_ui_css_v; ?>">
<style id="his-critical-overrides">
/* Ensure Font Awesome wins over the global * font-family reset */
.fa, .fas, .far, .fal, .fat, .fab, .fa-solid, .fa-regular, .fa-brands,
.fa::before, .fas::before, .far::before, .fal::before, .fab::before,
.fa-solid::before, .fa-regular::before, .fa-brands::before,
i.fas, i.far, i.fab, i.fa {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    font-style: normal !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    display: inline-block;
    line-height: 1;
}
.fab, .fa-brands, .fab::before, .fa-brands::before {
    font-family: "Font Awesome 6 Brands" !important;
    font-weight: 400 !important;
}
.far, .fa-regular, .far::before, .fa-regular::before {
    font-weight: 400 !important;
}
/* Force enterprise topbar on all desktop widths */
.admin-topbar.no-print,
header.admin-topbar {
    display: flex !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    height: 64px !important;
    min-height: 64px !important;
    max-height: 64px !important;
    overflow: visible !important;
    padding: 0 16px !important;
    gap: 10px !important;
}
.admin-topbar .topbar-actions {
    flex-shrink: 0;
}
.admin-topbar .topbar-search {
    min-width: 0;
}
@media (max-width: 992px) {
    .admin-topbar.no-print,
    header.admin-topbar {
        height: auto !important;
        max-height: none !important;
        min-height: 56px !important;
        flex-wrap: wrap !important;
        padding: 10px 12px !important;
    }
}
</style>
</head>
<body>
    <?php if (!$logged_in): ?>
        <!-- Login Screen -->
        <div class="login-screen">
            <form method="POST" class="login-form" autocomplete="on">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="login-brand">
                    <img src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/images/logo.png" alt="WPU Medical">
                    <div class="sub">Hospital Information System</div>
                </div>
                <h1 class="medical-header">Secure Admin Sign-in</h1>
                <p style="text-align:center;color:var(--his-muted,#64748B);font-size:14px;margin:-8px 0 24px;">WPU Health Services · Authorized personnel only</p>
                
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-shield-alt alert-icon" aria-hidden="true"></i>
                        <div class="alert-content">
                            <div class="alert-title">Login Failed</div>
                            <div class="alert-message"><?php echo htmlspecialchars($login_error); ?></div>
                        </div>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Dismiss">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username" autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Sign in</button>
                <a href="<?php echo htmlspecialchars($wpu_public_portal_href); ?>" class="btn-back">
                    <i class="fas fa-home"></i> Home Page
                </a>
            </form>
        </div>
    <?php elseif (isset($_SESSION['locked'])): ?>
        <!-- Lock Screen -->
        <div class="lock-screen">
            <form method="POST" class="lock-form" autocomplete="current-password">
                <input type="hidden" name="action" value="unlock">
                <input type="hidden" name="form_token" value="<?php echo htmlspecialchars(wpu_ensure_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="login-brand" style="margin-bottom:18px;">
                    <img src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/images/logo.png" alt="WPU Medical">
                </div>
                <h2><i class="fas fa-lock" aria-hidden="true"></i> Session Locked</h2>
                <p style="margin-bottom: 20px;">Signed in as <strong><?php echo htmlspecialchars($current_user); ?></strong>. Enter your password to continue.</p>
                
                <?php if (isset($unlock_error) || isset($error_message)): ?>
                    <div class="alert alert-error">
                        <div class="alert-content">
                            <div class="alert-title">Unlock Failed</div>
                            <div class="alert-message"><?php echo htmlspecialchars($unlock_error ?? $error_message); ?></div>
                        </div>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Dismiss">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="unlock_password">Password</label>
                    <input type="password" id="unlock_password" name="unlock_password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-unlock" aria-hidden="true"></i> Unlock workstation</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Main Application -->
        <div class="sidebar no-print" id="sidebar" role="navigation" aria-label="Main navigation">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/images/logo.png" alt="WPU Medical Logo">
                </div>
                <div class="sidebar-brand">
                    <h2>WPU Health</h2>
                    <p>Enterprise HIS</p>
                </div>
            </div>
            
            <nav class="sidebar-menu sidebar-nav" aria-label="Admin modules">
                <div class="sidebar-section-label">Overview</div>
                <a href="?page=dashboard" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                    <span class="nav-link-text">Dashboard</span>
                </a>

                <div class="sidebar-section-label">Clinical</div>
                <a href="?page=certificates_referrals&tab=certificates" class="nav-link <?php echo ($page === 'certificates' || $page === 'referrals' || $page === 'certificates_referrals') ? 'active' : ''; ?>">
                    <i class="fas fa-file-medical" aria-hidden="true"></i>
                    <span class="nav-link-text">Certificates &amp; Referrals</span>
                </a>
                <a href="?page=health_dental_records&records_tab=dental" class="nav-link <?php echo ($page === 'dental_records' || $page === 'health_records' || $page === 'health_dental_records' || $page === 'view_record') ? 'active' : ''; ?>">
                    <i class="fas fa-notes-medical" aria-hidden="true"></i>
                    <span class="nav-link-text">Health &amp; Dental</span>
                </a>
                <?php
                $wpu_calendar_base = (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('url'))
                    ? url('/admin/calendar')
                    : '/admin/calendar';
                ?>
                <div class="sidebar-section-label">Calendar</div>
                <a href="<?php echo htmlspecialchars($wpu_calendar_base); ?>" class="nav-link">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i>
                    <span class="nav-link-text">Calendar</span>
                </a>
                <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/appointments'); ?>" class="nav-link">
                    <i class="fas fa-list" aria-hidden="true"></i>
                    <span class="nav-link-text">Appointments</span>
                </a>
                <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/physicians'); ?>" class="nav-link">
                    <i class="fas fa-user-md" aria-hidden="true"></i>
                    <span class="nav-link-text">Physicians</span>
                </a>
                <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/reports'); ?>" class="nav-link">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <span class="nav-link-text">Appointment Reports</span>
                </a>
                <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/portal-users'); ?>" class="nav-link">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <span class="nav-link-text">Portal Users</span>
                </a>
                <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/settings'); ?>" class="nav-link">
                    <i class="fas fa-sliders-h" aria-hidden="true"></i>
                    <span class="nav-link-text">Appointment Settings</span>
                </a>
                <a href="?page=reports" class="nav-link <?php echo $page === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar" aria-hidden="true"></i>
                    <span class="nav-link-text">Reports</span>
                </a>

                <div class="sidebar-section-label">Administration</div>
                <a href="?page=settings" class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog" aria-hidden="true"></i>
                    <span class="nav-link-text">Settings</span>
                </a>
                <a href="?page=admin_management" class="nav-link <?php echo $page === 'admin_management' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                    <span class="nav-link-text">User Management</span>
                </a>
                <a href="?page=user_logs" class="nav-link <?php echo $page === 'user_logs' ? 'active' : ''; ?>">
                    <i class="fas fa-history" aria-hidden="true"></i>
                    <span class="nav-link-text">Activity Logs</span>
                </a>
                <a href="?page=backup" class="nav-link <?php echo $page === 'backup' ? 'active' : ''; ?>">
                    <i class="fas fa-database" aria-hidden="true"></i>
                    <span class="nav-link-text">Backup</span>
                </a>
            </nav>
            
            <div class="user-info">
                <div class="user-avatar" aria-hidden="true">
                    <?php echo strtoupper(substr($current_user, 0, 2)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($current_user); ?></div>
                    <div class="user-actions">
                        <a href="?lock" class="user-action-btn lock">
                            <i class="fas fa-lock"></i> Lock
                        </a>
                        <a href="#" class="user-action-btn logout" onclick="confirmLogout(event)">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="sidebar-backdrop no-print" id="sidebar-backdrop" onclick="toggleSidebar()" aria-hidden="true"></div>

        <div class="main-content">
            <header class="admin-topbar no-print" role="banner">
                <button type="button" class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Open or close navigation menu" aria-expanded="false" aria-controls="sidebar">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <span class="admin-topbar-title">WPU Health Services</span>

                <div class="topbar-search">
                    <i class="fas fa-search search-ico" aria-hidden="true"></i>
                    <label for="his-global-search" class="visually-hidden">Global search</label>
                    <input type="search" id="his-global-search" placeholder="Search modules, actions…" autocomplete="off">
                    <span class="kbd" aria-hidden="true">Ctrl K</span>
                </div>

                <div class="topbar-actions">
                    <div class="topbar-meta" aria-live="polite">
                        <span class="date"><?php echo htmlspecialchars(date('D, M j, Y')); ?></span>
                        <span class="time" data-his-clock><?php echo htmlspecialchars(date('H:i:s')); ?></span>
                    </div>

                    <button type="button" class="icon-btn" onclick="typeof hisOpenCommandPalette==='function'&&hisOpenCommandPalette()" title="Quick actions (Ctrl+K)" aria-label="Quick actions">
                        <i class="fas fa-bolt" aria-hidden="true"></i>
                    </button>

                    <button type="button" class="icon-btn" onclick="typeof hisToggleTheme==='function'&&hisToggleTheme()" title="Toggle theme" aria-label="Toggle light or dark theme">
                        <i class="fas fa-moon" data-his-theme-icon aria-hidden="true"></i>
                    </button>

                    <div class="topbar-profile" data-his-dropdown style="position:relative;">
                        <button type="button" class="icon-btn" onclick="typeof hisToggleNotif==='function'&&hisToggleNotif(event)" title="Notifications" aria-label="Notifications" aria-haspopup="true">
                            <i class="fas fa-bell" aria-hidden="true"></i>
                            <span class="badge-dot" aria-hidden="true"></span>
                        </button>
                        <div class="notif-panel" id="his-notif-panel" data-his-dropdown-menu role="menu" aria-label="Notifications">
                            <div class="notif-panel__head">
                                <span>Notifications</span>
                                <span style="font-size:11px;color:var(--his-muted);font-weight:600;">Station</span>
                            </div>
                            <div class="notif-item">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <div>
                                    <strong>Auto-lock <?php echo $enabled ? 'enabled' : 'disabled'; ?></strong>
                                    <small>Protects this workstation when idle</small>
                                </div>
                            </div>
                            <div class="notif-item">
                                <i class="fas fa-database" aria-hidden="true"></i>
                                <div>
                                    <strong><?php echo number_format((int) $cert_total + (int) $ref_total + (int) $dental_total + (int) $health_total); ?> records on file</strong>
                                    <small>Certificates, referrals, and patient records</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="topbar-profile" data-his-dropdown>
                        <button type="button" class="topbar-profile-btn" data-his-dropdown-btn aria-haspopup="true" aria-expanded="false">
                            <span class="av"><?php echo strtoupper(substr($current_user, 0, 2)); ?></span>
                            <span class="name"><?php echo htmlspecialchars($current_user); ?></span>
                            <i class="fas fa-chevron-down" style="font-size:10px;opacity:.6;" aria-hidden="true"></i>
                        </button>
                        <div class="topbar-dropdown" data-his-dropdown-menu role="menu">
                            <a href="?page=settings" role="menuitem"><i class="fas fa-cog" aria-hidden="true"></i> Settings</a>
                            <a href="?page=admin_management" role="menuitem"><i class="fas fa-user-shield" aria-hidden="true"></i> User management</a>
                            <a href="?lock" role="menuitem"><i class="fas fa-lock" aria-hidden="true"></i> Lock session</a>
                            <div class="sep" role="separator"></div>
                            <a href="#" role="menuitem" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            <div class="container">
                <!-- Alert Container for PHP Messages -->
                <div class="alert-container">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success" id="success-alert">
                            <i class="fas fa-check-circle alert-icon"></i>
                            <div class="alert-content">
                                <div class="alert-title">Success</div>
                                <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Dismiss">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-error" id="error-alert">
                            <i class="fas fa-circle-exclamation alert-icon" aria-hidden="true"></i>
                            <div class="alert-content">
                                <div class="alert-title">Error</div>
                                <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Dismiss">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                $his_page_title = 'Unified Admin Panel';
                switch ($page) {
                    case 'dashboard': $his_page_title = 'Dashboard'; break;
                    case 'certificates':
                    case 'referrals':
                    case 'certificates_referrals': $his_page_title = 'Certificates & Referrals'; break;
                    case 'dental_records':
                    case 'health_records':
                    case 'health_dental_records': $his_page_title = 'Health & Dental Records'; break;
                    case 'view_record': $his_page_title = 'View Record'; break;
                    case 'reports': $his_page_title = 'Reports'; break;
                    case 'settings': $his_page_title = 'System Settings'; break;
                    case 'admin_management': $his_page_title = 'Admin Management'; break;
                    case 'user_logs': $his_page_title = 'User Logs'; break;
                    case 'backup': $his_page_title = 'Backup & Export'; break;
                }
                ?>
                <div class="header">
                    <div class="header-titles">
                        <nav class="breadcrumbs" aria-label="Breadcrumb">
                            <a href="?page=dashboard">Home</a>
                            <span class="sep" aria-hidden="true">/</span>
                            <span aria-current="page"><?php echo htmlspecialchars($his_page_title); ?></span>
                        </nav>
                        <h1 class="medical-header"><?php echo htmlspecialchars($his_page_title); ?></h1>
                        <p class="page-header-meta"><?php echo htmlspecialchars(date('l, F j, Y')); ?> · Signed in as <strong><?php echo htmlspecialchars($current_user); ?></strong></p>
                    </div>
                    <div class="auto-lock-status <?php echo $enabled ? 'active' : 'inactive'; ?>" title="Auto-lock helps protect this station when you step away">
                        <span class="status-indicator" aria-hidden="true"></span>
                        Auto-lock: <?php echo $enabled ? 'ON' : 'OFF'; ?>
                    </div>
                </div>

                <div class="content">
                    <?php
                    if (wpu_admin_page_exists($page)) {
                        wpu_render_admin_page($page, get_defined_vars());
                    } else {
                        echo '<div class="alert alert-error">Page not found.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- All modals remain the same as in the original code -->
        <div id="historyRecordSnapshotModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="historyRecordSnapshotTitle">
            <div class="modal-dialog" style="max-width: 720px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="historyRecordSnapshotTitle">Visit snapshot</h2>
                        <button type="button" class="close" onclick="closeModal('historyRecordSnapshotModal')" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body" id="historyRecordSnapshotBody">
                        <p style="color: var(--gray-600); margin: 0;">Loading…</p>
                    </div>
                    <div class="modal-footer">
                        <a id="historyRecordSnapshotOpenFull" href="#" class="btn btn-primary">Open full record</a>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('historyRecordSnapshotModal')">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Create Certificate Modal -->
        <div id="createCertificateModal" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Create Medical Certificate</h2>
                        <button class="close" onclick="closeModal('createCertificateModal')">&times;</button>
                    </div>
                    <form id="createCertificateForm" method="POST">
                        <div class="modal-body">
                            <div class="form-section-header">Personal Information</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" required placeholder="Enter full name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age *</label>
                                    <input type="number" class="form-control" name="age" required placeholder="Enter age" min="0" max="150">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-control" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Civil Status *</label>
                                    <select class="form-control" name="civil_status" required>
                                        <option value="">Select Status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Complete Address *</label>
                                    <textarea class="form-control" name="address" required placeholder="Enter complete address" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-section-header">Medical Examination</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Date of Examination *</label>
                                    <input type="date" class="form-control" name="date" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Reason for Examination</label>
                                    <input type="text" class="form-control" name="reason" placeholder="Enter reason (optional)">
                                </div>
                            </div>

                            <div class="form-section-header">Medical Findings *</div>
                            <div class="form-group full-width">
                                <label class="form-label">Assessment Results (Select at least one) *</label>
                                <div style="margin-bottom: 12px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" id="cert_fit" name="findings[]" value="fit">
                                        <span>Physically and mentally fit</span>
                                    </label>
                                </div>
                                <div>
                                    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" id="cert_impression" name="findings[]" value="impression">
                                        <span>With the impression of:</span>
                                    </label>
                                    <textarea class="form-control" name="impression_text" id="impression_text" 
                                        placeholder="Enter medical impression details" style="margin-top: 8px;" disabled></textarea>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">Medical Advice</label>
                                <textarea class="form-control" name="advice" placeholder="Enter medical advice and recommendations (optional)" rows="3"></textarea>
                            </div>

                           <div class="form-section-header">Administrative Details</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Date Issued *</label>
                                    <input type="date" class="form-control" name="date_issued" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Receipt Number *</label>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <input type="text" class="form-control" name="receipt_no" id="receipt_no" 
                                            value="<?php echo generateReceiptNumber($pdo); ?>" 
                                            required readonly
                                            style="flex: 1; background: #f8f9fa;">
                                        <button type="button" class="btn btn-sm" 
                                                onclick="generateNewReceipt()"
                                                style="background: var(--gray-200); color: var(--gray-700); white-space: nowrap;">
                                            <i class="fas fa-sync-alt"></i> Regenerate
                                        </button>
                                    </div>
                                    <small style="color: var(--gray-600); font-size: 12px; margin-top: 4px; display: block;">
                                        Auto-generated receipt number
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">MC Number</label>
                                    <input type="text" class="form-control" name="mc_no" placeholder="Enter MC number (optional)">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" style="background: var(--gray-300); color: var(--gray-700);" onclick="closeModal('createCertificateModal')">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Generate Certificate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Referral Modal -->
        <div id="createReferralModal" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Create Two Way Referral Form</h2>
                        <button class="close" onclick="closeModal('createReferralModal')">&times;</button>
                    </div>
                    <form id="createReferralForm" method="POST">
                        <input type="hidden" name="id" id="referral-id">
                        <div class="modal-body">
                            <div class="form-section-header">Referral Header</div>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label">To: HOSPITAL/CLINIC OF CHOICE: *</label>
                                    <input type="text" class="form-control" name="referral-hospital" required placeholder="Enter hospital/clinic name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date: *</label>
                                    <input type="date" class="form-control" name="referral-date" required>
                                </div>
                            </div>

                            <div class="form-section-header">Patient Information</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Patient Name: *</label>
                                    <input type="text" class="form-control" name="referral-name" required placeholder="Enter patient's full name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age: *</label>
                                    <input type="number" class="form-control" name="referral-age" required placeholder="Enter age" min="0" max="150">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Sex: *</label>
                                    <div style="display: flex; gap: 20px; margin-top: 8px;">
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                            <input type="radio" name="referral-sex" value="MALE" required>
                                            <span>MALE</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                            <input type="radio" name="referral-sex" value="FEMALE" required>
                                            <span>FEMALE</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Patient Type:</label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                            <input type="checkbox" name="referral-type[]" value="OCCUPATION">
                                            <span>OCCUPATION</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                            <input type="checkbox" name="referral-type[]" value="FACULTY">
                                            <span>FACULTY</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                            <input type="checkbox" name="referral-type[]" value="STAFF">
                                            <span>STAFF</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                            <input type="checkbox" name="referral-type[]" value="STUDENT">
                                            <span>STUDENT</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Complete Address: *</label>
                                    <textarea class="form-control" name="referral-address" required placeholder="Enter complete address" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-section-header">Medical Information</div>
                            <div class="form-group full-width">
                                <label class="form-label">CASE SUMMARY:</label>
                                <textarea class="form-control" name="referral-case" placeholder="Enter case summary and medical history (optional)" rows="3"></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">REASON FOR REFERRAL/SERVICES REQUESTED:</label>
                                <textarea class="form-control" name="referral-reason" placeholder="Enter reason for referral and services requested (optional)" rows="3"></textarea>
                            </div>

                            <div class="form-section-header">Return Information (Optional)</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Send Back to Referring Agency:</label>
                                    <input type="text" class="form-control" name="referral-send-back" placeholder="Enter referring agency name (optional)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Return Date:</label>
                                    <input type="date" class="form-control" name="referral-send-back-date">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Patient Name (Return):</label>
                                    <input type="text" class="form-control" name="referral-send-back-name" placeholder="Enter patient's name for return (optional)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age (Return):</label>
                                    <input type="number" class="form-control" name="referral-send-back-age" placeholder="Enter age for return (optional)" min="0" max="150">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Sex (Return):</label>
                                    <select class="form-control" name="referral-send-back-sex">
                                        <option value="">Select Sex (optional)</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">SERVICES DONE/FINDINGS/RECOMMENDATIONS:</label>
                                    <textarea class="form-control" name="referral-services" placeholder="Enter services provided, findings, and recommendations (optional)" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="form-section-header">Signature & Authorization</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Name and Signature:</label>
                                    <input type="text" class="form-control" name="referral-signature" placeholder="Enter name and signature (optional)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Designation:</label>
                                    <input type="text" class="form-control" name="referral-designation" placeholder="Enter designation/title (optional)">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" style="background: var(--gray-300); color: var(--gray-700);" onclick="closeModal('createReferralModal')">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Generate Referral
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirmation Modals -->
        <div id="deleteConfirmationModal" class="modal">
            <div class="modal-dialog" style="max-width: 500px;">
                <div class="modal-content">
                    <div class="modal-header" style="background: #dc2626;">
                        <h2 class="modal-title" style="color: white;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Confirm Deletion
                        </h2>
                        <button class="close" onclick="closeModal('deleteConfirmationModal')" style="color: white;">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div style="text-align: center; padding: 20px 0;">
                            <div style="font-size: 48px; color: #dc2626; margin-bottom: 16px;">
                                ⚠️
                            </div>
                            <h3 style="color: #374151; margin-bottom: 12px;" id="deleteConfirmationTitle">Are you sure?</h3>
                            <p style="color: #6b7280; line-height: 1.5;" id="deleteConfirmationMessage">
                                This action cannot be undone. This will permanently delete the record.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center; gap: 12px;">
                        <button type="button" class="btn" style="background: #6b7280; color: white; min-width: 100px;" onclick="closeModal('deleteConfirmationModal')">
                            Cancel
                        </button>
                        <button type="button" class="btn" style="background: #dc2626; color: white; min-width: 100px;" id="confirmDeleteButton">
                            <i class="fas fa-trash"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="logoutConfirmationModal" class="modal">
            <div class="modal-dialog" style="max-width: 500px;">
                <div class="modal-content">
                    <div class="modal-header" style="background: #dc2626;">
                        <h2 class="modal-title" style="color: white;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Confirm Logout
                        </h2>
                        <button class="close" onclick="closeModal('logoutConfirmationModal')" style="color: white;">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div style="text-align: center; padding: 20px 0;">
                            <div style="font-size: 48px; color: #dc2626; margin-bottom: 16px;">
                                ⚠️
                            </div>
                            <h3 style="color: #374151; margin-bottom: 12px;" id="logoutConfirmationTitle">Are you sure?</h3>
                            <p style="color: #6b7280; line-height: 1.5;" id="logoutConfirmationMessage">
                                This action will log you out of your account.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer" style="justify-content: center; gap: 12px;">
                        <button type="button" class="btn" style="background: #6b7280; color: white; min-width: 100px;" onclick="closeModal('logoutConfirmationModal')">
                            Cancel
                        </button>
                        <button type="button" class="btn" style="background: #dc2626; color: white; min-width: 100px;" id="confirmLogoutButton">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Signature Modal -->
        <div id="staffSignatureModal" class="modal">
            <div class="modal-dialog" style="max-width: 600px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Update Physician Information</h2>
                        <button class="close" onclick="closeModal('staffSignatureModal')">&times;</button>
                    </div>
                    <form id="staffSignatureForm" method="POST">
                        <div class="modal-body">
                            <p style="color: var(--gray-600); margin-bottom: 20px;">
                                This information will appear on all medical certificates and referrals.
                            </p>
                            
                            <div class="form-group">
                                <label class="form-label">Physician Name *</label>
                                <input type="text" class="form-control" id="staff_name" name="staff_name" 
                                       required placeholder="e.g., MICAELLA T. BAGALANON-LABUTOY, MD, OHP">
                                <small style="color: var(--gray-600); font-size: 12px; margin-top: 4px; display: block;">
                                    Enter the full name with credentials
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Position/Title *</label>
                                <input type="text" class="form-control" id="staff_position" name="staff_position" 
                                       required placeholder="e.g., University Physician">
                                <small style="color: var(--gray-600); font-size: 12px; margin-top: 4px; display: block;">
                                    Enter the official position or title
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">License Number *</label>
                                <input type="text" class="form-control" id="staff_license" name="staff_license" 
                                       required placeholder="e.g., License. No. 0148115">
                                <small style="color: var(--gray-600); font-size: 12px; margin-top: 4px; display: block;">
                                    Enter the license number including the prefix (e.g., "License. No.")
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" style="background: var(--gray-300); color: var(--gray-700);" onclick="closeModal('staffSignatureModal')">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Certificate Code Modal -->
        <div id="certificateCodeModal" class="modal">
            <div class="modal-dialog" style="max-width: 600px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Update Document Codes</h2>
                        <button class="close" onclick="closeModal('certificateCodeModal')">&times;</button>
                    </div>
                    <form id="certificateCodeForm" method="POST">
                        <div class="modal-body">
                            <p style="color: var(--gray-600); margin-bottom: 20px;">
                                These codes appear at the bottom of medical certificates and referrals.
                            </p>
                            
                            <div class="form-group">
                                <label class="form-label">Medical Certificate Code *</label>
                                <input type="text" class="form-control" id="certificate_code" name="certificate_code" 
                                       value="<?php echo htmlspecialchars($certificate_code); ?>"
                                       required placeholder="e.g., WPU-QSF-GASS-HSO-01 Rev.00 (09.20.24)">
                                <small style="color: var(--gray-600); font-size: 12px; margin-top: 4px; display: block;">
                                    This code appears at the bottom of medical certificates
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Referral Form Code *</label>
                                <input type="text" class="form-control" id="referral_code" name="referral_code" 
                                       value="<?php echo htmlspecialchars($referral_code); ?>"
                                       required placeholder="e.g., WPU-QSF-GASS-HSO-12 Rev.00 (09.20.24)">
                                <small style="color: var(--gray-600); font-size: 12px; margin-top: 4px; display: block;">
                                    This code appears at the bottom of referral forms
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" style="background: var(--gray-300); color: var(--gray-700);" onclick="closeModal('certificateCodeModal')">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

        <!-- Admin core JS loaded at end of body -->

        <!-- Dental Add Record Modal -->
        <div id="dentalAddRecordModal" class="modal">
            <div class="modal-dialog" style="max-width: 900px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><i class="fas fa-tooth"></i> Add New Dental Record</h2>
                        <button class="close" onclick="closeDentalAddRecordModal()">&times;</button>
                    </div>
                    <form id="dentalAddRecordForm" method="POST">
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Patient Type *</label>
                                    <select id="dental_patient_type" name="patient_type" required>
                                        <option value="">Select patient type</option>
                                        <?php foreach ($wpu_patient_types as $type): ?>
                                            <option value="<?php echo (int) $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ID Number *</label>
                                    <input type="text" id="dental_student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" id="dental_full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender *</label>
                                    <select id="dental_gender" name="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age *</label>
                                    <input type="number" id="dental_age" name="age" required min="1" max="120">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marital Status *</label>
                                    <select id="dental_marital_status" name="marital_status" required>
                                        <option value="">Select status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Separated">Separated</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Is Minor? *</label>
                                    <select id="dental_is_minor" name="is_minor" required onchange="toggleDentalGuardian()">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                                <div class="form-group" id="dentalGuardianGroup" style="display: none;">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" id="dental_guardian_name" name="guardian_name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Religion</label>
                                    <input type="text" id="dental_religion" name="religion">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" id="dental_phone_number" name="phone_number">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Address</label>
                                    <textarea id="dental_address" name="address" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select id="dental_department" name="department" required>
                                        <option value="">Select department</option>
                                        <?php foreach ($wpu_departments as $dept): ?>
                                            <option value="<?php echo (int) $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Visit Date *</label>
                                    <input type="date" id="dental_visit_date" name="visit_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Case Type *</label>
                                    <select id="dental_case_type" name="case_type" required>
                                        <option value="">Select case type</option>
                                        <?php foreach ($wpu_case_types as $case): ?>
                                            <option value="<?php echo (int) $case['id']; ?>"><?php echo htmlspecialchars($case['case_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Attending Doctor *</label>
                                    <input type="text" id="dental_doctor" name="doctor" required>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Diagnosis *</label>
                                    <textarea id="dental_diagnosis" name="diagnosis" required rows="3"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Treatment *</label>
                                    <textarea id="dental_treatment" name="treatment" required rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Subjective</label>
                                    <textarea id="dental_subjective" name="subjective" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Objectives</label>
                                    <textarea id="dental_objectives" name="objectives" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Diagnostics</label>
                                    <textarea id="dental_diagnostics" name="diagnostics" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Assessment</label>
                                    <textarea id="dental_assessment" name="assessment" rows="2"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Plan</label>
                                    <textarea id="dental_plan" name="plan" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Record</button>
                            <button type="button" class="btn btn-secondary" onclick="closeDentalAddRecordModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Dental Edit Record Modal -->
        <div id="dentalEditRecordModal" class="modal">
            <div class="modal-dialog" style="max-width: 900px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><i class="fas fa-edit"></i> Edit Dental Record</h2>
                        <button class="close" onclick="closeDentalEditModal()">&times;</button>
                    </div>
                    <form id="dentalEditRecordForm" method="POST">
                        <input type="hidden" name="action" value="update_record">
                        <input type="hidden" name="record_id" id="dental_edit_record_id">
                        <input type="hidden" name="module_type" value="dental">
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Patient Type *</label>
                                    <select id="dental_edit_patient_type" name="patient_type" required>
                                        <option value="">Select patient type</option>
                                        <?php foreach ($wpu_patient_types as $type): ?>
                                            <option value="<?php echo (int) $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ID Number *</label>
                                    <input type="text" id="dental_edit_student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" id="dental_edit_full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender *</label>
                                    <select id="dental_edit_gender" name="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age *</label>
                                    <input type="number" id="dental_edit_age" name="age" required min="1" max="120">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marital Status *</label>
                                    <select id="dental_edit_marital_status" name="marital_status" required>
                                        <option value="">Select status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Separated">Separated</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Is Minor? *</label>
                                    <select id="dental_edit_is_minor" name="is_minor" required onchange="toggleDentalEditGuardian()">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                                <div class="form-group" id="dentalEditGuardianGroup" style="display: none;">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" id="dental_edit_guardian_name" name="guardian_name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Religion</label>
                                    <input type="text" id="dental_edit_religion" name="religion">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" id="dental_edit_phone_number" name="phone_number">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Address</label>
                                    <textarea id="dental_edit_address" name="address" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select id="dental_edit_department" name="department" required>
                                        <option value="">Select department</option>
                                        <?php foreach ($wpu_departments as $dept): ?>
                                            <option value="<?php echo (int) $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Visit Date *</label>
                                    <input type="date" id="dental_edit_visit_date" name="visit_date" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Case Type *</label>
                                    <select id="dental_edit_case_type" name="case_type" required>
                                        <option value="">Select case type</option>
                                        <?php foreach ($wpu_case_types as $case): ?>
                                            <option value="<?php echo (int) $case['id']; ?>"><?php echo htmlspecialchars($case['case_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Attending Doctor *</label>
                                    <input type="text" id="dental_edit_doctor" name="doctor" required>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Diagnosis *</label>
                                    <textarea id="dental_edit_diagnosis" name="diagnosis" required rows="3"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Treatment *</label>
                                    <textarea id="dental_edit_treatment" name="treatment" required rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Subjective</label>
                                    <textarea id="dental_edit_subjective" name="subjective" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Objectives</label>
                                    <textarea id="dental_edit_objectives" name="objectives" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Diagnostics</label>
                                    <textarea id="dental_edit_diagnostics" name="diagnostics" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Assessment</label>
                                    <textarea id="dental_edit_assessment" name="assessment" rows="2"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Plan</label>
                                    <textarea id="dental_edit_plan" name="plan" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Record</button>
                            <button type="button" class="btn btn-secondary" onclick="closeDentalEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Health Add Record Modal -->
        <div id="healthAddRecordModal" class="modal">
            <div class="modal-dialog" style="max-width: 900px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><i class="fas fa-heartbeat"></i> Add New Health Record</h2>
                        <button class="close" onclick="closeHealthAddRecordModal()">&times;</button>
                    </div>
                    <form id="healthAddRecordForm" method="POST">
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Patient Type *</label>
                                    <select id="health_patient_type" name="patient_type" required>
                                        <option value="">Select patient type</option>
                                        <?php foreach ($wpu_patient_types as $type): ?>
                                            <option value="<?php echo (int) $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ID Number *</label>
                                    <input type="text" id="health_student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" id="health_full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender *</label>
                                    <select id="health_gender" name="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age *</label>
                                    <input type="number" id="health_age" name="age" required min="1" max="120">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marital Status *</label>
                                    <select id="health_marital_status" name="marital_status" required>
                                        <option value="">Select status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Separated">Separated</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Is Minor? *</label>
                                    <select id="health_is_minor" name="is_minor" required onchange="toggleHealthGuardian()">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                                <div class="form-group" id="healthGuardianGroup" style="display: none;">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" id="health_guardian_name" name="guardian_name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Religion</label>
                                    <input type="text" id="health_religion" name="religion">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" id="health_phone_number" name="phone_number">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Address</label>
                                    <textarea id="health_address" name="address" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select id="health_department" name="department" required>
                                        <option value="">Select department</option>
                                        <?php foreach ($wpu_departments as $dept): ?>
                                            <option value="<?php echo (int) $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Visit Date *</label>
                                    <input type="date" id="health_visit_date" name="visit_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Case Type *</label>
                                    <select id="health_case_type" name="case_type" required>
                                        <option value="">Select case type</option>
                                        <?php foreach ($wpu_case_types as $case): ?>
                                            <option value="<?php echo (int) $case['id']; ?>"><?php echo htmlspecialchars($case['case_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Attending Doctor *</label>
                                    <input type="text" id="health_doctor" name="doctor" required>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Diagnosis *</label>
                                    <textarea id="health_diagnosis" name="diagnosis" required rows="3"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Treatment *</label>
                                    <textarea id="health_treatment" name="treatment" required rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Subjective</label>
                                    <textarea id="health_subjective" name="subjective" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Objectives</label>
                                    <textarea id="health_objectives" name="objectives" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Diagnostics</label>
                                    <textarea id="health_diagnostics" name="diagnostics" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Assessment</label>
                                    <textarea id="health_assessment" name="assessment" rows="2"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Plan</label>
                                    <textarea id="health_plan" name="plan" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Record</button>
                            <button type="button" class="btn btn-secondary" onclick="closeHealthAddRecordModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Health Edit Record Modal -->
        <div id="healthEditRecordModal" class="modal">
            <div class="modal-dialog" style="max-width: 900px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><i class="fas fa-edit"></i> Edit Health Record</h2>
                        <button class="close" onclick="closeHealthEditModal()">&times;</button>
                    </div>
                    <form id="healthEditRecordForm" method="POST">
                        <input type="hidden" name="action" value="update_record">
                        <input type="hidden" name="record_id" id="health_edit_record_id">
                        <input type="hidden" name="module_type" value="health">
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Patient Type *</label>
                                    <select id="health_edit_patient_type" name="patient_type" required>
                                        <option value="">Select patient type</option>
                                        <?php foreach ($wpu_patient_types as $type): ?>
                                            <option value="<?php echo (int) $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ID Number *</label>
                                    <input type="text" id="health_edit_student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" id="health_edit_full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender *</label>
                                    <select id="health_edit_gender" name="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age *</label>
                                    <input type="number" id="health_edit_age" name="age" required min="1" max="120">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marital Status *</label>
                                    <select id="health_edit_marital_status" name="marital_status" required>
                                        <option value="">Select status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Separated">Separated</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Is Minor? *</label>
                                    <select id="health_edit_is_minor" name="is_minor" required onchange="toggleHealthEditGuardian()">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                                <div class="form-group" id="healthEditGuardianGroup" style="display: none;">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" id="health_edit_guardian_name" name="guardian_name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Religion</label>
                                    <input type="text" id="health_edit_religion" name="religion">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" id="health_edit_phone_number" name="phone_number">
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Address</label>
                                    <textarea id="health_edit_address" name="address" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select id="health_edit_department" name="department" required>
                                        <option value="">Select department</option>
                                        <?php foreach ($wpu_departments as $dept): ?>
                                            <option value="<?php echo (int) $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Visit Date *</label>
                                    <input type="date" id="health_edit_visit_date" name="visit_date" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Case Type *</label>
                                    <select id="health_edit_case_type" name="case_type" required>
                                        <option value="">Select case type</option>
                                        <?php foreach ($wpu_case_types as $case): ?>
                                            <option value="<?php echo (int) $case['id']; ?>"><?php echo htmlspecialchars($case['case_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Attending Doctor *</label>
                                    <input type="text" id="health_edit_doctor" name="doctor" required>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Diagnosis *</label>
                                    <textarea id="health_edit_diagnosis" name="diagnosis" required rows="3"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Treatment *</label>
                                    <textarea id="health_edit_treatment" name="treatment" required rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Subjective</label>
                                    <textarea id="health_edit_subjective" name="subjective" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Objectives</label>
                                    <textarea id="health_edit_objectives" name="objectives" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Diagnostics</label>
                                    <textarea id="health_edit_diagnostics" name="diagnostics" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Assessment</label>
                                    <textarea id="health_edit_assessment" name="assessment" rows="2"></textarea>
                                </div>
                                <div class="form-group full-width">
                                    <label class="form-label">Plan</label>
                                    <textarea id="health_edit_plan" name="plan" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Record</button>
                            <button type="button" class="btn btn-secondary" onclick="closeHealthEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php include('../components/admin_chat.php'); ?>
<script src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/js/chat.js"></script>
<script>
window.WPU_HIS_CONFIG = <?php echo json_encode([
    'autoLock' => ['enabled' => (bool) $enabled, 'timeout' => (int) $timeout],
    'lockUrl' => wpu_admin_url(['action' => 'lock']),
    'dashboardUrl' => wpu_admin_url(['page' => 'dashboard']),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/js/admin-core.js?v=<?php echo (int) $his_core_js_v; ?>"></script>
<script src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/js/wpu-ajax.js?v=<?php echo (int) $his_ajax_js_v; ?>"></script>
<script src="<?php echo htmlspecialchars($his_assets, ENT_QUOTES, 'UTF-8'); ?>/js/admin-his-ui.js?v=<?php echo (int) $his_ui_js_v; ?>"></script>

</body>
</html>