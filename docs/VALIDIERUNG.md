# Prüfbericht 1.6.2 – 31. Oktober 2026

Zielsystem: IP-Symcon ab 9.0, WLANThermo Mini V3 und dessen unterstützte Fühler. Die Firmwareanbindung bleibt gegenüber 1.3.0 erhalten. Die neue Kachel verwendet das offizielle HTML-SDK ohne zusätzliche Statusvariablen.

## Änderungen und Nachweise 1.6.2

236 PHP- und 60 JavaScript-Vertragsprüfungen erfolgreich. Der neue Fühler-Grenzwert steht in der nativen Summary. Der Vertrag prüft den vollständigen sichtbaren Grenzwert bei geschlossenem Editor, Erhalt offener Eingaben bei Live-Daten und dauerhafte Wertanzeige bei verborgenem/geschlossenem Editor im Schreibschutz. Kern-/Garraum-Trennung, Kopfkanal ohne Doppelanzeige, Schreibaufträge und Prognose bleiben abgedeckt. Die bisherigen Kopfkachel-Editorprüfungen entfallen mit der ausdrücklich entfernten Kopfbedienung.

Titel/Kopftemperatur/Geräteinformationen stehen im Kopfbereich. Native Grenz-Details werden per CSS-Grid nach oben angeordnet; nur das Formular klappt auf. Kein Browserlayout- oder echtes IPSView-Ergebnis wird aus DOM-Vertragsprüfungen abgeleitet. Reale Zielprüfung: breite und schmale Kachel, aufgeklappte Eingabe, Schreibschutz, MEATER-Paar, Kopfkanal ohne gültigen Wert und Gerätedetails ausgeschaltet.

## Änderungen und Nachweise 1.6.1

236 PHP- und 61 JavaScript-Vertragsprüfungen erfolgreich. Neue Konfigurationsfelder sind registriert und im Formular vorhanden. PHP prüft Titel/Kopfanzeige-Standard, eigene Beschriftungen und Kanalnummer, Ausschluss gesperrter Messwerte, ungültige Kanalnummer, überlange Texte und Standardtexte bei leeren Eingaben. Keine zusätzlichen Statusvariablen.

JavaScript prüft Titel/Temperaturausgabe, Vermeidung doppelter Kanäle und passende Übersichtszähler, Rückkehr nach Abschalten, fehlenden/ungültigen Kopfkanal, Wegfall einer leeren Übersicht, Kopfkanal aus einem MEATER-Paar, sichere Textausgabe, erhaltene offene Eingabe, Schreibauftrag für die gewählte Kanalnummer, Verwerfen beim Kanalwechsel, Garraum ohne Eingabe und Schreibschutz.

CSS ordnet Titel, Kopfkachel und Verbindung auf breiten Flächen nebeneinander an; unter 700 Pixeln steht die Kopfanzeige in einer eigenen Zeile. Die Tests sind DOM-Vertragsprüfungen und bestätigen kein Browserlayout. Echte IPSView-Darstellung und Gerätebedienung bleiben am Zielsystem zu prüfen. Die Vorschau verwendet tatsächlichen Kachelcode mit synthetischen Messwerten.

## Änderungen und Nachweise 1.6.0

230 PHP- und 48 JavaScript-Vertragsprüfungen erfolgreich. Neu geprüft: Rollen/Paar-ID aus eindeutigen Namen; keine Paarung unterschiedlicher oder doppelter Namen; keine Vermutung für anonyme Bluetooth-Kanäle; ausdrückliche Zuordnung; Ablehnung doppelter, identischer und leer benannter Zuordnungen; Ausschluss ungültiger/ausgeblendeter Messstellen; serverseitige Garraum-Schreibsperre; unveränderte Originalnamen.

JavaScript prüft gemeinsame Anzeige beider Werte, genau ein Kern-Obergrenzenformular, korrekte Kernkanalnummer im Schreibauftrag, Garraum ohne Kern als reine Anzeige, Garraum-Hauptanzeige ohne Zielwarnung/Prognose, verschwundenen Garraumsensor, zwei MEATER bei vertauschter Reihenfolge und getrennte Anzeige ohne Paar-ID. Keine neue Browserlayout-/IPSView-/Geräteprüfung. Die neue Konfigurationsliste wird von der Formular-/Eigenschaftsstrukturprüfung erfasst.

Die offizielle Firmware wurde am dokumentierten Commit 06d456ca232bc26784d3349f79f222de41436921 geprüft: API::channelAry liefert keine Adresse/lokale Messstellen-ID; TemperatureGrp::add/remove verändert die Kanalliste dynamisch. Deshalb gibt es keine automatische Zuordnung nach Kanalnummern. Eindeutige Namenspaare oder ausdrückliche Konfiguration bilden die Zuordnungsgrundlage.

Am Zielsystem prüfen: zwei echte MEATER mit eindeutigen Namen oder expliziter Zuordnung, einzelne Messstellen aus-/einschalten, Bluetooth-Verbindungsabbruch, schmale IPSView-Kachel, Hauptanzeige für Kern bzw. Garraum sowie Kern-Obergrenze schreiben. Die berechnete Garzeit bleibt eine Trend-Schätzung.

## Änderungen und Nachweise 1.5.2

218 PHP- und 40 JavaScript-Vertragsprüfungen erfolgreich. Zusätzlich geprüft: separate Zielzeit im Hauptbereich bei gültiger Prognose und Entfernung der alten Zielzeit/geschätzten Hervorhebung bei Plateau. Der isolierte Vorschaucode besteht ebenfalls denselben JavaScript-Vertrag. Keine neue Browserlayout-, IPSView- oder Geräteprüfung. Die Vorschau zeigt die optionale Hauptanzeige mit synthetischen Daten; der Modulstandard bleibt unverändert.

## Änderungen und Nachweise 1.5.1

218 PHP- und 38 JavaScript-Vertragsprüfungen erfolgreich. Neue Grenzfälle: aktueller Geräte-Min-Wert weicht vom Kachelcache ab; Min wird bei Kollision auf neue Max minus ein Grad gesetzt; beide Werte stehen in einem POST; Bestätigung nennt die Anpassung; am API-Limit -30 sind beide Werte gleich; bereits gleiche Grenzen werden nicht unnötig geändert; Schreibschutz, allgemeine Skriptschnittstelle, Dezimaleingaben und fehlgeschlagene Übernahme bleiben abgesichert.

Die Geräteoperation wurde in einen gemeinsamen internen Pfad aufgeteilt. Nur der Kachelpfad aktiviert die automatische Min-Anpassung. Validierung und Auswahl der korrigierten Werte erfolgen nach frischem Lesen unter derselben Semaphore wie Schreiben und Rücklesen.

JavaScript prüft die standardmäßig geschlossene native Detailzeile, Erhalt des geöffneten Zustands bei Live-Daten, kompakte Restzeit/Zielzeit, weiterhin verfügbare Grafik/Reset sowie Aktivierung und Stoppen des Animationszustands bei Prognose bzw. Plateau. CSS implementiert weiche Breitenänderungen und einen Lichtlauf mit Abschaltung über Moduloption und reduzierte Bewegung. Ein DOM-Vertragstest bestätigt keine gerenderte Animation oder tatsächliche Kachelhöhe. Die visuelle Prüfung auf den Zielgeräten bleibt offen.

## Änderungen und Nachweise 1.5.0

Die Prognoseprüfungen aus 1.5.0 sind weiterhin enthalten. Zusätzlich geprüft: bekannte lineare Restzeit (72→75 °C in zehn Minuten, Ziel 82 °C: etwa 23 Minuten 20 Sekunden), gleiche Prognose in Fahrenheit, Lernphase, erreichte/fehlende Obergrenze, Plateau, Abkühlung, neu eintretendes Plateau, verrauschte Verläufe, veraltete Daten, Fühlerwechsel, lange Unterbrechungen, abrupte Sprünge, rückwärts laufende Uhr, schneller Mehrfachabruf, ferne Prognosen und Speicherbegrenzung.

Modulintegration wird mit kontrollierter Uhr und tatsächlichen simulierten Geräteabrufen geprüft: keine Hilfsvariablen, Zieländerung ohne Verlust der Temperaturreihe, Reset ohne Geräte-POST und ohne künstliche Messpunkte, gesperrte Bedienung, Diagramm aus, ungültiger/wieder verbundener Fühler, Prognose aus und falsches Trendfenster. Die Vorschau wird aus wirklich durch die Implementierung berechneten synthetischen Testreihen erzeugt; sie ist keine Geräteaufzeichnung.

JavaScript prüft Restzeittext und Zielzeit, Trend, gültige SVG-Koordinaten für Messung/Ziel/Prognose, Startwertfortschritt, getrennte Diagrammabschaltung, Entfernung alter Prognosen bei Plateau, internes Reset-Kommando und vollständiges Ausblenden. Das bleibt ein DOM-Vertragstest; tatsächliche Browser-/IPSView-Darstellung wurde nicht bestätigt.

Die Buffergrenzen werden durch getrennte Fühlerbuffer mit höchstens 361 Punkten eingehalten; Diagramme erhalten maximal 48 echte Punkte. Der Versandpuffer speichert nur noch den Hash der letzten Nachricht statt einer Kopie sämtlicher Verläufe. Kein Archiv oder zusätzliche Variable wird angelegt.

Die Tests bestätigen Rechen- und Fehlerbehandlung, nicht die Prognosegüte bei echtem Gargut. Eine lineare Trendfortschreibung ist keine physikalisch vollständige Garzeitberechnung und keine Garantie. Zukünftige Heizleistungsänderungen, Nachgaren und Temperaturplateaus können nicht vorhergesagt werden.

## Änderungen und Nachweise 1.4.3

Zusätzlich geprüft: Die Kachel schreibt ausschließlich max und erhält den Min-Wert am Gerät. Der Min-Wert wird nicht mehr im Kachel-Datenpaket übertragen; veraltete Kachelanfragen mit min werden ohne Geräteschreibzugriff abgewiesen. Untergrenzenalarme werden in der Kachel nicht als Obergrenzenüberschreitung dargestellt. Die echte tile.js wurde zusätzlich auf ein Formular mit nur einem Obergrenzenfeld, max-only-Nachrichten, entsprechend beschriftete Hauptanzeige und ausgeblendete Verhältnisgrafiken bei nichtpositiven Obergrenzen geprüft. Die bisherige Skript-/Variablenverwaltung bleibt erhalten.

Kein neuer Test mit einer laufenden Symcon-/IPSView-Installation oder realer Hardware. Dieser Absatz beschreibt die damalige Änderung 1.4.3; die Restzeitschätzung ist seit 1.5.0 ergänzt. Balken und Ring sind reine Temperaturanzeigen, keine Zeitprognosen.

## Änderungen und Nachweise 1.4.2

Zusätzlich geprüft: Fehlerwert 999 und getrennte Bluetooth-Fühler werden aus dem Kachel-Datenpaket entfernt, Null Grad bei kabelgebundenem Fühler bleiben gültig, Variablen-IDs bleiben erhalten, unzulässige Kachelaktionen auf ungültige Fühler werden abgewiesen, Fühler verschwinden bei Geräteausfall und kehren bei Erholung zurück. Große Hauptanzeige ist standardmäßig aus, kann live eingeschaltet werden und ersetzt keinen fehlenden festen Hauptfühler. JavaScript filtert ungültige Daten ebenfalls, entfernt Karten aus dem DOM und zeigt bei fehlenden Daten einen Hinweis.

IPSView: Die primäre Herstellerdokumentation zu TileHtml wurde geprüft. Die Instanz verwendet standardmäßig den normalen HTML-SDK-Typ. Der Vollbildtyp wird nur noch bei ausdrücklicher Konfiguration und verfügbarer Konstante verwendet. Beide Typauswahlen sind im Modulvertrag getestet. Kein tatsächlicher IPSView-Client war verbunden; daraus wird keine erfolgreiche Clientprüfung abgeleitet. Einbindungsschritte und Herstellerquelle stehen in README.md.

## Gemeldeter Fehler und Regression 1.4.1

Der Screenshot des Zielsystems meldete einen nicht unterstützten Werttyp in `libs/Visualization.php`, Zeile 131 der Version 1.4.0. Dort wurde ein PHP-Array direkt an UpdateVisualizationValue übergeben. Die bisherige Simulation akzeptierte Arrays und konnte diesen Fehler deshalb nicht erkennen.

Die Simulation wurde zuerst um die native Typgrenze erweitert. Mit dem unveränderten 1.4.0-Laufzeitcode brach der Test daraufhin an exakt diesem Aufruf mit „Cannot auto-convert value for parameter Value (Type is not supported)“ ab. Nach Umstellung beider Sendepfade auf JSON-Text bestanden alle Prüfungen. Der JavaScript-Empfänger unterstützt JSON-Text bereits; eine Änderung der Kachel war dafür nicht erforderlich.

Zusätzlich geprüft: sämtliche gesendeten Nachrichten sind Strings; Deaktivierung/Fehlerquittierung enthalten gültiges JSON; eine neue Instanz ohne Host initialisiert sich mit Diagnose 201 und null Variablen; eine deaktivierte Instanz initialisiert sich mit Status 104. Ein erneuter Test auf dem echten Symcon-System steht aus.

## Ausgeführte Prüfungen

- **218 erfolgreiche PHP-Modulprüfungen**, einschließlich der bisherigen 124 Prüfungen. Neu: null Variablen auf neuen Instanzen, empfohlene Auswahl, Migration bestehender Instanzen, explizite Bereinigung, externe Ziele mit Referenzen/Typprüfung/Schreibschutz, Kachelaktionen und Rücklesebestätigung, serverseitiger Schreibschutz, ausgeblendete Kanäle, Offline-Livedaten und sichere JSON-Einbettung.
- **38 erfolgreiche JavaScript-Vertragsprüfungen** mit Ausführung der echten tile.js in Node.js und einem kleinen DOM-Vertrag. Initialanzeige, Fühlerwahl per Tastatur, strukturierte SDK-Nachrichten, Doppelsendsperre, Erfolg/Fehler, manuelle Pitmaster-Regelung, Offlinezustand, Design-/Kompaktkonfiguration, entfernte Kanäle, sichere Texte, Timeout und Deaktivierung.
- PHP-Syntax aller zehn PHP-Dateien erfolgreich, einschließlich beider Traits und des neuen Tests. PHP 8.3.6, ausschließlich nach /tmp entpackte Testlaufzeit; keine Systeminstallation.
- JavaScript-Syntax der Kachel und der isolierten Gesprächsvorschau mit Node.js geprüft.
- JSON-/Strukturprüfung: neun JSON-Dateien einschließlich tatsächlich erzeugter Formulare und Kacheldaten gültig. Modul-/Bibliotheks-GUIDs, Klassennamen, Präfix, Typ, Formularnamen, registrierte Eigenschaften und Referenz auf die explizit bestätigte Löschfunktion geprüft. In der normalen Variablenregistrierung gibt es keinen Löschaufruf.
- Die HTTP-Implementierung in libs/Api.php ist gegenüber der bereits in dieser Sitzung mit acht echten HTTP-Tests geprüften Version unverändert. Getrennt geprüft wurden GET, Basic/Digest-Anmeldung, UTF-8-POST, HTTP 401, Redirect, HTTP 404 und Timeout gegen einen lokalen Testserver.
- Die Vorschau verwendet das tatsächlich von GetVisualizationTile gelieferte HTML mit repräsentativen Testdaten. Die Gesprächsvorschau begrenzt zusätzlich CSS und DOM-Zugriffe auf ihren Wurzelknoten; ihre Darstellung verwendet dieselben Quelldateien.

Die PHP-Simulation bildet ausgewählte Symcon-Verträge nach. Die JavaScript-Simulation prüft Verhalten und Nachrichten, nicht das CSS-Layout oder die tatsächliche Symcon-Kommunikation. Keine dieser Simulationen ersetzt die reale Laufzeit. Der Vollbildzweig wird mit verfügbarer Typ-Konstante getestet; ein echter Symcon-9.1-Host wurde nicht verwendet.

## Grenzen und nicht ausgeführte Prüfungen

- Die automatische Browser-Sicherheitsprüfung hat das Öffnen der lokalen Vorschau per file:// abgelehnt. Es wurde kein Browser-Screenshot und kein erfolgreicher Responsivitätstest daraus abgeleitet. CSS enthält Regeln für schmale, mittlere, breite und niedrige Kacheln sowie Touch und reduzierte Animation; deren tatsächliche Darstellung muss am Zielsystem geprüft werden.
- Keine laufende Symcon-Installation und kein realer Mini V3/MEATER erreichbar. Daher kein Nachweis für tatsächliche Clientdarstellung, Hardwarekommunikation dieser Version oder echten Lizenz-/Archivzustand.
- Keine vollständige Validierung gegen aktuelle offizielle JSON-Schemas und keine Store-Freigabe.
- Symcon 9.0 unterstützt den normalen HTML-Typ. Der dokumentierte HTML-Vollbildtyp ist erst ab 9.1 verfügbar und wird nur bei vorhandener Konstante und aktivierter Vollbildoption eingesetzt. Maximierte individuelle HTML-Darstellung unter 9.0 wird nicht zugesichert.
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

HTTP-Tests benötigen PHP-cURL; die Modul- und JavaScript-Tests benötigen keine externen Bibliotheken. Die Laufzeit des Moduls selbst verwendet keine Node-/Python-Abhängigkeiten. tests/forecast.php wird durch tests/run.php automatisch ausgeführt. GitHub Actions enthält die Prüfungen. TESTAUSGABE.txt enthält die Modul- und JavaScript-Ergebnisse.

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

Für 1.4.2 am Zielsystem zusätzlich prüfen: TileHtml mit WLANThermo-Instanz-ID in IPSView laden; angeschlossene und abgezogene kabelgebundene/Bluetooth-Fühler vergleichen; Hauptanzeige aus/ein und festen Garraumkanal testen; danach Live-Bedienung auf den verwendeten IPSView-Plattformen prüfen.

Für 1.5.0 am Zielsystem zusätzlich prüfen: mindestens drei Minuten echten Anstieg beobachten; Prognose gegen den tatsächlichen Zielzeitpunkt vergleichen; Plateau/Abkühlung, Zieländerung, neuer Garvorgang und Verlauf-Reset testen. Dienstneustart muss die Lernphase neu beginnen. Die benötigte Genauigkeit ist anhand realer Garvorgänge zu bewerten.

Für 1.5.1 zusätzlich am Zielsystem prüfen: kompakte Restzeitzeile und Ausklappen bei schmaler Kachel; Animation an/aus und reduzierte Bewegung; Obergrenze unter bisherigen Min setzen und Gerätewerte/Rückmeldung kontrollieren; globale Schreibschutzfunktion prüfen.
