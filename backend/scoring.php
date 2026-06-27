<?php
/**
 * 10K Scoring Engine – 5-Würfel-Variante
 * Berechnet alle Wertungskombinationen aus einem Würfelwurf.
 *
 * Regeln:
 *  - Einzelne 1 = 100 Pkt
 *  - Einzelne 5 =  50 Pkt
 *  - Dreierpasch: 1-1-1 = 1000, sonst Augenzahl × 100
 *  - Viererpasch: Dreierpasch × 2
 *  - Fünferpasch: Dreierpasch × 4
 *  - Hot Dice: alle 5 behalten → erneut alle 5 würfeln
 *  (Straße, Fullhouse, Drei Paare entfallen – brauchen 6 Würfel)
 */
class Scoring {

    /**
     * Gibt alle gültigen Auswahl-Kombinationen zurück, die Punkte bringen.
     * $dice = array of int (1-6), nur nicht-behaltene Würfel
     * Returns: array of ['dice' => [...], 'score' => int, 'label' => string]
     */
    public static function allOptions(array $dice): array {
        $options = [];
        $n = count($dice);
        if ($n === 0) return [];

        // Paschs (3er, 4er, 5er) für jede Augenzahl – max. 5 Würfel
        $counts = array_count_values($dice);
        foreach ($counts as $val => $cnt) {
            $baseScore = ($val === 1) ? 1000 : ($val * 100);
            for ($pasch = 3; $pasch <= min($cnt, 5); $pasch++) {
                $mult   = ($pasch === 3) ? 1 : pow(2, $pasch - 3);
                $score  = $baseScore * $mult;
                $label  = self::paschLabel($val, $pasch);
                $subset = array_fill(0, $pasch, $val);
                // prüfe ob subset in $dice enthalten
                if (self::subsetAvailable($dice, $subset)) {
                    $options[] = ['dice' => $subset, 'score' => $score, 'label' => $label];
                }
            }
        }


        // Straße (1-2-3-4-5) und Full House - nur mit genau 5 Würfeln
        if ($n === 5) {
            $sorted = $dice;
            sort($sorted);
            // Straße: 1,2,3,4,5
            if ($sorted === [1,2,3,4,5]) {
                $options[] = ['dice' => $sorted, 'score' => 1000, 'label' => 'Straße (1-2-3-4-5)'];
            }
            // Full House: 3er + 2er
            $vals = array_unique($sorted);
            if (count($vals) === 2) {
                $cnts = array_count_values($sorted);
                $c = array_values($cnts);
                sort($c);
                if ($c === [2, 3]) {
                    $options[] = ['dice' => $sorted, 'score' => 1000, 'label' => 'Full House'];
                }
            }
        }

        // Einzelne 1er und 5er (nur wenn nicht durch Pasch abgedeckt)
        foreach ([1 => 100, 5 => 50] as $val => $pts) {
            if (($counts[$val] ?? 0) === 1 || ($counts[$val] ?? 0) === 2) {
                for ($i = 1; $i <= min(2, $counts[$val] ?? 0); $i++) {
                    $subset = array_fill(0, $i, $val);
                    $options[] = ['dice' => $subset, 'score' => $pts * $i,
                                  'label' => $i . '× Würfel ' . $val . ' (+' . ($pts*$i) . ')'];
                }
            }
        }

        // Duplikate entfernen (gleicher Score + gleiche Würfel)
        $seen = [];
        $unique = [];
        foreach ($options as $opt) {
            $key = $opt['score'] . '|' . implode(',', $opt['dice']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $opt;
            }
        }
        usort($unique, fn($a,$b) => $b['score'] - $a['score']);
        return $unique;
    }

    /**
     * Prüft ob ein Subset von Würfeln im Pool vorhanden ist.
     */
    public static function subsetAvailable(array $pool, array $subset): bool {
        $avail = $pool;
        foreach ($subset as $v) {
            $idx = array_search($v, $avail);
            if ($idx === false) return false;
            unset($avail[$idx]);
            $avail = array_values($avail);
        }
        return true;
    }

    /**
     * Prüft ob mindestens eine Kombination möglich ist (kein Bust).
     */
    public static function hasScoringOption(array $dice): bool {
        return count(self::allOptions($dice)) > 0;
    }

    /**
     * Bestes Greedy-Score für KI: nimmt immer den höchsten verfügbaren Block.
     */
    public static function bestGreedy(array $dice): array {
        $opts = self::allOptions($dice);
        return empty($opts) ? ['dice' => [], 'score' => 0, 'label' => 'Bust'] : $opts[0];
    }

    private static function paschLabel(int $val, int $cnt): string {
        $names = [3 => 'Dreierpasch', 4 => 'Viererpasch', 5 => 'Fünferpasch'];
        return ($names[$cnt] ?? "{$cnt}er Pasch") . " ({$val}er)";
    }

    /**
     * Validiert eine gesendete Auswahl: gibt Score zurück oder false.
     */
    public static function validateSelection(array $selected, array $available): int {
        if (!self::subsetAvailable($available, $selected)) return false;
        $opts = self::allOptions($available);
        // Finde matching option
        sort($selected);
        foreach ($opts as $opt) {
            $d = $opt['dice'];
            sort($d);
            if ($d === $selected) return $opt['score'];
        }
        return false;
    }
}
