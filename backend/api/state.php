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
$curSlot  = activeSlot($players, (int)$p['current_turn']) ?? -1;

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
    'SELECT t.*, pl.name AS pname, pl.is_ai AS pis_ai FROM `10k_turns` t
     JOIN `10k_players` pl ON pl.id = t.player_id
     WHERE t.game_id = ? ORDER BY t.id DESC LIMIT 10'
);
$st->execute([$gameId]);
$log = $st->fetchAll();

$myTurn = ((int)$p['slot'] === $curSlot) && $p['game_status'] === 'running';

// Nullwurf-/Bank-Meldung für die Anzeige (letzter Eintrag im gesamten Spiel,
// nicht auf die aktuelle Runde beschränkt, da current_turn nach Bust/Bank
// bereits weitergezählt wurde und für die neue Runde noch nichts existiert)
// KI-Aktionen werden bewusst nicht als Meldung angezeigt (nur menschliche Spieler)
$lastIsAi = isset($log[0]) && (int)($log[0]['pis_ai'] ?? 0) === 1;
$bust = isset($log[0]) && $log[0]['action'] === 'bust' && !$lastIsAi;
$lastActorName = $log[0]['pname'] ?? null;
if ($bust) {
    $message = ($lastActorName ?? 'Ein Spieler') . ' hat einen Nullwurf gewürfelt – Punkte des Zuges verloren.';
} elseif (isset($log[0]) && $log[0]['action'] === 'bank' && !$lastIsAi) {
    $message = ($lastActorName ?? 'Ein Spieler') . ' hat gebankt.';
} elseif (isset($log[0]) && $log[0]['action'] === 'roll_hot' && !$lastIsAi) {
    $message = '🔥 Hot Dice! Alle Würfel sind wieder frei.';
} else {
    $message = '';
}

$winner = null;
if ($p['game_status'] === 'finished') {
    $ranked = array_values(array_filter($players, fn($pl) => $pl['finish_rank'] !== null));
    if (!empty($ranked)) {
        usort($ranked, fn($a,$b) => $a['finish_rank'] - $b['finish_rank']);
        $winner = $ranked[0]['name'];
    } else {
        usort($players, fn($a,$b) => $b['total_score'] - $a['total_score']);
        $winner = $players[0]['name'];
    }
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

// Historie aller behaltenen Würfel über den ganzen Zug hinweg (übersteht Hot-Dice-Neustarts,
// bei denen kept_json für den frischen 5er-Wurf wieder geleert wird)
$keptHistory = [];
$st = $db->prepare(
    'SELECT kept_json FROM `10k_turns`
     WHERE game_id = ? AND turn_no = ? ORDER BY roll_no ASC'
);
$st->execute([$gameId, $turnNo]);
foreach ($st->fetchAll() as $row) {
    $kd = json_decode($row['kept_json'], true) ?: [];
    if (count($kd) === 5) {
        foreach ($kd as $d) { $keptHistory[] = $d['v']; }
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
    'must_choose_finish' => ((int)($p['awaiting_choice'] ?? 0) === 1),
    'turn_no'      => $turnNo,
    'turn_score'   => $lastRoll ? (int)$lastRoll['turn_score'] : 0,
    'last_roll'    => $lastRoll ?: null,
    'dice'         => $dice,
    'kept'         => $keptArr,
    'kept_history' => $keptHistory,
    'log'          => array_reverse($log),
    'winner'       => $winner,
    'bust'         => $bust,
    'message'      => $message,
]);
