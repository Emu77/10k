-- 10K Würfelspiel – Datenbankstruktur
-- Einmal ausführen (z.B. via phpMyAdmin)

CREATE TABLE IF NOT EXISTS `10k_games` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`         CHAR(6) NOT NULL UNIQUE,
    `status`       ENUM('lobby','running','finished') NOT NULL DEFAULT 'lobby',
    `current_turn` INT UNSIGNED NOT NULL DEFAULT 0,  -- index in players-Reihenfolge
    `win_score`    INT UNSIGNED NOT NULL DEFAULT 10000,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `10k_players` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `game_id`      INT UNSIGNED NOT NULL,
    `slot`         TINYINT UNSIGNED NOT NULL,   -- 0-based Reihenfolge
    `name`         VARCHAR(30) NOT NULL,
    `token`        CHAR(32) NOT NULL,            -- session-Token für Auth
    `is_ai`        TINYINT(1) NOT NULL DEFAULT 0,
    `total_score`  INT UNSIGNED NOT NULL DEFAULT 0,
    `has_entered`  TINYINT(1) NOT NULL DEFAULT 0, -- mind. 1× ≥ 1000 gepunktet
    FOREIGN KEY (`game_id`) REFERENCES `10k_games`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `game_slot` (`game_id`, `slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `10k_turns` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `game_id`      INT UNSIGNED NOT NULL,
    `player_id`    INT UNSIGNED NOT NULL,
    `turn_no`      INT UNSIGNED NOT NULL,
    `roll_no`      TINYINT UNSIGNED NOT NULL,
    `dice_json`    VARCHAR(300) NOT NULL,        -- alle 6 Würfel als JSON [{"v":3,"kept":false},...]
    `kept_json`    VARCHAR(300) NOT NULL DEFAULT '[]', -- behaltene Würfel
    `roll_score`   INT UNSIGNED NOT NULL DEFAULT 0,
    `turn_score`   INT UNSIGNED NOT NULL DEFAULT 0,
    `action`       ENUM('roll','keep','bank','bust') NOT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`game_id`) REFERENCES `10k_games`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
