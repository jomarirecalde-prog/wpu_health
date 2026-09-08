<?php
/**
 * WPU HIS — centralized security bootstrap.
 * Session hardening, auth, CSRF, rate limiting, upload validation, audit events.
 */

declare(strict_types=1);

if (defined('WPU_SECURITY_LOADED')) {
    return;
}
define('WPU_SECURITY_LOADED', true);

/** Max failed logins before lockout (per username, sliding window). */
const WPU_LOGIN_MAX_ATTEMPTS = 5;
/** Lockout duration in seconds. */
const WPU_LOGIN_LOCKOUT_SECONDS = 900;
/** Sliding window for counting failed attempts. */
const WPU_LOGIN_ATTEMPT_WINDOW = 900;
/** Minimum password length. */
const WPU_PASSWORD_MIN_LENGTH = 12;
/** Password history entries to retain. */
const WPU_PASSWORD_HISTORY_COUNT = 5;
/** Absolute session lifetime (seconds). */
const WPU_SESSION_ABSOLUTE_TTL = 28800;
/** Idle session timeout (seconds) — aligns with autolock when enabled. */
const WPU_SESSION_IDLE_TTL = 7200;

/**
 * Start a hardened PHP session (HttpOnly, Secure when HTTPS, SameSite=Lax).
 */
function wpu_secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE) {
        return;
    }

    $secure = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (getenv('SESSION_SECURE_COOKIE') === 'true');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('wpu_admin_session');
    session_start();

    if (! isset($_SESSION['_wpu_session_created'])) {
        $_SESSION['_wpu_session_created'] = time();
        $_SESSION['_wpu_session_fingerprint'] = wpu_session_fingerprint();
    }

    wpu_enforce_session_timeouts();
}

/**
 * Basic session fingerprint (User-Agent + partial IP) to deter hijacking.
 */
function wpu_session_fingerprint(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = wpu_client_ip();

    return hash('sha256', $ua.'|'.$ip);
}

function wpu_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }

    return $ip;
}

/**
 * Invalidate session on idle/absolute timeout or fingerprint mismatch.
 */
function wpu_enforce_session_timeouts(): void
{
    if (empty($_SESSION['admin_username'])) {
        return;
    }

    $now = time();

    if (isset($_SESSION['_wpu_session_fingerprint'])
        && ! hash_equals((string) $_SESSION['_wpu_session_fingerprint'], wpu_session_fingerprint())) {
        wpu_destroy_session();
        return;
    }

    if (isset($_SESSION['_wpu_last_activity'])
        && ($now - (int) $_SESSION['_wpu_last_activity']) > WPU_SESSION_IDLE_TTL) {
        wpu_destroy_session();

        return;
    }

    if (isset($_SESSION['_wpu_session_created'])
        && ($now - (int) $_SESSION['_wpu_session_created']) > WPU_SESSION_ABSOLUTE_TTL) {
        wpu_destroy_session();

        return;
    }

    $_SESSION['_wpu_last_activity'] = $now;
}

function wpu_destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Build Content-Security-Policy header value.
 */
function wpu_content_security_policy(): string
{
    $scriptSrc = "'self' 'unsafe-inline' https://cdnjs.cloudflare.com";

    $debug = (function_exists('config') && config('app.debug'))
        || filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);

    if ($debug) {
        $scriptSrc .= " 'unsafe-eval'";
    }

    return "default-src 'self'; script-src {$scriptSrc}; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
}

/**
 * Send OWASP-recommended security headers for legacy PHP responses.
 */
function wpu_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    $isHttps = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    header('Content-Security-Policy: '.wpu_content_security_policy());
}

/**
 * Bridge Laravel admin guard into legacy session when available.
 */
function wpu_sync_laravel_admin_session(): void
{
    if (defined('WPU_LARAVEL_ADMIN_USER') && WPU_LARAVEL_ADMIN_USER !== '') {
        $_SESSION['admin_username'] = WPU_LARAVEL_ADMIN_USER;
        if (empty($_SESSION['login_time'])) {
            $_SESSION['login_time'] = date('Y-m-d H:i:s');
        }
    } elseif (function_exists('auth') && auth()->guard('admin')->check()) {
        $_SESSION['admin_username'] = auth()->guard('admin')->user()->username;
    }
}

function wpu_is_admin_logged_in(): bool
{
    wpu_sync_laravel_admin_session();

    return ! empty($_SESSION['admin_username']);
}

/**
 * Resolve admin.php URL for redirects (Laravel workspace bridge or standalone).
 *
 * @param  array<string, scalar|null>  $query
 */
function wpu_admin_url(array $query = []): string
{
    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('route')) {
        $url = route('admin.workspace', ['path' => 'admin/admin.php']);
    } else {
        $url = 'admin.php';
    }

    if ($query !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }

    return $url;
}

/**
 * Require authenticated admin. JSON endpoints get 401; HTML redirects to login.
 */
function wpu_require_admin_auth(bool $json = false): void
{
    wpu_secure_session_start();
    wpu_send_security_headers();
    wpu_sync_laravel_admin_session();

    if (wpu_is_admin_logged_in()) {
        return;
    }

    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $login = defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE
        ? '/login'
        : 'admin.php?page=login';

    header('Location: '.$login, true, 302);
    exit;
}

/** Bootstrap JSON admin API/script. */
function wpu_bootstrap_admin_api(): void
{
    wpu_require_admin_auth(true);
}

/** Bootstrap HTML admin page (print views, etc.). */
function wpu_bootstrap_admin_page(): void
{
    wpu_require_admin_auth(false);
}

/**
 * Resolve CSRF token from request (header, POST field, or JSON body).
 */
function wpu_csrf_token_from_request(): string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? '';
    if ($header !== '') {
        return (string) $header;
    }

    if (isset($_POST['_token'])) {
        return (string) $_POST['_token'];
    }
    if (isset($_POST['form_token'])) {
        return (string) $_POST['form_token'];
    }

    return '';
}

/**
 * Verify CSRF for mutating requests. Supports Laravel bridge tokens and legacy form_token.
 */
function wpu_verify_csrf_request(bool $fail = true): bool
{
    $token = wpu_csrf_token_from_request();
    $valid = false;

    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('csrf_token')) {
        $valid = $token !== '' && hash_equals((string) csrf_token(), $token);
    } else {
        wpu_secure_session_start();

        if ($token !== '' && isset($_SESSION['form_token']) && hash_equals((string) $_SESSION['form_token'], $token)) {
            $valid = true;
        }

        if (! $valid && function_exists('csrf_token') && $token !== '' && hash_equals((string) csrf_token(), $token)) {
            $valid = true;
        }
    }

    if (! $valid && $fail) {
        wpu_security_audit(null, 'csrf_failure', 'Invalid or missing CSRF token');
        http_response_code(419);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh the page and try again.']);
        exit;
    }

    return $valid;
}

/**
 * Require CSRF on POST/PUT/PATCH/DELETE (except explicit exempt actions).
 *
 * @param  list<string>  $exemptActions
 */
function wpu_require_csrf_for_mutation(array $exemptActions = []): void
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if (in_array($action, $exemptActions, true)) {
        return;
    }

    wpu_verify_csrf_request(true);
}

function wpu_ensure_csrf_token(): string
{
    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('csrf_token')) {
        return (string) csrf_token();
    }

    wpu_secure_session_start();
    if (empty($_SESSION['form_token'])) {
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['form_token'];
}

function wpu_rotate_csrf_token(): string
{
    if (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('session')) {
        session()->regenerateToken();

        return (string) csrf_token();
    }

    wpu_secure_session_start();
    $_SESSION['form_token'] = bin2hex(random_bytes(32));

    return (string) $_SESSION['form_token'];
}

/**
 * File-based rate limiter (IP + key). Returns true if allowed.
 */
function wpu_rate_limit(string $key, int $maxAttempts, int $decaySeconds): bool
{
    $ip = wpu_client_ip();
    $bucket = sys_get_temp_dir().'/wpu_rl_'.hash('sha256', $key.'|'.$ip);
    $now = time();
    $data = ['count' => 0, 'reset' => $now + $decaySeconds];

    if (is_readable($bucket)) {
        $raw = @file_get_contents($bucket);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }

    if ($now > ($data['reset'] ?? 0)) {
        $data = ['count' => 0, 'reset' => $now + $decaySeconds];
    }

    $data['count'] = (int) ($data['count'] ?? 0) + 1;
    @file_put_contents($bucket, json_encode($data), LOCK_EX);

    return $data['count'] <= $maxAttempts;
}

/**
 * Verify password; migrates legacy plaintext to bcrypt on success.
 */
function wpu_verify_admin_password(PDO $pdo, array $admin, string $password): bool
{
    $hash = (string) ($admin['password'] ?? '');

    if ($hash === '') {
        return false;
    }

    if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon2')) {
        if (! password_verify($password, $hash)) {
            return false;
        }
        if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
            wpu_upgrade_password_hash($pdo, (int) $admin['id'], $password);
        }

        return true;
    }

    // Legacy plaintext — verify once, then rehash (preserves access, removes plaintext storage).
    if (! hash_equals($hash, $password)) {
        return false;
    }

    wpu_upgrade_password_hash($pdo, (int) $admin['id'], $password);

    return true;
}

function wpu_upgrade_password_hash(PDO $pdo, int $adminId, string $password): void
{
    $newHash = password_hash($password, PASSWORD_ARGON2ID);
    $stmt = $pdo->prepare('UPDATE admins SET password = ?, password_changed_at = NOW() WHERE id = ?');
    $stmt->execute([$newHash, $adminId]);
}

/**
 * Password complexity policy enforcement.
 */
function wpu_validate_password_policy(string $password): ?string
{
    if (strlen($password) < WPU_PASSWORD_MIN_LENGTH) {
        return 'Password must be at least '.WPU_PASSWORD_MIN_LENGTH.' characters.';
    }
    if (! preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (! preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    if (! preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    if (! preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must contain at least one special character.';
    }

    return null;
}

function wpu_hash_new_password(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Record login attempt and enforce lockout.
 */
function wpu_record_login_attempt(PDO $pdo, string $username, bool $success): void
{
    wpu_ensure_security_tables($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO admin_login_attempts (username, ip_address, user_agent, successful, attempted_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        mb_substr($username, 0, 50),
        mb_substr(wpu_client_ip(), 0, 45),
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        $success ? 1 : 0,
    ]);

    if ($success) {
        $pdo->prepare('UPDATE admins SET failed_login_attempts = 0, locked_until = NULL WHERE username = ?')
            ->execute([$username]);

        return;
    }

    wpu_security_audit($pdo, 'login_failed', 'Failed login for user: '.$username);

    $pdo->prepare('UPDATE admins SET failed_login_attempts = COALESCE(failed_login_attempts, 0) + 1 WHERE username = ?')
        ->execute([$username]);

    $failStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM admin_login_attempts
         WHERE username = ? AND successful = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $failStmt->execute([$username, WPU_LOGIN_ATTEMPT_WINDOW]);
    $failCount = (int) $failStmt->fetchColumn();

    if ($failCount >= WPU_LOGIN_MAX_ATTEMPTS) {
        $pdo->prepare('UPDATE admins SET locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE username = ?')
            ->execute([WPU_LOGIN_LOCKOUT_SECONDS, $username]);
        wpu_security_audit($pdo, 'account_locked', 'Account locked: '.$username);
    }
}

function wpu_is_account_locked(PDO $pdo, string $username): bool
{
    wpu_ensure_security_tables($pdo);

    $stmt = $pdo->prepare('SELECT locked_until FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $lockedUntil = $stmt->fetchColumn();

    if ($lockedUntil === false || $lockedUntil === null) {
        return false;
    }

    return strtotime((string) $lockedUntil) > time();
}

function wpu_check_password_history(PDO $pdo, int $adminId, string $password): bool
{
    wpu_ensure_security_tables($pdo);

    $stmt = $pdo->prepare(
        'SELECT password_hash FROM admin_password_history WHERE admin_id = ? ORDER BY created_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, $adminId, PDO::PARAM_INT);
    $stmt->bindValue(2, WPU_PASSWORD_HISTORY_COUNT, PDO::PARAM_INT);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, (string) $row['password_hash'])) {
            return false;
        }
    }

    return true;
}

function wpu_store_password_history(PDO $pdo, int $adminId, string $passwordHash): void
{
    wpu_ensure_security_tables($pdo);

    $pdo->prepare('INSERT INTO admin_password_history (admin_id, password_hash, created_at) VALUES (?, ?, NOW())')
        ->execute([$adminId, $passwordHash]);

    $pdo->prepare(
        'DELETE FROM admin_password_history WHERE admin_id = ? AND id NOT IN (
            SELECT id FROM (
                SELECT id FROM admin_password_history WHERE admin_id = ? ORDER BY created_at DESC LIMIT ?
            ) t
        )'
    )->execute([$adminId, $adminId, WPU_PASSWORD_HISTORY_COUNT]);
}

/**
 * Sanitize search input for LIKE queries.
 */
function wpu_sanitize_search_term(string $term, int $maxLen = 100): string
{
    $term = trim($term);
    $term = mb_substr($term, 0, $maxLen);

    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
}

/**
 * Validate uploaded file using finfo magic bytes (not client Content-Type).
 *
 * @return array{ok: bool, message: string, mime: string, extension: string}
 */
function wpu_validate_uploaded_file(array $file, int $maxBytes = 5242880): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed.', 'mime' => '', 'extension' => ''];
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'message' => 'File too large.', 'mime' => '', 'extension' => ''];
    }

    $original = (string) ($file['name'] ?? 'upload');
    if (str_contains($original, "\0") || preg_match('/\.(php|phtml|php3|php4|php5|phar|htaccess|cgi|asp|aspx|jsp|exe|sh|bat|cmd)(\.|$)/i', $original)) {
        return ['ok' => false, 'message' => 'File type not allowed.', 'mime' => '', 'extension' => ''];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    if (! isset($allowed[$mime])) {
        return ['ok' => false, 'message' => 'Invalid file type.', 'mime' => $mime, 'extension' => ''];
    }

    return ['ok' => true, 'message' => '', 'mime' => $mime, 'extension' => $allowed[$mime]];
}

function wpu_random_upload_filename(string $extension): string
{
    return bin2hex(random_bytes(16)).'.'.preg_replace('/[^a-z0-9]/', '', strtolower($extension));
}

function wpu_safe_download_filename(string $name): string
{
    $safe = preg_replace('/[^\w.\- ]/u', '_', $name);

    return ($safe !== null && $safe !== '') ? $safe : 'download';
}

/**
 * Abort request when CSRF token is invalid (JSON for XHR, flash message for HTML forms).
 */
function wpu_abort_if_invalid_csrf(): void
{
    if (wpu_verify_csrf_request(false)) {
        return;
    }

    wpu_security_audit(null, 'csrf_failure', (string) ($_POST['action'] ?? $_GET['action'] ?? 'unknown'));

    $isAjax = ! empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

    if ($isAjax) {
        http_response_code(419);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh the page and try again.']);
        exit;
    }

    $GLOBALS['error_message'] = 'Invalid security token. Please refresh the page and try again.';
    unset($_POST['action']);
}

/**
 * Security audit log (never log passwords/tokens/PHI bodies).
 */
function wpu_security_audit(?PDO $pdo, string $action, string $details = ''): void
{
    if ($pdo === null) {
        try {
            require_once __DIR__.'/../config/database.php';
            $pdo = getDBConnection();
        } catch (Throwable) {
            error_log('[WPU Security] '.$action.': '.$details);

            return;
        }
    }

    wpu_ensure_security_tables($pdo);

    $username = $_SESSION['admin_username'] ?? (defined('WPU_LARAVEL_ADMIN_USER') ? WPU_LARAVEL_ADMIN_USER : 'system');

    $stmt = $pdo->prepare(
        'INSERT INTO security_audit_log (username, ip_address, user_agent, action, details, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        mb_substr((string) $username, 0, 50),
        mb_substr(wpu_client_ip(), 0, 45),
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        mb_substr($action, 0, 100),
        mb_substr($details, 0, 500),
    ]);
}

/** Create security tables/columns if missing (safe for legacy deployments). */
function wpu_ensure_security_tables(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NULL,
            successful TINYINT(1) NOT NULL DEFAULT 0,
            attempted_at DATETIME NOT NULL,
            INDEX idx_username_time (username, attempted_at),
            INDEX idx_ip_time (ip_address, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_password_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_admin_created (admin_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS security_audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NULL,
            action VARCHAR(100) NOT NULL,
            details VARCHAR(500) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_action_time (action, created_at),
            INDEX idx_username_time (username, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $columns = [
        'failed_login_attempts' => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'locked_until' => 'DATETIME NULL DEFAULT NULL',
        'password_changed_at' => 'DATETIME NULL DEFAULT NULL',
        'two_factor_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'two_factor_secret' => 'VARCHAR(255) NULL DEFAULT NULL',
    ];

    foreach ($columns as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE admins ADD COLUMN {$col} {$definition}");
        } catch (PDOException) {
            // Column already exists.
        }
    }
}
