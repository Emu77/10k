<?php
/**
 * KI-Zug-Ausführer – wird vom Frontend per Polling getriggert,
 * wenn current_slot einer KI gehört.
 */
require_once __DIR__ . '/_helpers.php';

$data   = input();
$gameId = (int)($data['game_id'] ?? 0);
if (!$gameId) err('game_id fehlt');

$db = DB::get();
$st = $db->prepare('SELECT * FROM `10k_games` WHERE id = ? AND status = "running"');
$st->execute([$gameId]);
$game = $st->fetch();
if (!$game) err('Spiel nicht aktiv');

$players  = gamePlayers($gameId);
$nPlayers = count($players);
$curSlot  = (int)$game['current_turn'] % $nPlayers;
$curPlayer = null;
foreach ($players as $pl) {
    if ((int)$pl['slot'] === $curSlot) { $curPlayer = $pl; break; }
}
if (!$curPlayer || !$curPlayer['is_ai']) err('Kein KI-Zug fällig');

$turnNo    = (int)$game['current_turn'];
$aiId      = (int)$curPlayer['id'];
$turnScore = 0;
$keptVals  = [];
$rollNo    = 0;
$busted    = false;

// KI spielt gierig: würfelt solange, bis sie banken kann oder bust
for ($attempt = 0; $attempt < 10; $attempt++) {
    $activeCount = 5 - count($keptVals);
    if ($activeCount === 0) { $keptVals = []; $activeCount = 5; } // Hot Dice

    $rolled = [];
    for ($i = 0; $i < $activeCount; $i++) $rolled[] = random_int(1, 6);

    $rollNo++;
    if (!Scoring::hasScoringOption($rolled)) {
        // Bust
        $allDice = array_merge(
            array_map(fn($v) => ['v' => $v, 'kept' => true], $keptVals),
            array_map(fn($v) => ['v' => $v, 'kept' => false], $rolled)
        );
        $db->prepare(
            'INSERT INTO `10k_turns`
             (game_id,player_id,turn_no,roll_no,dice_json,kept_json,roll_score,turn_score,action)
             VALUES(?,?,?,?,?,?,0,0,"bust")'
        )->execute([$gameId,$aiId,$turnNo,$rollNo,json_encode($allDice),json_encode([])]);
        $busted = true;
        break;
    }

    // Bestes Greedy nehmen
    $best = Scoring::bestGreedy($rolled);
    $keptVals = array_merge($keptVals, $best['dice']);
    $turnScore += $best['score'];

    $allDice = array_merge(
        array_map(fn($v) => ['v' => $v, 'kept' => true], $keptVals),
        array_map(fn($v) => ['v' => $v, 'kept' => false],
            array_diff_key($rolled,
                array_fill(0, count($best['dice']), null) // remaining
            )
        )
    );
    // Vereinfacht: alle würfel als kept markieren nach greedy pick
    $allDice = array_map(fn($v) => ['v' => $v, 'kept' => true], $keptVals);

    $db->prepare(
        'INSERT INTO `10k_turns`
         (game_id,player_id,turn_no,roll_no,dice_json,kept_json,roll_score,turn_score,action)
         VALUES(?,?,?,?,?,?,?,?,"keep")'
    )->execute([
        $gameId,$aiId,$turnNo,$rollNo,
        json_encode($allDice),
        json_encode(array_map(fn($v) => ['v'=>$v], $keptVals)),
        $best['score'], $turnScore
    ]);

    // KI bankt wenn ≥ 300 Punkte UND zufällig (50% Chance nach 300+)
    $shouldBank = $turnScore >= 300 && (
        $turnScore >= 1500 ||
        ($curPlayer['has_entered'] && $turnScore >= 300 && random_int(0,1) === 1) ||
        (!$curPlayer['has_entered'] && $turnScore >= 1000)
    );
    if ($shouldBank) break;
}

if (!$busted && $turnScore > 0) {
    $newTotal = (int)$curPlayer['total_score'] + $turnScore;
    $won = $newTotal >= (int)$game['win_score'];

    $db->prepare(
        'UPDATE `10k_players` SET total_score=?, has_entered=1 WHERE id=?'
    )->execute([$newTotal, $aiId]);

    $db->prepare(
        'UPDATE `10k_turns` SET action="bank"
         WHERE game_id=? AND turn_no=? AND roll_no=?'
    )->execute([$gameId, $turnNo, $rollNo]);

    if ($won) {
        $db->prepare('UPDATE `10k_games` SET status="finished" WHERE id=?')
           ->execute([$gameId]);
        ok(['ai_done' => true, 'banked' => $turnScore, 'won' => true]);
    }
}

$db->prepare('UPDATE `10k_games` SET current_turn=current_turn+1 WHERE id=?')
   ->execute([$gameId]);

ok(['ai_done' => true, 'busted' => $busted, 'banked' => $busted ? 0 : $turnScore]);
