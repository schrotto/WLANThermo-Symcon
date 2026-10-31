# Lieferumfang A–I – WLANThermo 1.6.0

## A. Modulbeschreibung

Lokale Einbindung des WLANThermo Mini V3 mit dessen Temperatur-/Bluetooth-/MEATER-Kanälen und Pitmaster. Eigene interaktive Grill-Kachel, individuelle optionale Variablen, vorhandene Datenziele und gesondert bestätigte Bereinigung. Mindestversion IP-Symcon 9.0, PHP-cURL, Netzwerkzugriff auf das Gerät. HTML-Vollbild nur bei unterstütztem Typ ab 9.1.

## B. Modulstruktur

```text
library.json
LICENSE
README.md
.gitignore
.github/workflows/ci.yml
WLANThermo/
  module.json
  module.php
  form.json
  module.html
  tile.css
  tile.js
libs/
  Api.php
  VariableManagement.php
  Visualization.php
  Forecast.php
docs/
  CHANGELOG.md
  KACHEL-VORSCHAU.html
  LIEFERUMFANG.md
  QUELLCODE.md
  STORE.md
  TESTAUSGABE.txt
  VALIDIERUNG.md
  VARIABLEN.md
tests/
  Runtime.php
  run.php
  premium.php
  forecast.php
  structure.py
  tile-ui.cjs
  http_transport.py
  transport.php
  fixtures/
    data.json
    settings.json
    bluetooth.json
```

## C. Vollständiger Quellcode

Alle Dateien sind im Paket vollständig enthalten. QUELLCODE.md enthält zusätzlich den vollständigen Inhalt sämtlicher Laufzeit-/Konfigurationsdateien, README und Lizenz als zusammenhängende Quellcodeausgabe. HTML, CSS und JavaScript werden aus lokalen Modulressourcen in GetVisualizationTile eingebettet. Keine zusätzlichen Webserverpfade, Webhooks, Skripte oder HTML-Boxen erforderlich. Die deutsche Oberfläche benötigt keine locale.json.

## D. Konfigurationsformular

Allgemeine Einstellungen mit Aktivierung, Host/Anmeldung/Intervall; Geräte und Datenquellen mit Bluetooth; lizenzoptimierte Variablentabelle mit Gruppen, Standard und Sammelauswahl; Premium-Visualisierung mit Design und Fühlerauswahl; erweiterte Einstellungen mit Timeout/Schreibschutz/Rohdaten; Diagnose mit Verbindungstest, manueller Aktualisierung, Fehlerhinweis und gesonderter Bereinigung. Vollständige Beschreibung in README.md.

## E. Variablenverwaltung

Alle angebotenen Variablen sind optional. VARIABLEN.md beschreibt stabile Idents, Typen, Einheiten, Funktionen und automatische Darstellungen und listet den vollständigen Beispielkatalog. Reale API-Felder werden dynamisch ergänzt. Vorhandene externe Datenziele werden typgeprüft; die Kachel bleibt von dieser Auswahl unabhängig.

## F. Lizenzoptimierung

Neue Instanz: **0 zusätzliche Statusvariablen**, auch bei vollständig bedienbarer Kachel. Empfohlen: **1 + 2 × Anzahl Temperaturkanäle** (Online sowie Temperatur/Gültigkeit). Bei drei Testkanälen sieben Variablen. Der Beispielkatalog bietet 120 Felder; mit allen Gruppen und Rohdaten können in diesem Beispiel 120 eigene Variablen entstehen. Ein allgemeiner Maximalwert ist wegen dynamischer Gerätefelder nicht seriös festlegbar. Bestehende ausgeblendete Variablen zählen weiter; erst die ausdrücklich bestätigte Bereinigung gibt ihre Plätze frei. Vorhandene externe Datenziele vermeiden Doppelanlagen, zählen aber selbstverständlich weiterhin zur bestehenden Symcon-Lizenz.

## G. Premium-Visu-Kachel

Grillstation mit optionaler großer Haupttemperatur (standardmäßig aus), ausschließlich gültigen Fühlerkarten, Obergrenzen und Pitmaster. Kreis und Balken zeigen Temperaturpositionen von 0 bis zur positiven Obergrenze, keinen erfundenen Garfortschritt. Direkte Bedienung von Obergrenzen, Pitmaster und Bluetooth, dazu Aktualisieren. Daten direkt aus Modulbuffers als JSON-Text über das HTML-SDK, ohne Hilfsvariablen. Designmodi, Akzent, kompakte Ansicht, Details, Icons, Animation und Fühlerauswahl dauerhaft konfigurierbar. Die Vorschau verwendet denselben Code mit Testdaten, ohne Geräteverbindung. Responsives CSS vorhanden; Browser- und Symcon-Layoutprüfung noch offen.

## H. Installation

README.md enthält die vollständige Installation/Update-Anleitung für Module Control sowie das Einrichten der Kachel. Der Bibliotheksinhalt gehört in ein Git-Repository mit library.json im Hauptverzeichnis. Danach in Module Control dessen URL eintragen, die WLANThermo-Instanz erstellen bzw. beibehalten, Host konfigurieren, Kachel aktivieren und die Instanz in der Kachelvisualisierung anzeigen. Eine direkte Dateikopie erfordert den dokumentierten Dienstneustart. Keine Veröffentlichung wurde in dieser Sitzung durchgeführt.

## I. Tests und Dokumentation

218 PHP-Modulprüfungen und 38 JavaScript-Vertragsprüfungen erfolgreich, dazu PHP-/JavaScript-Syntax und JSON-/Strukturprüfung. Die unveränderte HTTP-Implementierung wurde in dieser Sitzung separat mit acht echten HTTP-Prüfungen getestet. VALIDIERUNG.md trennt diese Nachweise von offenen Prüfungen in Browser, Symcon und Hardware. README.md verlinkt die aktuelle offizielle Dokumentation für Modulklasse, Darstellung, Variablenverwaltung und HTML-SDK.

IPSView wird über das native TileHtml mit der WLANThermo-Instanz-ID angebunden. Keine zusätzliche Variable erforderlich. Die erweiterte HTML-Vollbildoption bleibt dafür aus. Einzelheiten in README.md. Tatsächlicher IPSView-Clientbetrieb bleibt zu prüfen.

Seit 1.4.3 enthält die Kachel ausschließlich die Obergrenze. Bestehende Min-Variablen bleiben erhalten; seit 1.5.1 kann die Kachel kollidierende Geräte-Untergrenzen bei Max-Änderungen automatisch anpassen. Seit 1.5.0 ist eine konservative Restzeitschätzung mit Zielzeitpunkt, internem Verlauf und Grafik enthalten. Keine zusätzlichen Variablen erforderlich. Neustart und unbrauchbare Verläufe führen zu einer neuen Lernphase; echte Prognosegüte muss am Gerät geprüft werden.

Die vollständige Umsetzung der Prognose liegt in libs/Forecast.php: lineare Ausgleichsgerade, Plausibilitätsfilter, begrenzte Messreihen und Modulintegration. Die Kachel zeigt Schätzungen mit ausdrücklicher Kennzeichnung und unterscheidet gemessene von fortgeschriebenen Werten. Einstellungen und Grenzen stehen in README.md.

Version 1.5.1 zeigt die Restzeit als kompakte geschlossene Detailzeile. Grafik und Reset bleiben durch Aufklappen erreichbar. Der Fortschrittsbalken hat weiche Übergänge und einen abschaltbaren Lichtlauf. Kacheländerungen passen bei Bedarf eine zu hohe Untergrenze automatisch mit an; die allgemeine Skriptfunktion bleibt strikt.

Version 1.5.2 zeigt die geschätzte Restdauer und den voraussichtlichen Zielzeitpunkt im optionalen Hauptbereich. Die Testdaten-Vorschau blendet diesen Bereich ein. Aktueller Prüfumfang: 218 PHP- und 40 JavaScript-Vertragsprüfungen.

Version 1.6.0: gemeinsame MEATER-Kachel für Kerntemperatur/Garraumtemperatur. Nur der Kern bietet Ziel, Restzeit und Obergrenzenbedienung. Eindeutige Namen oder explizite MEATER-Zuordnung verbinden die Messstellen. Geräte- und Variablennamen bleiben erhalten. 230 PHP- und 48 JavaScript-Vertragsprüfungen erfolgreich; reale IPSView-/Geräteprüfung offen.
