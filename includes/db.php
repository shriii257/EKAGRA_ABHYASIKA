<?php
// ============================================================
// EKAGRA ABHYASIKA - includes/db.php
// PHP 8.3 / InfinityFree compatible
// ============================================================

// ---- 1. Configure session BEFORE ob_start and session_start ----
if (session_status() === PHP_SESSION_NONE) {
    $sess_path = __DIR__ . '/../sessions';
    if (is_dir($sess_path) && is_writable($sess_path)) {
        session_save_path(realpath($sess_path));
    }
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// ---- 2. Output buffering AFTER session_start ----
if (ob_get_level() === 0) {
    ob_start();
}

// ---------------- DATABASE CONFIG ----------------
define('DB_HOST',    'sql201.infinityfree.com');
define('DB_NAME',    'if0_41864563_ekagra_db');
define('DB_USER',    'if0_41864563');
define('DB_PASS',    'oKomeUDESQyC2lq');
define('DB_CHARSET', 'utf8mb4');

// ---------------- WEBSITE CONFIG ----------------
define('SITE_NAME',   'Ekagra Abhyasika');
define('SITE_URL',    'https://ekagraabhyasika.great-site.net');
define('ADMIN_PHONE', '917000000000');

// ============================================================
// PDO CONNECTION
// ============================================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ]
    );
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DB Error</title>
    <style>body{font-family:Arial,sans-serif;background:#0a1628;color:#fff;display:flex;
    align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;}
    .box{background:#0d2b6e;border-radius:16px;padding:48px 40px;max-width:500px;}
    h2{color:#f0a500;}code{background:rgba(255,255,255,0.1);padding:4px 10px;border-radius:6px;}</style>
    </head><body><div class="box"><h2>⚠️ Database Connection Failed</h2>
    <p>Check settings in <code>includes/db.php</code></p>
    <p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>
    </div></body></html>');
}

// ============================================================
// AUTO EXPIRE STUDENTS
// ============================================================
try {
    $pdo->exec("
        UPDATE students SET status='expired'
        WHERE renewal_date < CURDATE() AND status='active'
    ");
} catch (Exception $e) {
    // Silent fail
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function logActivity(PDO $pdo, string $action, string $by, string $details = ''): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (action, performed_by, details) VALUES (?, ?, ?)");
        $stmt->execute([$action, $by, $details]);
    } catch (Exception $e) {
        // Silent fail
    }
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function fdate(?string $date, string $format = 'd M Y'): string
{
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function redirect(string $url): void
{
    if (ob_get_level() > 0) ob_end_clean();
    header("Location: $url");
    exit;
}