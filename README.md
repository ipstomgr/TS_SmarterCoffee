# TS_SmarterCoffee

Modul für IP-Symcon zur Steuerung der Kaffeemaschine **Smarter Coffee V1**.

Original von Thomas Schnittcher. Diese Version (1.1) ist eine überarbeitete Fassung.

## Inhalt

1. [Voraussetzungen](#1-voraussetzungen)
2. [Einrichtung](#2-einrichtung)
3. [Konfiguration](#3-konfiguration)
4. [Statusvariablen](#4-statusvariablen)
5. [PHP-Funktionen](#5-php-funktionen)
6. [Hinweise](#6-hinweise)
7. [Änderungen](#7-änderungen)

## 1. Voraussetzungen

- IP-Symcon mit PHP 8
- Smarter Coffee V1 im selben Netzwerk, mit fester IP-Adresse (z. B. per DHCP-Reservierung)

## 2. Einrichtung

1. Instanz **TS_SmarterCoffee** anlegen. Der benötigte **Client Socket** wird automatisch mit erstellt.
2. Im Client Socket die **IP-Adresse der Maschine** eintragen und den **Port `2081`** setzen.
3. Client Socket öffnen (aktiv schalten).
4. In der Instanz die gewünschten Werte einstellen, auf *Änderungen übernehmen* klicken und anschließend **Set Config** drücken.

## 3. Konfiguration

| Eigenschaft | Beschreibung | Werte |
|---|---|---|
| Tassen Soll | Anzahl der Tassen | 1 – 12 |
| Stärke | Kaffeestärke | schwach / mittel / stark |
| Filter/Bohnen | Filterkaffee oder gemahlene Bohnen | Filter / Bohnen |
| Zeit Heizplatte | Warmhaltezeit | 0 – 30 Min. in 5er-Schritten |
| Erkennung Kanne | Kannenerkennung der Maschine | Ein / Aus |

**Set Config** sendet die *gespeicherten* Werte an die Maschine. Änderungen im Formular müssen deshalb zuerst mit *Änderungen übernehmen* gespeichert werden.

## 4. Statusvariablen

Bedienbare Variablen:

| Name | Typ | Beschreibung |
|---|---|---|
| Start | Boolean | Brühvorgang starten (mit den Werten der Variablen unten) |
| Stop | Boolean | Brühvorgang stoppen |
| Tassen Soll | Integer | gewünschte Tassenzahl |
| Stärke | Integer | 0 schwach, 1 mittel, 2 stark |
| Filter/Bohnen | Boolean | schaltet die Maschine zwischen Filter und Bohnen um |
| Heizplatte | Boolean | Heizplatte ein/aus |
| Zeit Heizplatte | Integer | Warmhaltezeit in Minuten |

Anzeigevariablen:

| Name | Typ | Beschreibung |
|---|---|---|
| Tassen | Integer | Tassen im laufenden Brühvorgang, im Leerlauf 0 |
| Wasserstand | Integer | 0 leer, 1 niedrig, 2 halb, 3 voll |
| genug Wasser? | Boolean | Wasserstandsflag der Maschine |
| Kanne in Maschine ? | Boolean | Kanne eingesetzt |
| Kaffee fertig | Boolean | Brühvorgang beendet |
| Boiler | Boolean | Boiler aktiv |
| Mahlwerk | Boolean | Mahlwerk aktiv |
| Working | Boolean | Arbeitsbit der Maschine (siehe Hinweise) |
| Status / Status Hex | Integer / String | Rohwert des Statusbytes |
| letzte Meldung | String | Quittung des letzten Kommandos |
| letzte Meldung2 | String | Rückmeldung zu Kannenerkennung und Ein-Tassen-Modus |

## 5. PHP-Funktionen

Präfix: `TSCOF`. `$id` ist die Instanz-ID.

```php
TSCOF_SetConfig(int $id);                    // gespeicherte Konfiguration an die Maschine senden
TSCOF_SetStart(int $id, bool $value);        // Brühvorgang starten
TSCOF_SetStop(int $id, bool $value);         // Brühvorgang stoppen
TSCOF_SetCups(int $id, int $value);          // Tassenzahl setzen (1-12)
TSCOF_SetStrength(int $id, int $value);      // Stärke setzen (0-2)
TSCOF_SetFilterBohnen(int $id, bool $value); // zwischen Filter und Bohnen umschalten
TSCOF_SetHeizplatte(int $id, bool $value);   // Heizplatte ein/aus
TSCOF_SetZeitHeizplatte(int $id, int $value);// Warmhaltezeit in Minuten
TSCOF_parseStatus(int $id, string $data);    // Statuspaket auswerten, liefert ein Array
```

Beispiel: 4 Tassen mittelstark brühen.

```php
$id = 12345; // Instanz-ID
RequestAction(IPS_GetObjectIDByIdent('CupsSoll', $id), 4);
RequestAction(IPS_GetObjectIDByIdent('Strength', $id), 1);
TSCOF_SetStart($id, true);
```

## 6. Hinweise

- `SetStart`, `SetStop` und `SetConfig` enthalten kurze Pausen (`sleep`), weil die Maschine Abstand zwischen Kommandos braucht. Ein Aufruf blockiert den Skript-Thread deshalb für ein bis zwei Sekunden.
- Die Maschine meldet den Status etwa alle zwei Sekunden.
- Das Statusbit „Working“ wird von der Maschine beim Brühen offenbar nicht gesetzt. Zum Erkennen eines laufenden Vorgangs eignen sich „Tassen“ (> 0) und „Boiler“.
- Für die Fehlersuche die Debug-Ausgabe der Instanz aktivieren. Dort stehen alle gesendeten und empfangenen Pakete als Hex.

## 7. Änderungen

### 1.1
- Fehler im Button **Set Config** behoben (`ArgumentCountError`, Formular rief die Funktion mit einem zusätzlichen Parameter auf).
- Statusauswertung mit Bit-Operationen, wird nur noch einmal pro Paket ausgeführt.
- Kompatibel mit PHP 8.2 und neuer (`utf8_encode`/`utf8_decode` ersetzt).
- Debug-Ausgabe für gesendete und empfangene Pakete.
- Zu kurze Pakete werden abgefangen, Typen und Wertebereiche werden geprüft.

### 1.0
- Erste Version.
