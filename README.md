# 10K – Zehntausend 🎲

> Multiplayer-Würfelspiel für Android mit PHP/MySQL-Backend

Mehrere Spieler treten über ihre Android-Geräte gegeneinander an – online, über einen gemeinsamen Raumcode. Wahlweise mit KI-Gegnern.

---

## Features

- 🌐 **Online-Multiplayer** – Raum erstellen, Code teilen, losspielen
- 🤖 **KI-Gegner** – 1–3 KI-Spieler mit Greedy-Strategie
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
| Navigation | Navigation Component |
| Session | SharedPreferences |

### Backend
| Komponente | Technologie |
|---|---|
| Sprache | PHP 7.4 |
| Datenbank | MySQL / MariaDB |
| Schnittstelle | REST-API (JSON) |
| Server | Apache (Shared Hosting) |
| Hosting | kronisoft.net |

---

## Projektstruktur

```
10k/                        ← PHP-Backend
├── config.php              ← DB-Verbindung, Umgebungserkennung
├── scoring.php             ← Spiellogik / Punkteberechnung
├── setup.sql               ← Datenbankstruktur
├── index.php               ← Lobby (Web-Fallback)
├── game.php                ← Spielfeld (Web-Fallback)
└── api/
    ├── create.php          ← Raum erstellen
    ├── join.php            ← Beitreten
    ├── start.php           ← Spiel starten
    ├── roll.php            ← Würfeln
    ├── keep.php            ← Würfel behalten
    ├── bank.php            ← Punkte banken
    ├── state.php           ← Spielzustand (Polling)
    └── ai_turn.php         ← KI-Zug

10k-android/                ← Android-App
└── app/src/main/java/net/kronisoft/zehntausend/
    ├── MainActivity.kt
    ├── api/
    │   ├── ApiService.kt   ← Retrofit-Interface (8 Endpunkte)
    │   └── RetrofitClient.kt
    ├── model/Models.kt     ← Datenklassen
    ├── util/SessionStore.kt
    └── ui/
        ├── Theme.kt
        ├── components/DieView.kt
        ├── lobby/
        │   ├── LobbyScreen.kt
        │   └── LobbyViewModel.kt
        └── game/
            ├── GameScreen.kt
            └── GameViewModel.kt
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
| Straße (1–2–3–4–5) | 1.000 |
| Full House (3+2) | 1.000 |

**Hot Dice** – alle 5 Würfel behalten → erneut alle 5 würfeln  
**Mindesteinstieg** – erste Wertung muss ≥ 300 Punkte sein  
**Bust** – kein wertbarer Würfel im Wurf → 0 Punkte, nächster Spieler  
**Straße (1–2–3–4–5)** – alle 5 Würfel in einem Wurf → 1.000 Punkte  
**Full House (3+2)** – alle 5 Würfel in einem Wurf → 1.000 Punkte  
**3 Striche in Folge** – Gesamtpunktestand wird auf 0 zurückgesetzt

---

## Setup (Backend)

**1. Datenbank anlegen**
```sql
-- setup.sql in phpMyAdmin ausführen
```

**2. `config.php` anpassen**
```php
define('DB_NAME', 'DEIN_DB_NAME');
define('DB_USER', 'DEIN_DB_USER');
define('DB_PASS', 'DEIN_DB_PASS');
```
> `config.php` ist in `.gitignore` – nie ins Repo committen!

**3. Dateien hochladen**
```
Ziel: kronisoft.net/projekte/10k/backend/backend/
Tool: FileZilla
```

> ⚠️ `.htaccess` muss leer sein – `php_flag`/`Options`-Direktiven verursachen 500-Fehler auf kronisoft.net.

---

## Setup (Android-App)

**1. Projekt öffnen**
```
Android Studio → Open → 10k-android/
```

**2. Gradle sync abwarten**

**3. Backend-URL prüfen** (`RetrofitClient.kt`)
```kotlin
const val BASE_URL_PROD  = "https://kronisoft.net/projekte/10k/"
const val BASE_URL_LOCAL = "http://10.0.2.2/projekte/10k/"  // Emulator
```

**4. App auf Gerät deployen**
```bash
# Wireless ADB (SHIFT6mq)
adb connect <IP>:<PORT>
# dann Run in Android Studio
```

---

## Multiplayer-Konzept

Der Multiplayer funktioniert über **Polling** (kein WebSocket):

```
Spieler A                    kronisoft.net/api          Spieler B
   |                               |                       |
   |── POST create.php ──────────>|                       |
   |<─ {code: "AB12CD"} ─────────|                       |
   |                               |                       |
   |                               |<── POST join.php ────|
   |                               |─── {token: ...} ────>|
   |                               |                       |
   |── POST start.php ──────────>|                       |
   |                               |                       |
   |── GET  state.php (2s) ──────>|<── GET state.php ────|
   |── POST roll.php ────────────>|                       |
   |── POST keep.php ────────────>|                       |
   |── POST bank.php ────────────>|                       |
   |── GET  state.php (2s) ──────>|<── GET state.php ────|
```

---

## Projektkontext

Entwickelt während des Betriebspraktikums im Rahmen der Umschulung zum **Fachinformatiker Anwendungsentwicklung** an der GPB Berlin-Neukölln (IHK Berlin).

---

## Lizenz

Privates Lernprojekt – kein offizieller Release.
