Ein paar Dinge stimmen nicht mehr – Projektstruktur, PHP-Version, Einsteigsbedingung. Ich aktualisiere:

```bash
cat > /media/emu/daten/arbeit/htdocs/10k/README.md << 'EOF'
# 10K – Zehntausend 🎲
> Multiplayer-Würfelspiel für Android mit PHP/MySQL-Backend

Mehrere Spieler treten über ihre Android-Geräte gegeneinander an – online, über einen gemeinsamen Raumcode. Wahlweise mit KI-Gegnern.

---

## Features

- 🌐 **Online-Multiplayer** – Raum erstellen, Code teilen, losspielen
- 🤖 **KI-Gegner** – bis zu 3 KI-Spieler mit Greedy-Strategie
- 🎯 **Alle klassischen Wertungen** – 1er, 5er, Dreierpasch bis Fünferpasch, Hot Dice
- 🏆 **Wählbare Zielpunktzahl** – 5.000 / 10.000 / 15.000
- 🌙 **Dark Mode UI** – Material3, Jetpack Compose

---

## Technik

### Android-App

| Komponente | Technologie |
|---|---|
| Sprache | Kotlin |
| UI | Jetpack Compose + Material3 |
| Architektur | MVVM (ViewModel, StateFlow) |
| Netzwerk | Retrofit 2 + OkHttp |
| JSON | Gson |
| Async | Kotlin Coroutines |

### Backend

| Komponente | Technologie |
|---|---|
| Sprache | PHP 7.4 |
| Datenbank | MySQL |
| Schnittstelle | REST-API (JSON) |
| Routing | `api/index.php` (action-Parameter) |
| Server | Apache (Shared Hosting) |
| Hosting | kronisoft.net |

---

## Projektstruktur

```
10k/
├── backend/
│   ├── config.php              ← DB-Verbindung
│   ├── config.example.php      ← Vorlage (im Repo)
│   ├── scoring.php             ← Punkteberechnung
│   ├── setup.sql               ← Datenbankstruktur
│   └── api/
│       ├── index.php           ← Router (action=...)
│       ├── _helpers.php        ← Hilfsfunktionen
│       ├── create.php          ← Raum erstellen
│       ├── join.php            ← Beitreten
│       ├── start.php           ← Spiel starten
│       ├── roll.php            ← Würfeln
│       ├── keep.php            ← Würfel behalten
│       ├── bank.php            ← Punkte banken
│       ├── state.php           ← Spielzustand (Polling)
│       └── ai_turn.php         ← KI-Zug
├── app/
│   └── app/src/main/java/zehntausend/app/
│       ├── data/
│       │   ├── model/          ← Datenklassen (GameState, ApiResponse)
│       │   ├── network/        ← ApiService, RetrofitClient
│       │   └── repository/     ← GameRepository
│       ├── viewmodel/          ← GameViewModel, UiState
│       └── ui/screens/         ← LoginScreen, LobbyScreen, GameScreen
└── docs/                       ← Projektdokumentation
```

---

## API-Endpunkte

Alle Calls via `POST https://kronisoft.net/projekte/10k/backend/api/index.php?action=<action>`

| Action | Parameter | Beschreibung |
|---|---|---|
| `create` | `name`, `ai_count` | Neues Spiel erstellen |
| `join` | `game_code`, `name` | Spiel beitreten |
| `start` | `game_id`, `player_id`, `token` | Spiel starten |
| `roll` | `game_id`, `player_id`, `token` | Würfeln |
| `keep` | `game_id`, `player_id`, `indices`, `token` | Würfel behalten |
| `bank` | `game_id`, `player_id`, `token` | Punkte banken |
| `state` | `game_id`, `player_id`, `token` | Spielzustand abfragen |
| `ai_turn` | `game_id` | KI-Zug ausführen |

---

## Spielregeln

| Kombination | Punkte |
|---|---|
| Einzelne 1 | 100 |
| Einzelne 5 | 50 |
| Dreierpasch (1-1-1) | 1.000 |
| Dreierpasch (n-n-n) | n × 100 |
| Viererpasch | Dreierpasch × 2 |
| Fünferpasch | Dreierpasch × 4 |

**Hot Dice** – alle 5 Würfel behalten → erneut alle 5 würfeln  
**Bust** – kein wertbarer Würfel im Wurf → 0 Punkte, nächster Spieler

---

## Setup (Backend)

**1. Datenbank anlegen**
```sql
-- setup.sql in phpMyAdmin ausführen
```

**2. `config.php` anlegen** (aus `config.example.php`)
```php
define('DB_NAME', 'DEIN_DB_NAME');
define('DB_USER', 'DEIN_DB_USER');
define('DB_PASS', 'DEIN_DB_PASS');
```
> `config.php` ist in `.gitignore` – nie ins Repo committen!

**3. Dateien hochladen**
```
Ziel: kronisoft.net/projekte/10k/backend/
Tool: FileZilla
```

> ⚠️ `.htaccess` muss leer sein – `php_flag` und `Options`-Direktiven verursachen 500-Fehler auf kronisoft.net.

---

## Setup (Android-App)

**1. Projekt öffnen**
```
Android Studio → Open → 10k/app/
```

**2. Backend-URL prüfen** (`RetrofitClient.kt`)
```kotlin
private const val BASE_URL = "https://kronisoft.net/projekte/10k/backend/"
```

**3. App deployen**
```bash
# Wireless ADB (SHIFT6mq, Android 13)
adb connect 192.168.178.77:<PORT>
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

---

## Multiplayer-Konzept

Polling alle 2 Sekunden (kein WebSocket – Shared-Hosting-Einschränkung):

```
Spieler A                    API (kronisoft.net)        Spieler B / KI
   |── create ────────────>|                              |
   |<─ {code, token} ──────|                              |
   |                        |<── join ─────────────────── |
   |── start ─────────────>|                              |
   |── roll / keep / bank ->|                              |
   |── state (2s) ─────────>|<── state (2s) ───────────── |
   |                        |── ai_turn (wenn KI dran) ──>|
```

---

## Projektkontext

Abschlussprojekt der Umschulung zum **Fachinformatiker Anwendungsentwicklung** an der GPB Berlin-Neukölln (IHK Berlin).

---

## Lizenz

Privates Lernprojekt – kein offizieller Release.
EOF
```
