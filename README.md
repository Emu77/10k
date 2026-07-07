# 10K – Zehntausend <img src="app/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png" width="36" align="right"/>

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
| Package | `zehntausend.app` |

### Backend
| Komponente | Technologie |
|---|---|
| Sprache | PHP 7.4 |
| Datenbank | MySQL / MariaDB |
| Schnittstelle | REST-API (JSON), eine PHP-Datei pro Endpunkt |
| Server | Apache (Shared Hosting) |
| Hosting | kronisoft.net |

---

## Projektstruktur

```
10k/
|-- backend/                    PHP-Backend
|   |-- config.php              DB-Verbindung (nicht in Git)
|   |-- config.example.php      Vorlage fuer config.php
|   |-- scoring.php             Spiellogik / Punkteberechnung
|   |-- setup.sql               Datenbankstruktur
|   |-- .htaccess               muss leer bleiben (sonst 500 auf kronisoft.net)
|   `-- api/
|       |-- _helpers.php        gemeinsame Hilfsfunktionen
|       |-- index.php
|       |-- create.php          Raum erstellen
|       |-- join.php            Beitreten
|       |-- start.php           Spiel starten
|       |-- roll.php            Wuerfeln
|       |-- keep.php            Wuerfel behalten
|       |-- bank.php            Punkte banken
|       |-- state.php           Spielzustand (Polling)
|       `-- ai_turn.php         KI-Zug
|
|-- app/                        Android-App (Gradle-Projekt)
|   `-- app/src/main/java/zehntausend/app/
|       |-- MainActivity.kt
|       |-- data/
|       |   |-- model/Models.kt         Datenklassen
|       |   |-- network/
|       |   |   |-- ApiService.kt       Retrofit-Interface
|       |   |   `-- RetrofitClient.kt
|       |   `-- repository/GameRepository.kt
|       |-- viewmodel/GameViewModel.kt
|       `-- ui/
|           |-- theme/          Color.kt, Theme.kt, Type.kt
|           `-- screens/
|               |-- LoginScreen.kt
|               |-- LobbyScreen.kt
|               |-- GameScreen.kt
|               `-- ResultScreen.kt
|
|-- docs/                       Anforderungen, ERM, API-Spezifikation, Spielregeln, Umsetzungsplan
|-- releases/                   gebaute APKs (datierte Commits)
`-- config.php                  Root-Symlink/Kopie fuer lokale XAMPP-Umgebungserkennung
```

---

## API-Endpunkte

Basis-URL: `https://kronisoft.net/projekte/10k/backend/api/`

Jeder Endpunkt ist eine eigene PHP-Datei, aufgerufen per `POST <Basis-URL><endpunkt>.php`:

| Datei | Parameter | Beschreibung |
|---|---|---|
| `create.php` | `name`, `ai_count` | Neues Spiel erstellen |
| `join.php` | `code`, `name` | Spiel beitreten |
| `start.php` | `game_id`, `player_id`, `token` | Spiel starten |
| `roll.php` | `game_id`, `player_id`, `token` | Würfeln |
| `keep.php` | `game_id`, `player_id`, `indices`, `token` | Würfel behalten |
| `bank.php` | `game_id`, `player_id`, `token` | Punkte banken |
| `state.php` | `game_id`, `player_id`, `token` | Spielzustand abfragen |
| `ai_turn.php` | `game_id` | KI-Zug ausführen |

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
-- backend/setup.sql in phpMyAdmin ausführen
```

**2. `backend/config.php` anlegen**
```php
// Kopie von backend/config.example.php mit echten Zugangsdaten
define('DB_NAME', 'DEIN_DB_NAME');
define('DB_USER', 'DEIN_DB_USER');
define('DB_PASS', 'DEIN_DB_PASS');
```
> `config.php` ist in `.gitignore` – nie ins Repo committen! `config.example.php` dient als Vorlage.

**3. Dateien hochladen**
```
Ziel: kronisoft.net/projekte/10k/backend/
Tool: FileZilla
```

> ⚠️ `backend/.htaccess` muss leer bleiben – `php_flag`/`Options`-Direktiven verursachen 500-Fehler auf kronisoft.net.

---

## Setup (Android-App)

**1. Projekt öffnen**
```
Android Studio -> Open -> app/
```

**2. Gradle sync abwarten**

**3. Backend-URL prüfen** (`RetrofitClient.kt`)
```kotlin
private const val BASE_URL = "https://kronisoft.net/projekte/10k/backend/"
```

**4. App auf Gerät deployen**
```bash
# Wireless ADB (SHIFTphone 8 / SHIFT6mq)
adb connect <IP>:<PORT>
# dann Run in Android Studio
```

**APK-Build (Kommandozeile)**
```bash
./gradlew assembleDebug --no-configuration-cache
```

---

## Multiplayer-Konzept

Der Multiplayer funktioniert über **Polling** (kein WebSocket):

```
Spieler A                    kronisoft.net/api          Spieler B
   |                               |                       |
   |-- POST create.php ---------->|                       |
   |<- {code: "AB12CD"} ---------|                       |
   |                               |                       |
   |                               |<-- POST join.php -----|
   |                               |--- {token: ...} ----->|
   |                               |                       |
   |-- POST start.php ----------->|                       |
   |                               |                       |
   |-- GET  state.php (2s) ------>|<-- GET state.php -----|
   |-- POST roll.php ------------>|                       |
   |-- POST keep.php ------------>|                       |
   |-- POST bank.php ------------>|                       |
   |-- GET  state.php (2s) ------>|<-- GET state.php -----|
```

---

## Projektkontext

Entwickelt während des Betriebspraktikums im Rahmen der Umschulung zum **Fachinformatiker Anwendungsentwicklung** an der GPB Berlin-Neukölln (IHK Berlin).

---

## Lizenz

Privates Lernprojekt – kein offizieller Release.
