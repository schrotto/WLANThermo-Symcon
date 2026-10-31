# Prüfbericht 1.11.3 – 31. Oktober 2026

Zielsystem: IP-Symcon ab 9.0, WLANThermo Mini V3 und dessen unterstützte Fühler. Die Firmwareanbindung bleibt gegenüber 1.3.0 erhalten. Die neue Kachel verwendet das offizielle HTML-SDK ohne zusätzliche Statusvariablen.

## Änderungen und Nachweise 1.11.3

302 PHP- und 115 JavaScript-Vertragsprüfungen erfolgreich. Der Benutzer meldete die einzelnen Augen-Buttons als nicht anklickbar. Ohne Zugriff auf den betroffenen Client ist die konkrete Hit-Test-/Ereignisursache nicht reproduziert. Korrigiert wurden die browserabhängige Details-Hülle, mögliche überlagerte Kopf-Klickflächen und die Weitergabe von Button-Ereignissen an Kachel-Gesten.

Der Test-DOM übernimmt nun die tatsächlichen Tags der statischen HTML-IDs. Neue Verträge prüfen normalen sichtbaren Buttoncontainer im Schreibschutz, gestoppte Pointer-Weitergabe, echten Menübutton samt aria-expanded sowie Öffnen/Schließen durch tatsächliche Button-Handler statt künstlichem Setzen einer nativen open-Eigenschaft. Bestehende Sichtbarkeitsaktionen, persistente Auswahl, Antwort-/Doppelklickbehandlung und Escape bleiben geprüft.

CSS-Klickfläche und Ebenenreihenfolge wurden nicht im gerenderten Zielclient gemessen. Auf dem echten Client sind Klick auf Icon/Name/Hintergrund, Touch, Geräte-Schreibschutz, zwei/vier/fünf Fühler, Live-Daten und Kopfkachelüberlagerung zu prüfen. HTML-/JS-Neuladen nach dem Update ist notwendig.

## Änderungen und Nachweise 1.11.2

302 PHP- und 110 JavaScript-Vertragsprüfungen erfolgreich. Die Fehlerursache aus dem Screenshot wurde im Test nachgestellt, indem die bislang im Runtime-Stub selbst definierten HTML-/NONE-Konstanten entfernt wurden. Alle Neuinstanzen des Modulvertrags laufen nun ohne diese Konstanten. Zusätzlich geprüft: deaktivierte Kachel ohne NONE-Konstante, Vollbildwunsch ohne Vollbildkonstante (HTML-Rückfall), dokumentierter Vollbildtyp bei später definierter Konstante und vollständige Initialdaten nach Wiederaktivierung. Kein globales Nachdefinieren von Konstanten im Modul.

SetVisualizationType verwendet die offiziell dokumentierten Zahlen 0/1 nur als Rückfall für fehlende NONE-/HTML-Konstanten. Der Vollbildtyp bleibt an vorhandene SDK-Unterstützung gebunden. Der Screenshot belegt die fehlende HTML-Konstante, nicht die tatsächliche Symcon-Versionsnummer. Ein echtes Neuanlegen auf der betroffenen Installation wurde hier nicht durchgeführt.

Der Test deckte zusätzlich einen alten Deaktivierungspayload nach schnellem Wiederaktivieren auf. Zurücksetzen der Versand-Hashes beim Abschalten erzwingt korrekte Wiederaktivierungsdaten. Bestehende Variablen-/Bild-/Aktionsverträge bleiben erfolgreich.

## Änderungen und Nachweise 1.11.1

Reine CSS-Layoutkorrektur: ab 941 Pixeln gleich breite Außenspalten und mittlere Temperaturspalte; eigene schmale Zeile weiterhin zentriert. 298 PHP- und 110 JavaScript-Vertragsprüfungen erfolgreich. Diese Regressionstests bestätigen keine gerenderte Zentrierung; tatsächliche Darstellung bei breiten/schmalen IPSView-Kacheln bleibt zu prüfen.

## Änderungen und Nachweise 1.11.0

298 PHP- und 110 JavaScript-Vertragsprüfungen erfolgreich. Neue UI-Verträge prüfen vier direkte Fühlerbuttons, Wechsel zum zunächst geschlossenen Menü bei fünf, Erhalt eines geöffneten Menüs bei Live-Daten und bestätigter Sichtbarkeitsänderung, Escape mit Fokus am Schalter, zehn verfügbare Fühler, Rückkehr zur direkten Leiste und Wegfall des Schalters bei leerer Auswahl. Bestehende persistente Sichtbarkeit, Schreibschutz und Warn-/Pushover-Verträge bleiben enthalten.

Statisches HTML verlegt Verbindung/Aktualisierung in die linke Kopfspalte und die Auswahl in die rechte. CSS legt die Mehrfachliste absolut über den Inhalt, begrenzt sie auf die Fensterbreite und richtet die Kopftemperatur oben aus. Drei/vier direkte Schalter erhalten zwei Spalten. Diese Struktur-/DOM-Verträge bestätigen weder gerenderte Geometrie noch echtes Touchverhalten. Kein neuer Browser-/IPSView-/Gerätetest.

Zielprüfung: zwei, vier, fünf und zehn Fühler, lange Anzeigenamen, schmale Kachel, Menü öffnen und nacheinander mehrere Fühler aus-/einblenden, Live-Aktualisierung, Escape/Fokus und weiterhin unveränderte Kopftemperaturposition. Der KI-Bildentwurf ist keine Aufnahme des implementierten Codes.

## Änderungen und Nachweise 1.10.2

298 PHP- und 102 JavaScript-Vertragsprüfungen erfolgreich. Neue UI-Verträge prüfen Bilder links/rechts in der gültigen Kopftemperatur, untereinander links sowie Rückkehr an den Titel bei ausgeschalteter Kopftemperatur. Das Verschieben verwendet bestehende Bildknoten; unveränderte Bilddaten werden nicht neu gesetzt.

Timer-Verträge prüfen sichtbare neue Rückmeldung, vollständiges Ausblenden/Leeren durch den aktuellen Timer und Schutz einer neueren Rückmeldung vor einem veralteten Callback. Zeitdauern sind im Code vier Sekunden für normale Hinweise und acht Sekunden für Fehler. Testtimer führen die Callbacks kontrolliert aus; sie messen keine reale Browserzeit. Dauerhafte Gerätedatenfehler sind von der temporären Infoleiste getrennt.

CSS reserviert gleich breite Bildspalten um den mittleren Messwert und verkleinert Bildspalten/Schrift bei schmalen Kacheln. Keine gerenderte IPSView-/Browser-Prüfung. Am Zielsystem prüfen: beide/ein/keine Bilder, beide Anordnungen, ungültige Kopftemperatur, schmale Breite und schnell wechselnde Rückmeldungen.

## Änderungen und Nachweise 1.10.1

298 PHP- und 95 JavaScript-Vertragsprüfungen erfolgreich. Zusätzliche UI-Verträge prüfen die ausschließlich aus Icon und Fühlername bestehenden Drahtbuttons, das unmittelbar in der Vorgabenfläche liegende native Select ohne Details-Vorstufe sowie Auswahl ohne Schreibauftrag mit anschließend sichtbarer Übernahme. Bestehende Bestätigungs-/Persistenz-, Schreibschutz- und Sichtbarkeitstests bleiben enthalten.

Die Auswahlfläche ist ein Label mit einem über die gesamte Fläche angeordneten nativen Select; es wird kein showPicker-Aufruf oder künstliches Doppelklicken verwendet. Die visuelle Zusammenfassung bleibt darunter sichtbar. Tastaturfokus wird über focus-within umrandet. DOM-Verträge bestätigen keine tatsächlich geöffnete Browser-/IPSView-Auswahlliste. Kopfbreiten/Bildgrößen sind ausschließlich CSS-Anpassungen und benötigen visuelle Prüfung auf dem Zielclient.

## Änderungen und Nachweise 1.10.0

298 PHP- und 92 JavaScript-Vertragsprüfungen erfolgreich. Neue tests/visibility.php prüft nur gültige Drahtfühler ohne Kopfkanal in der Leiste, Ausblenden im Schreibschutz ohne HTTP-Abruf/POST, weiter sichtbaren Wiederherstellungsbutton, persistente Auswahl in neuer Modullaufzeit, Zurückweisen alter Grenzanfragen, Wiedereinblenden, Ausschluss von Bluetooth/Kopfkanal, übergeordnete Modul-Auswahl, Typ-/Parameterfehler, Hostwechsel und Sonderfall Kopftemperatur. Ein simuliertes Pushover-Szenario bestätigt die Unterdrückung von Zielmeldungen für verborgene Drahtfühler.

JavaScript prüft beschriftete Buttons mit aria-pressed, Betätigung im Geräte-Schreibschutz, bestätigungsgebundenen Umschaltauftrag und Doppelklicksperre, Entfernen ausschließlich der Drahtkachel bei weiter bestehendem MEATER-Paar, erreichbare Leiste/Erklärung bei vollständig verborgenen Kacheln, Wiedereinblenden, leere Leiste und Erhalt offener Fremd-Eingaben während und nach der Umschaltung.

Sichtbarkeitsaktionen sind schema-/kanalgeprüft, über die Instanz-Semaphore serialisiert und schreiben ausschließlich ein Instanzattribut. Es erfolgen weder Geräteschreibzugriffe noch automatische Modul-Eigenschaftsänderungen. Die serverseitigen Freigaben der Gerätesteuerung bleiben bestehen. Keine neuen Statusvariablen. CSS und SVG-Zustände wurden im DOM-Vertrag geprüft, nicht als gerendertes IPSView-Layout.

Offene Zielprüfung: Leistenposition bei schmalen/breiten Kacheln, Touch/Keyboard, alle Drahtfühler aus und wieder ein, Client-Neuladen, vorhandene Moduleigenschaftsauswahl und mehrere gleichzeitig geöffnete Visualisierungen. Reale Pushover-Zustellung bleibt weiterhin offen.

## Änderungen und Nachweise 1.9.0

284 PHP- und 83 JavaScript-Vertragsprüfungen erfolgreich. Neue Tests in tests/notifications.php verwenden eine kontrollierte Uhr und einen simulierten Pushover-Transport; es wurde keine echte Nachricht versendet. Geprüft sind Vorlauf 1/0,5 °C, exakte Zielerreichung, MEATER-Garraum-Ausschluss, abschaltbarer Farbeffekt, eigener Fühlername/Ist/Ziel, einmalige Vorwarnung und separate Zielmeldung, Pendeln, persistente Zustände, fünf Minuten Abkühlung, Zielwechsel, geheime Schlüssel außerhalb von Snapshot/Visu, Kanalabwahl, versteckte Kanäle, Versandfehler bei weiterhin aktiver Instanz, Pause/Wiederholung, Fahrenheit-Vorlauf und fehlende Schlüssel. Ergänzend: Stopp bei API-Ablehnung und explizites Rücksetzen, globale Netzfehlerpause für andere Fühler sowie getrennte Vorwarnungs-/Zielmeldungsschalter.

JavaScript prüft rote/pulsierende Zustandsklassen, optionale Hauptanzeige, abschaltbares Pulsieren bei verbleibendem Farbeffekt, Wegfall des Alarmzustands, Beschriftung Vorgaben und Versandfehler in der Kachel. Die Farbe/Animation und die nach rechts verschobene MEATER-Grenze sind nicht mit einem echten Browser/IPSView geprüft. Bestehende reduzierte Bewegungs- und Animationsregeln bleiben wirksam.

Native HTTPS-Implementierung anhand der offiziellen Pushover-API geprüft: fester Endpunkt, POST/Formulardaten, TLS-Verifikation, keine Redirects, 3 Sekunden Verbindung/5 Sekunden gesamt. HTTP 4xx und Status != 1 stoppen Wiederholungen bis Korrektur/Rücksetzen. Temporäre Fehler frühestens nach 60 Sekunden erneut; maximal ein Versuch pro Poll. Annahme durch Pushover ist keine Handy-Zustellbestätigung. Bei Antwortverlust ist doppelte Annahme nicht vollständig auszuschließen. Keine Zugangsdaten in Fehler-/Debugtext oder Kachelpayload. Keine neuen Statusvariablen.

Echte Zielprüfung bleibt erforderlich: App-Token und User-Key konfigurieren, eigene Kanalwahl prüfen, Temperatur über beide Schwellen führen, Neustart/Pendeln und geändertes Ziel prüfen, Netzverlust/API-Ablehnung behandeln, Handy-Eingang sowie rote Anzeige/Puls/Bewegung aus am Zielclient kontrollieren.

## Änderungen und Nachweise 1.8.0

258 PHP- und 78 JavaScript-Vertragsprüfungen erfolgreich. PHP prüft bestätigte Vorlagenspeicherung, weiteren Abruf und Wiederherstellung in einer neuen Test-Modullaufzeit, Ablehnung widersprüchlicher Vorlage/Grenze vor dem POST, keine veraltete Bestätigung bei externer Grenzänderung/anderer Quelle/entfernter Sammlung. Upload-Prüfung mit kleinen PNG-Daten: zwei Bilder, Einzel-Ausblendung ohne Löschen, Auslassen unveränderter Bilder bei einem geänderten Live-Messwert, vollständige Initialdaten unabhängig vom Versandpuffer sowie Ablehnung ungeeigneter bzw. zu großer Daten.

JavaScript prüft geschlossene Vorlagen-Wertkachel mit bestätigtem Namen und Temperatur, Erhalt bei Live-Aktualisierung, Entfernen alter Bestätigung, Bildanordnung vor/hinter dem Titel bzw. untereinander, Erhalt bei Updates ohne Bildpayload, Rückkehr zur Flamme und entfernte Fühlerzählung. Weiterhin geprüft sind Auswahl ohne POST, ausdrückliche Übernahme, Schreibschutz und MEATER-Rollen.

Der Upload ist über das offiziell dokumentierte SelectFile-Formularelement angebunden. Getestet wurden dessen gespeicherte Base64-Werte und ihre Verarbeitung; kein tatsächlicher Datei-Dialog in einer laufenden Symcon-Konsole. Bildprüfung über getimagesizefromstring und MIME-Positivliste; 256 KiB/2048 Pixel je Seite als Grenzen. Nur Hashes der Bildpayloads werden als Versandpuffer gespeichert. Initiale Kachel enthält Bilder immer, Folgeupdates nur bei Änderungen. Keine externen URLs oder neuen Statusvariablen.

Offen am Zielsystem: echte Uploads/Ersetzen/Ausblenden, PNG/JPEG/GIF/WebP im Client, beide Layouts bei schmaler Kachel, bestätigte Vorlagenwahl nach Browser-Neuladen und Symcon-Neustart. DOM-Verträge bestätigen kein gerendertes IPSView-Layout.

## Änderungen und Nachweise 1.7.0

245 PHP- und 70 JavaScript-Vertragsprüfungen erfolgreich. PHP prüft Startvorlagen, gespeicherten Anzeigenamen und erneutes Laden in der dynamischen Tabelle, unveränderte Originalnamen/MEATER-Zuordnung, Celsius/Fahrenheit-Umrechnung, bestätigtes Schreiben der umgerechneten Temperatur, leere Sammlung ohne Hilfsvariablen sowie ungültige Vorlagen-/Aliaswerte.

JavaScript prüft Darstellung der Vorlagen mit Einheit, Auswahl ohne Schreibauftrag, Erhalt der Auswahl bei Live-Daten, Übernahme auf den richtigen Kernkanal, Zurücksetzen geänderter/entfernter Vorlagen, Schreibschutz, leere Sammlung, Ausschluss der MEATER-Garraumrolle und sichere Anzeigenamen mit erhaltener Paarung.

Die Sammlung verwendet denselben validierten Kanal-Schreibpfad wie das manuelle Grenzformular. Es gibt keinen automatischen Gerätezugriff durch bloße Auswahl oder Konfigurationsänderung. Native Auswahl und Übernahme bleiben bei Schreibschutz gesperrt. Die Startwerte stammen aus dem Benutzerwunsch. DOM-Vertragstests bestätigen keine Browsergeometrie, Touch-Darstellung oder echte Gerätekommunikation. Reale IPSView-Prüfung bleibt offen.

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
