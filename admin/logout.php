<?php
// admin/logout.php — FIX: ob_start, db.php handles session_start()
ob_start();
require_once '../includes/db.php';

if (isset($_SESSION['admin_id'])) {
    logActivity($pdo, 'Admin Logout', $_SESSION['admin_user'] ?? 'admin', 'Logged out');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}
session_destroy();

ob_end_clean();
header('Location: login.php');
exit;
