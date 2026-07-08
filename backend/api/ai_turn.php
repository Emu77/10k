<?php
/**
 * KI-Zug-Ausführer – wird vom Frontend per Polling getriggert,
 * wenn current_slot einer KI gehört.
 *
 * Fast-Forward: Sobald KEIN Mensch mehr aktiv ist (alle Menschen haben
 * bereits einen finish_rank – d.h. entweder "Beenden" gewählt, was das
 * Spiel ohnehin sofort beendet, oder "Weiterspielen lassen"), spielt
 * dieser einzelne Request alle verbleibenden KI-Züge automatisch bis
 * zum Spielende durch, statt pro Zug auf einen neuen Poll zu warten.
 */
require_once __DIR__ . '/_helpers.php';

$data   = input();
$gameId = (int)($data['game_id'] ?? 0);
if (!$gameId) err('game_id fehlt');

$db = DB::get();

function allHumansDone(array $players): bool {
    foreach ($players as $pl) {
        if ((int)$pl['is_ai'] === 0 && $pl['finish_rank'] === null) return false;
    }
    return true;
}

// Vor dem ersten Zug prüfen, ob wir überhaupt in die Fast-Forward-Phase
// eintreten (kein Mensch wird ab jetzt noch handeln müssen).
$initPlayers  = gamePlayers($gameId);
$fastForward  = allHumansDone($initPlayers);
$maxTurns     = $fastForward ? 500 : 1;   // Sicherheitsdeckel gegen Endlosschleifen
$maxSeconds   = 20;                        // Sicherheitsdeckel gegen PHP-Timeout (Shared Hosting)
$startedAt    = microtime(true);

$turnsPlayed = 0;
$lastBusted  = false;
$lastBanked  = 0;
$anyoneWon   = false;

for ($loop = 0; $loop < $maxTurns; $loop++) {
    if ((microtime(true) - $startedAt) > $maxSeconds) break;

    $st = $db->prepare('SELECT * FROM `10k_games` WHERE id = ? AND status = "running"');
    $st->execute([$gameId]);
    $game = $st->fetch();
    if (!$game) break; // Spiel ist inzwischen fertig

    $players  = gamePlayers($gameId);
    $curSlot  = activeSlot($players, (int)$game['current_turn']);
    $curPlayer = null;
    if ($curSlot !== null) {
        foreach ($players as $pl) {
            if ((int)$pl['slot'] === $curSlot) { $curPlayer = $pl; break; }
        }
    }

    if (!$curPlayer || !$curPlayer['is_ai']) {
        if ($loop === 0) err('Kein KI-Zug fällig');
        break; // Mensch ist wieder dran (oder niemand mehr aktiv) -> Fast-Forward stoppen
    }

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

    $won = false;
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
            $rst = $db->prepare('SELECT COUNT(*) AS c FROM `10k_players` WHERE game_id = ? AND finish_rank IS NOT NULL');
            $rst->execute([$gameId]);
            $nextRank = (int)$rst->fetch()['c'] + 1;
            $db->prepare('UPDATE `10k_players` SET finish_rank = ? WHERE id = ?')
               ->execute([$nextRank, $aiId]);
            $anyoneWon = true;

            $allPlayers = gamePlayers($gameId);
            if (allHumansDone($allPlayers)) {
                $allDone = true;
                foreach ($allPlayers as $pl) {
                    if ($pl['finish_rank'] === null) { $allDone = false; break; }
                }
                if ($allDone) {
                    $db->prepare('UPDATE `10k_games` SET status="finished" WHERE id=?')
                       ->execute([$gameId]);
                }
            }
        }
    }

    $db->prepare('UPDATE `10k_games` SET current_turn=current_turn+1 WHERE id=?')
       ->execute([$gameId]);

    $turnsPlayed++;
    $lastBusted = $busted;
    $lastBanked = $busted ? 0 : $turnScore;

    if (!$fastForward) break; // altes Verhalten: genau ein Zug pro Aufruf

    // Prüfen, ob das Spiel inzwischen beendet ist -> Schleife stoppen
    $st2 = $db->prepare('SELECT status FROM `10k_games` WHERE id = ?');
    $st2->execute([$gameId]);
    if (($st2->fetch()['status'] ?? '') !== 'running') break;
}

ok([
    'ai_done'        => true,
    'busted'         => $lastBusted,
    'banked'         => $lastBanked,
    'fast_forwarded' => $fastForward,
    'turns_played'   => $turnsPlayed,
    'any_win'        => $anyoneWon,
]);
