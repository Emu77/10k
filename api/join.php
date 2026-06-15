<?php
require_once __DIR__ . '/_helpers.php';

$data = input();
$code = strtoupper(trim($data['code'] ?? ''));
$name = trim($data['name'] ?? '');

if (strlen($code) !== 6)                    err('Ungültiger Raumcode');
if (strlen($name) < 1 || strlen($name) > 30) err('Name ungültig');

$db = DB::get();
$st = $db->prepare('SELECT * FROM `10k_games` WHERE code = ? AND status = "lobby"');
$st->execute([$code]);
$game = $st->fetch();
if (!$game) err('Raum nicht gefunden oder bereits gestartet');

// Freien Slot finden
$st = $db->prepare('SELECT MAX(slot) AS mx FROM `10k_players` WHERE game_id = ?');
$st->execute([$game['id']]);
$maxSlot = (int)($st->fetch()['mx'] ?? -1);
if ($maxSlot >= 5) err('Raum voll (max. 6 Spieler)');

$token = md5(uniqid('', true));
$st = $db->prepare(
    'INSERT INTO `10k_players` (game_id, slot, name, token, is_ai) VALUES (?,?,?,?,0)'
);
$st->execute([$game['id'], $maxSlot + 1, $name, $token]);

ok(['token' => $token, 'game_id' => $game['id'], 'code' => $code]);
