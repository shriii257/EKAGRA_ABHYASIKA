<?php
// student/logout.php — FIX: ob_start, proper session destruction
ob_start();
require_once '../includes/db.php';

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
