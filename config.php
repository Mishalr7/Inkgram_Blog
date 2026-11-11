<?php
// ─── Database credentials ────────────────────────────────────────────────────
// In a real server, set these as actual environment variables instead.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'inkwell_blog');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/Assignment2');

// ─── Secure Session ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ─── Database Connection ─────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('DB Connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('Service temporarily unavailable. Please try again later.');
}
$conn->set_charset('utf8mb4');

// ─── Core Helpers ─────────────────────────────────────────────────────────────
function redirect($u) { header("Location: $u"); exit; }
function is_logged_in() { return isset($_SESSION['user_id']); }
function is_admin() { return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please log in.'];
        redirect(SITE_URL . '/login.php');
    }
}
function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Admin access required.'];
        redirect(SITE_URL . '/dashboard.php');
    }
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function make_slug($t) { $s = strtolower(trim($t)); $s = preg_replace('/[^a-z0-9\s-]/', '', $s); return trim(preg_replace('/[\s-]+/', '-', $s), '-'); }
function fmt_date($d) { return date('F j, Y', strtotime($d)); }

function render_flash() {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    echo '<div class="flash ' . ($f['type'] === 'success' ? 'flash-success' : 'flash-error') . '">' . e($f['msg']) . '</div>';
}

function unique_slug(mysqli $conn, string $base): string {
    $slug = $base; $i = 1;
    do {
        $st = $conn->prepare("SELECT id FROM posts WHERE slug=? LIMIT 1");
        $st->bind_param('s', $slug); $st->execute();
        $exists = $st->get_result()->num_rows > 0; $st->close();
        if ($exists) $slug = $base . '-' . $i++;
    } while ($exists);
    return $slug;
}

// ─── CSRF Protection ──────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!$submitted || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid security token. Please try again.'];
        $back = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/index.php';
        redirect($back);
    }
}

// ─── Login Rate Limiting ──────────────────────────────────────────────────────
function is_rate_limited(mysqli $conn): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts WHERE ip=? AND attempted_at > NOW() - INTERVAL 15 MINUTE"
    );
    $st->bind_param('s', $ip); $st->execute();
    $count = (int)$st->get_result()->fetch_row()[0]; $st->close();
    return $count >= 10;
}

function record_login_attempt(mysqli $conn): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = $conn->prepare("INSERT INTO login_attempts (ip) VALUES (?)");
    $st->bind_param('s', $ip); $st->execute(); $st->close();
}

function clear_login_attempts(mysqli $conn): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = $conn->prepare("DELETE FROM login_attempts WHERE ip=?");
    $st->bind_param('s', $ip); $st->execute(); $st->close();
}
