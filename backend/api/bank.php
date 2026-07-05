<?php
require_once __DIR__ . '/_helpers.php';

$data  = input();
$token = trim($data['token'] ?? '');
$p     = playerByToken($token);
if (!$p)                             err('Ungültiges Token', 401);
if ($p['game_status'] !== 'running') err('Spiel läuft nicht');

$db      = DB::get();
$gameId  = (int)$p['game_id'];
$players = gamePlayers($gameId);
$curSlot = (int)$p['current_turn'] % count($players);
if ((int)$p['slot'] !== $curSlot)   err('Nicht dein Zug');

$turnNo = (int)$p['current_turn'];
$st = $db->prepare(
    'SELECT * FROM `10k_turns`
     WHERE game_id = ? AND turn_no = ?
     ORDER BY roll_no DESC LIMIT 1'
);
$st->execute([$gameId, $turnNo]);
$lastRoll = $st->fetch();
if (!$lastRoll) err('Nichts zum Banken');
if ($lastRoll['action'] === 'bust') err('Zug endete als Bust – nichts zu banken');
if ($lastRoll['action'] === 'roll') err('Du musst zuerst Würfel behalten, bevor du bankst');

$turnScore  = (int)$lastRoll['turn_score'];
$hasEntered = (int)$p['has_entered'];

// Einsteigsbedingung: mind. 1000 Punkte im ersten Bank
if (!$hasEntered && $turnScore < 300) {
    err('Erste Wertung muss mindestens 300 Punkte betragen');
}

$newTotal = (int)$p['total_score'] + $turnScore;
$winScore = (int)$p['win_score'];
$won      = $newTotal >= $winScore;

// Spieler-Score aktualisieren
$db->prepare(
    'UPDATE `10k_players` SET total_score = ?, has_entered = 1, bust_streak = 0 WHERE id = ?'
)->execute([$newTotal, $p['id']]);

// Turn als "bank" markieren
$db->prepare(
    'UPDATE `10k_turns` SET action = "bank" WHERE game_id = ? AND turn_no = ? AND roll_no = ?'
)->execute([$gameId, $turnNo, (int)$lastRoll['roll_no']]);

if ($won) {
    $db->prepare('UPDATE `10k_games` SET status = "finished" WHERE id = ?')
       ->execute([$gameId]);
} else {
    // Nächster Spieler
    $db->prepare('UPDATE `10k_games` SET current_turn = current_turn + 1 WHERE id = ?')
       ->execute([$gameId]);
}

ok([
    'ok'          => true,
    'banked'      => $turnScore,
    'total'       => $newTotal,
    'won'         => $won,
    'next_turn'   => !$won,
]);
