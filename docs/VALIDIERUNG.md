# Prüfbericht 1.4.1 – 27. Oktober 2026

Zielsystem: IP-Symcon ab 9.0, WLANThermo Mini V3 und dessen unterstützte Fühler. Die Firmwareanbindung bleibt gegenüber 1.3.0 erhalten. Die neue Kachel verwendet das offizielle HTML-SDK ohne zusätzliche Statusvariablen.

## Gemeldeter Fehler und Regression 1.4.1

Der Screenshot des Zielsystems meldete einen nicht unterstützten Werttyp in `libs/Visualization.php`, Zeile 131 der Version 1.4.0. Dort wurde ein PHP-Array direkt an UpdateVisualizationValue übergeben. Die bisherige Simulation akzeptierte Arrays und konnte diesen Fehler deshalb nicht erkennen.

Die Simulation wurde zuerst um die native Typgrenze erweitert. Mit dem unveränderten 1.4.0-Laufzeitcode brach der Test daraufhin an exakt diesem Aufruf mit „Cannot auto-convert value for parameter Value (Type is not supported)“ ab. Nach Umstellung beider Sendepfade auf JSON-Text bestanden alle Prüfungen. Der JavaScript-Empfänger unterstützt JSON-Text bereits; eine Änderung der Kachel war dafür nicht erforderlich.

Zusätzlich geprüft: sämtliche gesendeten Nachrichten sind Strings; Deaktivierung/Fehlerquittierung enthalten gültiges JSON; eine neue Instanz ohne Host initialisiert sich mit Diagnose 201 und null Variablen; eine deaktivierte Instanz initialisiert sich mit Status 104. Ein erneuter Test auf dem echten Symcon-System steht aus.

## Ausgeführte Prüfungen

- **164 erfolgreiche PHP-Modulprüfungen**, einschließlich der bisherigen 124 Prüfungen. Neu: null Variablen auf neuen Instanzen, empfohlene Auswahl, Migration bestehender Instanzen, explizite Bereinigung, externe Ziele mit Referenzen/Typprüfung/Schreibschutz, Kachelaktionen und Rücklesebestätigung, serverseitiger Schreibschutz, ausgeblendete Kanäle, Offline-Livedaten und sichere JSON-Einbettung.
- **15 erfolgreiche JavaScript-Vertragsprüfungen** mit Ausführung der echten tile.js in Node.js und einem kleinen DOM-Vertrag. Initialanzeige, Fühlerwahl per Tastatur, strukturierte SDK-Nachrichten, Doppelsendsperre, Erfolg/Fehler, manuelle Pitmaster-Regelung, Offlinezustand, Design-/Kompaktkonfiguration, entfernte Kanäle, sichere Texte, Timeout und Deaktivierung.
- PHP-Syntax aller acht PHP-Dateien erfolgreich, einschließlich beider Traits und des neuen Tests. PHP 8.3.6, ausschließlich nach /tmp entpackte Testlaufzeit; keine Systeminstallation.
- JavaScript-Syntax der Kachel und der isolierten Gesprächsvorschau mit Node.js geprüft.
- JSON-/Strukturprüfung: neun JSON-Dateien einschließlich tatsächlich erzeugter Formulare und Kacheldaten gültig. Modul-/Bibliotheks-GUIDs, Klassennamen, Präfix, Typ, Formularnamen, registrierte Eigenschaften und Referenz auf die explizit bestätigte Löschfunktion geprüft. In der normalen Variablenregistrierung gibt es keinen Löschaufruf.
- Die HTTP-Implementierung in libs/Api.php ist gegenüber der bereits in dieser Sitzung mit acht echten HTTP-Tests geprüften Version unverändert. Getrennt geprüft wurden GET, Basic/Digest-Anmeldung, UTF-8-POST, HTTP 401, Redirect, HTTP 404 und Timeout gegen einen lokalen Testserver.
- Die Vorschau verwendet das tatsächlich von GetVisualizationTile gelieferte HTML mit repräsentativen Testdaten. Die Gesprächsvorschau begrenzt zusätzlich CSS und DOM-Zugriffe auf ihren Wurzelknoten; ihre Darstellung verwendet dieselben Quelldateien.

Die PHP-Simulation bildet ausgewählte Symcon-Verträge nach. Die JavaScript-Simulation prüft Verhalten und Nachrichten, nicht das CSS-Layout oder die tatsächliche Symcon-Kommunikation. Keine dieser Simulationen ersetzt die reale Laufzeit. Der Vollbildzweig wird mit verfügbarer Typ-Konstante getestet; ein echter Symcon-9.1-Host wurde nicht verwendet.

## Grenzen und nicht ausgeführte Prüfungen

- Die automatische Browser-Sicherheitsprüfung hat das Öffnen der lokalen Vorschau per file:// abgelehnt. Es wurde kein Browser-Screenshot und kein erfolgreicher Responsivitätstest daraus abgeleitet. CSS enthält Regeln für schmale, mittlere, breite und niedrige Kacheln sowie Touch und reduzierte Animation; deren tatsächliche Darstellung muss am Zielsystem geprüft werden.
- Keine laufende Symcon-Installation und kein realer Mini V3/MEATER erreichbar. Daher kein Nachweis für tatsächliche Clientdarstellung, Hardwarekommunikation dieser Version oder echten Lizenz-/Archivzustand.
- Keine vollständige Validierung gegen aktuelle offizielle JSON-Schemas und keine Store-Freigabe.
- Symcon 9.0 unterstützt den normalen HTML-Typ. Der dokumentierte HTML-Vollbildtyp ist erst ab 9.1 verfügbar und wird nur bei vorhandener Konstante eingesetzt. Maximierte individuelle HTML-Darstellung unter 9.0 wird nicht zugesichert.
- Der mitgelieferte Beispielkatalog enthält 120 Felder einschließlich drei Raw-Feldern. Das ist kein allgemeiner Maximalwert: Der reale Katalog hängt von Firmware, Fühlerzahl und zusätzlichen API-Feldern ab. Historische, zeitweise fehlende Felder bleiben im Katalog, um gespeicherte Entscheidungen zu erhalten.
- Die Firmwaregrundlage wird aus der Ausgangsdokumentation übernommen: offizielles ESP32-Repository, Commit 06d456ca232bc26784d3349f79f222de41436921. Insbesondere positionsbasierter Pitmaster-Payload und Bluetooth-Rückleseprüfung bleiben unverändert. Eine erneute Prüfung dieses Firmwarestands oder neuerer Firmware war nicht Bestandteil der Kacheländerung.

## Wiederholung

```bash
find WLANThermo libs tests -name '*.php' -print0 | xargs -0 -n1 php -l
php -n tests/run.php
python3 tests/structure.py
node --check WLANThermo/tile.js
node tests/tile-ui.cjs
python3 tests/http_transport.py php
```

HTTP-Tests benötigen PHP-cURL; die Modul- und JavaScript-Tests benötigen keine externen Bibliotheken. Die Laufzeit des Moduls selbst verwendet keine Node-/Python-Abhängigkeiten. GitHub Actions enthält die Prüfungen. TESTAUSGABE.txt enthält die Modul- und JavaScript-Ergebnisse.

## Prüfplan für das Zielsystem

1. Vorhandene Instanz über Module Control aktualisieren, GUIDs und Objekt-IDs vergleichen; keine automatische Datenlöschung erwarten. Neue Testinstanz mit null Variablen erstellen.
2. Host konfigurieren und individuelle Kachel in der Kachelvisualisierung aktivieren. Initialanzeige, erneutes Öffnen sowie Datenaktualisierung ohne Neuladen prüfen.
3. Kachelgrößen auf Smartphone, Tablet, Desktop und Wandpanel prüfen: kein horizontaler Überlauf, gut erreichbare Bedienelemente. Helles/dunkles/automatisches Farbschema, Kompaktmodus, Akzentfarbe, Touch, Tastatur und reduzierte Bewegung testen.
4. Hauptfühler wählen, Alarmgrenzen und Pitmaster ändern und die Rücklesebestätigung am Gerät vergleichen. Globalen Schreibschutz, Kachelbedienfreigabe und ausgeblendete Kanäle testen.
5. Gerät ausschalten: Standby, ungültige Messwerte und gesperrte Bedienung. Nach Wiedereinschalten automatische Erholung prüfen. Authentifizierungs- und API-Fehler getrennt testen.
6. Nur für Archiv/Automation benötigte Variablen aktivieren. Abwählen bewahrt ID und letzten Wert. Wiederaktivieren stellt dieselbe Variable wieder her. Ausgeblendete Variablen zählen weiter zur Lizenz.
7. Externes beschreibbares Datenziel gleichen Typs zuweisen: keine zusätzliche Modulvariable, vorhandener Wert wird aktualisiert; Zielprofil und -aktion bleiben gleich. Falschen Typ und schreibgeschützte Ziele prüfen.
8. Gesonderte Löschbestätigung ausschließlich an entbehrlichen Testvariablen prüfen. Externe Ziele und aktive eigene Variablen müssen erhalten bleiben. Gelöschte Variablen erhalten bei späterer Neuanlage neue IDs.
9. Neustart: gespeicherte Auswahl, Kacheldesign, Referenzen und Datenabruf prüfen. Maximierte HTML-Kachel nur mit unterstütztem Vollbildtyp ab 9.1 prüfen.

Relevante aktuelle offizielle Symcon-Seiten und die technischen Entscheidungen sind in README.md verlinkt.
