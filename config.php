<?php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = ($host === 'localhost' || str_starts_with($host, '127.'));

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'zehntausend');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_SOCK', '/opt/lampp/var/mysql/mysql.sock');
    define('BASE_URL', '/10k');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'DEIN_DB_NAME');
    define('DB_USER', 'DEIN_DB_USER');
    define('DB_PASS', 'DEIN_DB_PASS');
    define('DB_SOCK', '');
    define('BASE_URL', '/projekte/10k');
}

define('WIN_SCORE',   10000);
define('AI_THINK_MS', 800);

class DB {
    private static ?PDO $pdo = null;
    public static function get(): PDO {
        if (!self::$pdo) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            if (DB_SOCK) $dsn .= ';unix_socket=' . DB_SOCK;
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        return self::$pdo;
    }
}
