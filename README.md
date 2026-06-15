# 10K – Zehntausend 🎲

> Multiplayer-Würfelspiel für Android mit PHP/MySQL-Backend

Mehrere Spieler treten über ihre Android-Geräte gegeneinander an – online, über einen gemeinsamen Raumcode. Wahlweise mit KI-Gegnern.

---

## Features

- 🌐 **Online-Multiplayer** – Raum erstellen, Code teilen, losspielen
- 🤖 **KI-Gegner** – 1–3 KI-Spieler mit Greedy-Strategie
- 🎯 **Alle klassischen Wertungen** – 1er, 5er, Dreierpasch bis Fünferpasch, Hot Dice
- 📏 **Einsteigsbedingung** – erste Wertung muss ≥ 1.000 Punkte sein
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
| Sprache | PHP 8.x |
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
**Einsteigsbedingung** – erste gebankten Punkte müssen ≥ 1.000 sein  
**Bust** – kein wertbarer Würfel im Wurf → 0 Punkte, nächster Spieler

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
Ziel: kronisoft.net/projekte/10k/
Tool: FileZilla (FTP/SFTP)
```

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
