<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

// Logout only through POST with a valid CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in() || !csrf_ok($_POST['csrf'] ?? null)) {
    redirect(is_logged_in() ? 'dashboard.php' : 'login.php');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();

redirect('login.php?msg=logged_out');
