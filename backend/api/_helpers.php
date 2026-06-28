<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../scoring.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Datenbankverbindung ───────────────────────────────────────────────────
class DB {
    private static ?PDO $pdo = null;
    public static function get(): PDO {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }
}


function ok(array $data): void {
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
function input(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? $_POST;
}

// ── Spieler aus Token laden ───────────────────────────────────────────────
function playerByToken(string $token) {
    $st = DB::get()->prepare(
        'SELECT p.*, g.status AS game_status, g.current_turn, g.win_score, g.code
         FROM `10k_players` p
         JOIN `10k_games` g ON g.id = p.game_id
         WHERE p.token = ?'
    );
    $st->execute([$token]);
    return $st->fetch();
}

// ── Aktuellen Rundenstand laden ───────────────────────────────────────────
function currentTurnState(int $gameId, int $playerSlot, int $turnNo): array {
    $st = DB::get()->prepare(
        'SELECT * FROM `10k_turns`
         WHERE game_id = ? AND turn_no = ?
         ORDER BY roll_no DESC LIMIT 1'
    );
    $st->execute([$gameId, $turnNo]);
    return $st->fetch() ?: [];
}

// ── Alle Spieler eines Spiels ─────────────────────────────────────────────
function gamePlayers(int $gameId): array {
    $st = DB::get()->prepare(
        'SELECT id, slot, name, is_ai, total_score, has_entered, bust_streak
         FROM `10k_players` WHERE game_id = ? ORDER BY slot'
    );
    $st->execute([$gameId]);
    return $st->fetchAll();
}

// ── Raumcode generieren ───────────────────────────────────────────────────
function genCode(): string {
    return strtoupper(substr(md5(uniqid('', true)), 0, 6));
}
