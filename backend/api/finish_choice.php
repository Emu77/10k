<?php
require_once __DIR__ . '/_helpers.php';

$data   = input();
$token  = trim($data['token'] ?? '');
$choice = trim($data['choice'] ?? '');

$p = playerByToken($token);
if (!$p) err('Ungültiges Token', 401);
if ((int)($p['awaiting_choice'] ?? 0) !== 1) err('Keine Entscheidung fällig');
if (!in_array($choice, ['continue', 'end'], true)) err('Ungültige Wahl');

$db     = DB::get();
$gameId = (int)$p['game_id'];

$rst = $db->prepare('SELECT COUNT(*) AS c FROM `10k_players` WHERE game_id = ? AND finish_rank IS NOT NULL');
$rst->execute([$gameId]);
$nextRank = (int)$rst->fetch()['c'] + 1;

$db->prepare('UPDATE `10k_players` SET finish_rank = ?, awaiting_choice = 0 WHERE id = ?')
   ->execute([$nextRank, $p['id']]);

if ($choice === 'end') {
    $db->prepare('UPDATE `10k_games` SET status = "finished" WHERE id = ?')
       ->execute([$gameId]);
    ok(['ok' => true, 'ended' => true, 'finish_rank' => $nextRank]);
}

$db->prepare('UPDATE `10k_games` SET current_turn = current_turn + 1 WHERE id = ?')
   ->execute([$gameId]);

$players = gamePlayers($gameId);
$allDone = true;
foreach ($players as $pl) {
    if ($pl['finish_rank'] === null) { $allDone = false; break; }
}
if ($allDone) {
    $db->prepare('UPDATE `10k_games` SET status = "finished" WHERE id = ?')
       ->execute([$gameId]);
}

ok(['ok' => true, 'ended' => false, 'finish_rank' => $nextRank]);
