<?php
require_once __DIR__ . '/_helpers.php';

$data  = input();
$token = trim($data['token'] ?? '');
$p     = playerByToken($token);
if (!$p)                           err('Ungültiges Token', 401);
if ((int)$p['slot'] !== 0)              err('Nur der Host kann starten');
if ($p['game_status'] !== 'lobby') err('Spiel läuft bereits');

$db = DB::get();
$players = gamePlayers((int)$p['game_id']);
if (count($players) < 2)          err('Mindestens 2 Spieler nötig');

$db->prepare('UPDATE `10k_games` SET status = "running" WHERE id = ?')
   ->execute([$p['game_id']]);

ok(['started' => true]);
