<?php
declare(strict_types=1);

// Own session name, so this app's login is independent of the session used by app 1.
session_name('APP4SESSID');
session_start();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function redirect(string $to): never { header('Location: ' . $to); exit; }

function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }

function csrf_ok(mixed $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

function is_logged_in(): bool { return isset($_SESSION['user']['id']); }

/** Call at the top of every protected page. */
function require_login(): void {
    if (!is_logged_in()) { redirect('login.php'); }
    header('Cache-Control: no-store');   // the Back button must not reveal the page after logout
}
