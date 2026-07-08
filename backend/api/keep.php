<?php
require_once __DIR__ . '/_helpers.php';

$body     = file_get_contents('php://input');
$data     = json_decode($body, true) ?? $_POST;
$token    = trim($data['token'] ?? '');
$selected = $data['selected'] ?? (isset($data['selected']) ? $data['selected'] : []);
if (is_string($selected)) $selected = array_map('intval', explode(',', $selected)); // array of int values to keep

$p = playerByToken($token);
if (!$p)                             err('Ungültiges Token', 401);
if ($p['game_status'] !== 'running') err('Spiel läuft nicht');

$db      = DB::get();
$gameId  = (int)$p['game_id'];
$players = gamePlayers($gameId);
$curSlot = activeSlot($players, (int)$p['current_turn']);
if ($curSlot === null || (int)$p['slot'] !== $curSlot) err('Nicht dein Zug');

$turnNo = (int)$p['current_turn'];
$st = $db->prepare(
    'SELECT * FROM `10k_turns`
     WHERE game_id = ? AND turn_no = ? AND action != "bust"
     ORDER BY roll_no DESC LIMIT 1'
);
$st->execute([$gameId, $turnNo]);
$lastRoll = $st->fetch();
if (!$lastRoll) err('Kein aktiver Wurf');

// Aktive (nicht-behaltene) Würfel ermitteln
$allDice   = json_decode($lastRoll['dice_json'], true);
$activeDice = array_values(array_map(
    fn($d) => $d['v'],
    array_filter($allDice, fn($d) => !$d['kept'])
));

// Validierung der Auswahl
sort($selected);
$score = Scoring::validateSelection($selected, $activeDice);
if ($score === false) err('Ungültige Auswahl – keine Wertung');

$newTurnScore = (int)$lastRoll['turn_score'] + $score;

// Würfelzustand aktualisieren: ausgewählte als kept markieren
$remaining = $activeDice;
foreach ($selected as $sv) {
    $idx = array_search($sv, $remaining);
    if ($idx !== false) {
        unset($remaining[$idx]);
        $remaining = array_values($remaining);
    }
}
// Neues dice_json korrekt bauen
$newDice = [];
foreach ($allDice as $d) {
    if ($d['kept']) $newDice[] = ['v' => $d['v'], 'kept' => true];
}
foreach ($selected as $v) {
    $newDice[] = ['v' => $v, 'kept' => true];
}
foreach ($remaining as $v) {
    $newDice[] = ['v' => $v, 'kept' => false];
}
$newKept = array_values(array_filter($newDice, fn($d) => $d['kept']));
// Update letzten Turn-Eintrag
$db->prepare(
'UPDATE `10k_turns` SET dice_json = ?, kept_json = ?, turn_score = ?, action = "keep"
     WHERE game_id = ? AND turn_no = ? AND roll_no = ?'
)->execute([
    json_encode($newDice),
    json_encode($newKept),
    $newTurnScore,
    $gameId, $turnNo, (int)$lastRoll['roll_no']
]);

ok([
    'kept'        => array_column($newKept, 'v'),
    'remaining'   => $remaining,
    'turn_score'  => $newTurnScore,
    'score_added' => $score,
    'can_roll'    => true,
]);
