<?php
require_once __DIR__ . '/_helpers.php';

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim($body['token'] ?? $_GET['token'] ?? $_POST['token'] ?? '');
$p     = playerByToken($token);
if (!$p) err('Ungültiges Token', 401);

$db      = DB::get();
$gameId  = (int)$p['game_id'];
$players = gamePlayers($gameId);
$nPlayers = count($players);
$curSlot  = (int)$p['current_turn'] % max($nPlayers, 1);

// Letzter Zustand dieser Runde
$turnNo = (int)$p['current_turn'];
$st = $db->prepare(
    'SELECT * FROM `10k_turns`
     WHERE game_id = ? AND turn_no = ?
     ORDER BY roll_no DESC LIMIT 1'
);
$st->execute([$gameId, $turnNo]);
$lastRoll = $st->fetch();

// Letzten Log-Einträge für Anzeige
$st = $db->prepare(
    'SELECT t.*, pl.name AS pname FROM `10k_turns` t
     JOIN `10k_players` pl ON pl.id = t.player_id
     WHERE t.game_id = ? ORDER BY t.id DESC LIMIT 10'
);
$st->execute([$gameId]);
$log = $st->fetchAll();

$myTurn = ((int)$p['slot'] === $curSlot) && $p['game_status'] === 'running';

// Nullwurf-/Bank-Meldung für die Anzeige (letzter Eintrag im gesamten Spiel,
// nicht auf die aktuelle Runde beschränkt, da current_turn nach Bust/Bank
// bereits weitergezählt wurde und für die neue Runde noch nichts existiert)
$bust = isset($log[0]) && $log[0]['action'] === 'bust';
$lastActorName = $log[0]['pname'] ?? null;
if ($bust) {
    $message = ($lastActorName ?? 'Ein Spieler') . ' hat einen Nullwurf gewürfelt – Punkte des Zuges verloren.';
} elseif (isset($log[0]) && $log[0]['action'] === 'bank') {
    $message = ($lastActorName ?? 'Ein Spieler') . ' hat gebankt.';
} else {
    $message = '';
}

// Gewinner ermitteln
$winner = null;
if ($p['game_status'] === 'finished') {
    usort($players, fn($a,$b) => $b['total_score'] - $a['total_score']);
    $winner = $players[0]['name'];
}


// Würfel aus letztem Roll extrahieren
$dice = [];
$keptArr = [];
if ($lastRoll) {
    $allDice = json_decode($lastRoll['dice_json'], true) ?: [];
    foreach ($allDice as $d) {
        if (!$d['kept']) $dice[] = $d['v'];
        else $keptArr[] = $d['v'];
    }
}
ok([
    'status'       => $p['game_status'],
    'code'         => $p['code'],
    'win_score'    => (int)$p['win_score'],
    'players'      => $players,
    'current_slot' => $curSlot,
    'my_slot'      => (int)$p['slot'],
    'my_turn'      => $myTurn,
    'turn_no'      => $turnNo,
    'last_roll'    => $lastRoll ?: null,
    'dice'         => $dice,
    'kept'         => $keptArr,
    'log'          => array_reverse($log),
    'winner'       => $winner,
    'bust'         => $bust,
    'message'      => $message,
]);
