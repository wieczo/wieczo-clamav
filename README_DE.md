# Wieczo's Virus Scan

**Wieczo's Virus Scan** ist ein WordPress-Plugin, das es ermöglicht, Dateien direkt aus dem WordPress-Admin-Bereich an einen ClamAV-Dienst zu senden und sie auf Viren und Malware zu scannen. 

Des Weiteren sendet das Plugin jeden Upload zuerst an ClamAV. Im Falle eines Viruses wird der Upload abgebrochen.

Das Plugin stellt eine Verbindung zu einem ClamAV-Dienst her, indem es die in den Plugin-Einstellungen konfigurierten **Host** und **Port**-Variablen verwendet.

## Features

- **Datei-Upload**: Erlaubt das Hochladen von Dateien über eine benutzerfreundliche Admin-Seite.
- **Upload-Scan**: Scannt alle hochgeladenen Dateien auf Viren und Malware.
- **ClamAV Integration**: Sendet Dateien über einen Socket an einen ClamAV-Dienst zum Virenscan.
- **Einfache Konfiguration**: Konfiguriere den Host und den Port des ClamAV-Dienstes in den Plugin-Einstellungen.

## Installation

### Manuelle Installation:

1. Lade das Plugin-Verzeichnis `wieczo-clamav` in das Verzeichnis `/wp-content/plugins/` deiner WordPress-Installation hoch.
2. Aktiviere das Plugin im WordPress Admin-Bereich unter "Plugins".
3. Konfiguriere den ClamAV-Dienst unter **Einstellungen -> ClamAV Einstellungen** (Host und Port des ClamAV-Dienstes eintragen).

## Konfiguration

Nach der Aktivierung des Plugins kannst du die Verbindungsinformationen für deinen ClamAV-Dienst wie folgt konfigurieren:

1. Gehe zu **Einstellungen -> ClamAV Scanner**.
2. Trage den **Host** und den **Port** deines ClamAV-Dienstes ein. Beispielsweise:
    - **Host**: `127.0.0.1` (falls der Dienst lokal läuft)
    - **Port**: `3310` (Standard-Port für ClamAV)

3. Optional kannst du ein **Timeout** festlegen, um die maximale Wartezeit für die Verbindung zum ClamAV-Dienst zu bestimmen.

## Nutzung

### 1. Upload-Scan

Sobald das Plugin aktiv ist, scannt es automatisch alle Uploads auf Viren.

### 2. Datei hochladen und scannen

1. Navigiere zu **ClamAV Scanner -> Datei-Scanner** im WordPress-Admin-Menü.
2. Wähle eine Datei aus, die du auf Viren scannen möchtest, und klicke auf "Datei scannen".
3. Das Plugin sendet die Datei über einen Socket an den ClamAV-Dienst, und du erhältst das Scan-Ergebnis direkt im Admin-Dashboard.

## Funktionsweise

- Das Plugin verwendet die PHP `socket`-Funktionalität, um eine Verbindung zu einem ClamAV-Dienst herzustellen.
- Dateien, die über das WordPress-Admin-Interface hochgeladen werden, werden vorübergehend auf dem Server gespeichert.
- Das Plugin öffnet dann eine Socket-Verbindung zu dem konfigurierten ClamAV-Host und -Port und sendet die hochgeladene Datei zur Analyse.
- Nach dem Scan zeigt das Plugin die Scan-Ergebnisse auf der Admin-Seite an.

## Voraussetzungen

- WordPress 5.0 oder höher
- Ein laufender **ClamAV-Dienst**, der über einen Netzwerk-Socket erreichbar ist (Standard-Port: 3310).
- PHP Sockets müssen auf dem Server aktiviert sein.

## Entwicklung

### Lokale Entwicklung

1. Klone dieses Repository in das `/wp-content/plugins/`-Verzeichnis deines lokalen WordPress-Projekts:
   ```bash
   git clone https://github.com/wieczo/wieczo-clamav.git
