<?php
require_once __DIR__ . '/_helpers.php';

$data     = input();
$name     = trim($data['name'] ?? '');
$aiCount  = (int)($data['ai_count'] ?? 0);
$winScore = (int)($data['win_score'] ?? 10000);

if (strlen($name) < 1 || strlen($name) > 30) err('Name ungültig (1–30 Zeichen)');
if ($aiCount < 0 || $aiCount > 3)            err('0–3 KI-Gegner möglich');
if (!in_array($winScore, [5000, 10000, 15000])) err('Ungültiger Zielwert');

$db   = DB::get();
$code = genCode();

$db->beginTransaction();
// Spiel anlegen
$st = $db->prepare('INSERT INTO `10k_games` (code, win_score) VALUES (?, ?)');
$st->execute([$code, $winScore]);
$gameId = (int)$db->lastInsertId();

// Host-Spieler (Slot 0)
$token = md5(uniqid('', true));
$st = $db->prepare(
    'INSERT INTO `10k_players` (game_id, slot, name, token, is_ai) VALUES (?,?,?,?,?)'
);
$st->execute([$gameId, 0, $name, $token, 0]);

// KI-Spieler
for ($i = 0; $i < $aiCount; $i++) {
    $aiNames = ['HAL-9000', 'Deep Thought', 'ARIA', 'R2D2'];
    $st->execute([$gameId, $i + 1, $aiNames[$i] ?? "KI " . ($i+1), md5(uniqid('',true)), 1]);
}

$db->commit();

ok(['code' => $code, 'token' => $token, 'game_id' => $gameId]);
