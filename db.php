<?php
declare(strict_types=1);

/**
 * PDO connection. Settings come from environment variables (Render, Docker, XAMPP SetEnv),
 * then from an optional git-ignored config.local.php, then fall back to XAMPP defaults.
 * No password is stored in the source code.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }

    $local = is_file(__DIR__ . '/config.local.php') ? (array)require __DIR__ . '/config.local.php' : [];
    $cfg = static function (string $key, string $default) use ($local): string {
        $v = getenv($key);
        return ($v !== false && $v !== '') ? $v : (string)($local[$key] ?? $default);
    };

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        PDO::ATTR_TIMEOUT            => 5,
    ];
    // Hosted MySQL providers usually require TLS: set DB_SSL=true
    if (filter_var($cfg('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $cfg('DB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $cfg('DB_HOST', '127.0.0.1'), $cfg('DB_PORT', '3306'), $cfg('DB_NAME', 'php_authentication'));

    return $pdo = new PDO($dsn, $cfg('DB_USER', 'root'), $cfg('DB_PASS', ''), $options);
}
