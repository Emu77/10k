<?php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = ($host === 'localhost' || strpos($host, '127.') === 0);

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'zehntausend');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_SOCK', '/opt/lampp/var/mysql/mysql.sock');
    define('BASE_URL', '/10k');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'usr_web32_1');
    define('DB_USER', 'web32');
    define('DB_PASS', 'Test1234');
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
