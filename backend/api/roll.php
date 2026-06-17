<?php
require_once __DIR__ . '/_helpers.php';

$data  = input();
$token = trim($data['token'] ?? '');
$p     = playerByToken($token);
if (!$p)                            err('Ungültiges Token', 401);
if ($p['game_status'] !== 'running') err('Spiel läuft nicht');

$db      = DB::get();
$gameId  = (int)$p['game_id'];
$players = gamePlayers($gameId);

// Bin ich dran?
$curSlot = (int)$p['current_turn'] % count($players);
if ((int)$p['slot'] !== $curSlot) err('Nicht dein Zug');

// Aktuellen Rundenstand ermitteln
$turnNo = (int)$p['current_turn'];
$st = $db->prepare(
    'SELECT * FROM `10k_turns`
     WHERE game_id = ? AND turn_no = ?
     ORDER BY roll_no DESC LIMIT 1'
);
$st->execute([$gameId, $turnNo]);
$lastRoll = $st->fetch();

// Welche Würfel sind noch aktiv?
$keptDice   = $lastRoll ? json_decode($lastRoll['kept_json'], true) : [];
$keptVals   = array_column($keptDice, 'v');
$activeCount = 5 - count($keptVals);
// Wenn alle 5 behalten → "Hot Dice" → alle 5 wieder würfeln
if ($activeCount === 0) {
    $activeCount = 5;
    $keptDice    = [];
    $keptVals    = [];
}

$rollNo     = $lastRoll ? ((int)$lastRoll['roll_no'] + 1) : 1;
$turnScore  = $lastRoll ? (int)$lastRoll['turn_score'] : 0;

// Würfeln
$rolled = [];
for ($i = 0; $i < $activeCount; $i++) {
    $rolled[] = random_int(1, 6);
}

// Bust-Check
$hasSc = Scoring::hasScoringOption($rolled);

// Alle Würfel als JSON speichern (behalten + neu)
$allDice = array_map(fn($v) => ['v' => $v, 'kept' => true], $keptVals);
foreach ($rolled as $rv) {
    $allDice[] = ['v' => $rv, 'kept' => false];
}

$action = $hasSc ? 'roll' : 'bust';

if (!$hasSc) {
    // Bust → nächster Spieler, kein Punkt
    $nextTurn = $p['current_turn'] + count($players); // turn_no erhöhen
    $db->prepare('UPDATE `10k_games` SET current_turn = current_turn + 1 WHERE id = ?')
       ->execute([$gameId]);
}

$st = $db->prepare(
    'INSERT INTO `10k_turns`
     (game_id, player_id, turn_no, roll_no, dice_json, kept_json, roll_score, turn_score, action)
     VALUES (?,?,?,?,?,?,0,?,?)'
);
$st->execute([
    $gameId, $p['id'], $turnNo, $rollNo,
    json_encode($allDice),
    json_encode(array_map(fn($v) => ['v' => $v], $keptVals)),
    $turnScore,
    $action
]);

$options = $hasSc ? Scoring::allOptions($rolled) : [];

ok([
    'rolled'      => $rolled,
    'kept'        => $keptVals,
    'all_dice'    => $allDice,
    'bust'        => !$hasSc,
    'options'     => $options,
    'turn_score'  => $turnScore,
    'roll_no'     => $rollNo,
]);
