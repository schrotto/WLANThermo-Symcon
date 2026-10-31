# Änderungen

## 1.11.1 – 31.10.2026

- Kopftemperatur durch symmetrische Außenspalten exakt zur Gesamtansicht zentriert.
- 298 PHP- und 110 JavaScript-Vertragsprüfungen erfolgreich; gerenderte Zielclientdarstellung offen.

## 1.11.0 – 31.10.2026

- Freigegebenes Kopflayout: Titel/Verbindung links, Drahtfühlerauswahl und Geräteinfos rechts.
- Direkte Fühlerbuttons bis vier Sensoren; drei/vier in zwei Zeilen. Ab fünf kompakte Auswahl mit aufklappbarer Liste.
- Geöffnete Liste bei Live-Daten und Auswahl erhalten; Escape schließt mit Fokuswiederherstellung.
- Kopftemperatur bleibt oben ausgerichtet, geöffnete Liste verschiebt sie nicht.
- 298 PHP- und 110 JavaScript-Vertragsprüfungen erfolgreich.

## 1.10.2 – 31.10.2026

- Infoleiste automatisch nach vier Sekunden ausblenden, Fehlerrückmeldungen nach acht Sekunden.
- Schutz gegen veraltete Ausblend-Timer bei rasch aufeinanderfolgenden Meldungen.
- Bilder flankieren die gültige Kopftemperatur; Name/Messwert bleiben mittig. Ohne Kopftemperatur Rückkehr an den Titel.
- 298 PHP- und 102 JavaScript-Vertragsprüfungen erfolgreich.

## 1.10.1 – 31.10.2026

- Drahtfühlerbuttons verwenden nur den Fühlernamen ohne zusätzliche Kanalnummer.
- Breiterer Titelbereich, größere Bilder, obere Temperaturkachel etwas nach rechts.
- Vorgaben öffnen direkt die native Auswahl ohne vorgeschalteten Aufklappschritt; ausdrückliches Übernehmen bleibt erhalten.
- 298 PHP- und 95 JavaScript-Vertragsprüfungen erfolgreich.

## 1.10.0 – 31.10.2026

- Kompakte Augen-Buttonleiste zum Ein-/Ausblenden einzelner gültiger Drahtfühler in der Visualisierung.
- Persistente, hostgebundene Ansichtsauswahl ohne Gerätezugriff, Variablenänderung oder Löschung.
- Bluetooth-/MEATER-Kanäle und Kopftemperatur ausgenommen; Wiederherstellung auch bei vollständig leerer Übersicht erreichbar.
- Pushover-Zielmeldungen für versteckte Drahtfühler unterdrückt; offene Eingaben anderer Fühler erhalten.
- 298 PHP- und 92 JavaScript-Vertragsprüfungen erfolgreich.

## 1.9.0 – 31.10.2026

- Rote Kern-/Fühlertemperatur ab einstellbarem Vorlauf, optional mit sanftem Pulsieren.
- Optionale Pushover-Vorwarnung/Zielmeldung mit Anzeigename, Ist- und Zielwert; Kanalschalter in der Fühlerliste.
- Persistente Entprellung, Sendepause, Stopp bei API-Ablehnung, explizite Fehler-Rücksetzung und bereinigter Fehlerhinweis.
- Vorgaben statt Kerntemperatur als Sammlungstitel; MEATER-Obergrenze nach rechts.
- 284 PHP- und 83 JavaScript-Vertragsprüfungen erfolgreich; kein echter Pushover-Versand getestet.

## 1.8.0 – 31.10.2026

- Vorlagenauswahl als aufklappbare Wertkachel analog zur Obergrenze, mit dauerhaft sichtbarem Vorlagennamen/Temperatur.
- Bestätigte Vorlagenwahl pro Fühler persistent gespeichert und nach Neuladen wiederhergestellt.
- Zwei optionale Titelbild-Uploads mit eigener Sichtbarkeit und Anordnung vor/hinter dem Titel oder untereinander.
- Geprüfte lokale Rasterbilder; unveränderte Bilddaten werden bei Live-Updates nicht erneut versendet.
- Gelb markierte Fühler-/Messwertzählung entfernt.
- 258 PHP- und 78 JavaScript-Vertragsprüfungen erfolgreich.

## 1.7.0 – 31.10.2026

- Eigene Anzeigenamen pro Fühler in der Kanaltabelle, ohne Geräte-/Variablenumbenennung.
- Im Modul bearbeitbare Kerntemperatur-Sammlung mit auswählbaren Vorlagen und ausdrücklichem Übernehmen pro Fühler.
- Startvorlagen nach Benutzerwunsch: Rumpsteak 57 °C und Pulled Pork 94 °C. Celsius-Speicherung mit Fahrenheit-Umrechnung.
- Grenzwert zentriert und weiter links, Sammlung rechts daneben; Gerätestatus rechtsbündig.
- 245 PHP- und 70 JavaScript-Vertragsprüfungen erfolgreich.

## 1.6.2 – 31.10.2026

- Größere mittige Kopftemperatur, überarbeiteter Titel-/Logobereich.
- Alle gelb markierten Zusatztexte entfernt: WLANTHERMO-Label, Kanalangaben, Kopfkachel-Bearbeitung.
- Geräteinformationen und Bluetooth-Bedienung oben rechts statt am unteren Rand.
- Fühler-Obergrenze oben dauerhaft sichtbar; nur die Eingabe klappt bei Schreibrecht auf.
- 236 PHP- und 60 JavaScript-Vertragsprüfungen erfolgreich.

## 1.6.1 – 31.10.2026

- Frei einstellbarer Kacheltitel statt festem „Grillstation“.
- Kompakte Temperaturkachel zwischen Titel und Verbindung mit wählbarem Kanal und eigener Beschriftung.
- Oben angezeigter Kanal entfällt unten; Ausschalten stellt die normale Übersicht wieder her.
- Gültigkeits-/Sichtbarkeitsregeln, MEATER-Rollen, Schreibschutz und Obergrenzenbedienung berücksichtigt.
- 236 PHP- und 61 JavaScript-Vertragsprüfungen erfolgreich.

## 1.6.0 – 31.10.2026

- MEATER-Kern und Garraum gemeinsam in einer Kachel mit eindeutigen Messstellenbezeichnungen.
- Automatische Erkennung eindeutiger MEATER-Namenspaare; optionale ausdrückliche Kanalzuordnung für andere/doppelte Namen. Keine Paarung durch bloße Kanalnachbarschaft.
- Garraum als reine Messwertanzeige, auch im Hauptbereich; Kachel-Schreiben serverseitig gesperrt.
- Kern behält Obergrenze, Restzeit und Fortschritt; ungültige/ausgeschlossene Messstellen bleiben verborgen.
- Geräte-/Variablennamen und IDs bleiben erhalten.
- 230 PHP- und 48 JavaScript-Vertragsprüfungen erfolgreich.

## 1.5.2 – 29.10.2026

- Geschätzte Restdauer im optionalen Hauptbereich hervorgehoben, mit separatem voraussichtlichem Zielzeitpunkt.
- Plateau und andere unzuverlässige Verläufe ersetzen die Schätzung und entfernen den alten Zielzeitpunkt.
- Hauptanzeige in der Testdaten-Vorschau eingeschaltet; unveränderte optionale Konfiguration im Modul.
- 218 PHP- und 40 JavaScript-Vertragsprüfungen erfolgreich.

## 1.5.1 – 29.10.2026

- Restzeit auf eine kompakte aufklappbare Zeile reduziert; Verlauf, Trend und Reset erst bei Bedarf sichtbar.
- Ruhiger animierter Fortschrittsbalken mit weichen Wertübergängen und dezentem Lichtlauf bei gültiger Aufheizprognose.
- Abschaltbare Bewegung und reduzierte Animation berücksichtigt.
- Kachel passt eine kollidierende Untergrenze beim Max-Schreiben atomar auf Max minus ein Grad an, am API-Limit auf denselben Wert.
- Frische Gerätedaten und Rücklesen beider Grenzen; allgemeine Skriptschnittstelle bleibt strikt.
- 218 PHP-Modulprüfungen und 38 JavaScript-Vertragsprüfungen erfolgreich.

## 1.5.0 – 29.10.2026

- Restzeitschätzung bis zur Obergrenze mit geglättetem Temperaturtrend und voraussichtlichem Zielzeitpunkt.
- Echte Messkurve, punktierte Obergrenze und gestrichelte Trendfortschreibung.
- Temperaturfortschritt seit Start statt zeitlichem Garfortschritt.
- Lernphase, Plateau-, Abkühlungs-, Instabilitäts- und Veraltet-Erkennung.
- Begrenzte interne Verläufe ohne zusätzliche Statusvariablen; je Fühler manuell neu startbar.
- Neues Verhalten durch deterministische Messreihen und JavaScript-Verträge geprüft; keine reale Hardware-/Clientprüfung behauptet.

## 1.4.3 – 29.10.2026

- Min-Wert und Min-Eingabe aus der Kachel entfernt; nur Obergrenze sichtbar und bedienbar.
- Kachelnachrichten enthalten keinen Min-Wert; Geräteänderungen über die Kachel schreiben ausschließlich max.
- Warnungen und Temperaturanzeige beziehen sich nur auf die Obergrenze.
- Gespeicherte Geräte-Untergrenzen, optionale Variablen und Skriptschnittstellen bleiben erhalten.
- 178 PHP-Modulprüfungen und 26 JavaScript-Vertragsprüfungen erfolgreich.

## 1.4.2 – 29.10.2026

- Nur gültige aktuelle Fühler an die Kachel übertragen und darstellen; ungültige, getrennte und offline gewordene Fühler verschwinden automatisch.
- Gemeinsame Gültigkeitsprüfung für Kanalstatus und Visualisierung.
- Große Hauptanzeige standardmäßig aus; optional für festen Kanal oder automatische Auswahl.
- Fehlender fester Hauptkanal wird ausgeblendet und nicht durch anderen Fühler ersetzt.
- Normaler HTML-Kacheltyp als Standard für IPSView TileHtml; HTML-Vollbild ab 9.1 gesondert optional.
- Fühlerübersicht bleibt in niedrigen Kacheln auch ohne Hauptanzeige sichtbar.
- 173 PHP-Modulprüfungen und 21 JavaScript-Vertragsprüfungen erfolgreich; tatsächlicher IPSView-Clientbetrieb noch nicht getestet.

## 1.4.1 – 27.10.2026

- Fehler bei Instanzerstellung durch nicht unterstützte Array-Übergabe an UpdateVisualizationValue behoben.
- Sämtliche Kachelnachrichten werden als JSON-Text übertragen, einschließlich Deaktivierung und Fehlerquittierung.
- Versandpuffer wird nur bei erfolgreichem SDK-Aufruf fortgeschrieben.
- Testsimulation lehnt Arrays/Objekte an der nativen Wertgrenze ab.
- Fünf zusätzliche Regressionstests; insgesamt 164 Modulprüfungen und 15 JavaScript-Vertragsprüfungen erfolgreich.

## 1.4.0 – 27.10.2026

- Responsive Grill-Kachel über das HTML-SDK, ohne zusätzliche Statusvariablen.
- Live-Temperaturen, Alarmbereiche, Pitmaster und Bluetooth mit Rücklesebestätigung.
- Helles/dunkles Design, Akzentfarbe, Kompaktmodus und individuelle Kachelkanäle.
- Neue Instanzen starten mit null Variablen; bestehende Instanzen behalten ihren Umfang.
- Sammelauswahl, empfohlener Umfang und Variablenzähler.
- Vorhandene externe Datenziele mit Typ-/Schreibbarkeitsprüfung und Referenzverwaltung.
- Gesondert bestätigte Bereinigung eigener ungenutzter Variablen.
- 159 Modulprüfungen, 15 JavaScript-Vertragsprüfungen, PHP-/JS-Syntax und JSON-/Strukturprüfung erfolgreich.
- Browserdarstellung und echter Symcon-/Gerätebetrieb noch nicht verifiziert.

## 1.3.0 – 27.10.2026

- Variablen bei Abwahl erhalten statt löschen; IDs, Werte und Archivzuordnungen bleiben bestehen.
- Eigene Namen, Positionen und auswählbare aktuelle Darstellungen in der Variablentabelle.
- Übersichtliches deutsches Formular mit fünf Bereichen und Verbindungstest.
- Bisherige Einzel- und Gruppenauswahl sowie GUIDs/Idents bleiben erhalten.
- Wiederherstellung der vorherigen Sichtbarkeit und Offline-Anpassung der Variablenmetadaten.
- Ungültige Konfigurationen stoppen den Timer und deaktivieren Bedienaktionen; Formular bleibt erreichbar.
- 124 Modulprüfungen und 8 echte HTTP-Transportprüfungen erfolgreich, dazu PHP-Syntax und JSON-/Strukturprüfung.
- Reale Hardware und Symcon-Konsole müssen noch geprüft werden.

## 1.2.1 – 25.10.2026

- Ausgeschaltetes oder vorübergehend nicht erreichbares WLANthermo wird als normaler Standby (106) statt fehlerhafte Instanz (202) behandelt.
- Automatischer Abruf bleibt aktiv; nach dem Einschalten kehrt die Instanz selbstständig in den Aktivstatus (102) zurück.
- Alte Messwerte bleiben erhalten, sind während des Ausfalls aber ungültig; Bedienaktionen werden deaktiviert.
- Anmelde-, Zertifikats-, Konfigurations- und API-Fehler bleiben echte Fehler.
- 109 Modul-/API-Assertions bestanden; zusätzlich eine reale cURL-Verbindungsablehnung korrekt als Nichterreichbarkeit eingeordnet.

## 1.2.0 – 18.10.2026

- Autor: schrotto; Repository- und Dokumentationsadressen für `schrotto/WLANThermo-Symcon` vorbereitet.
- Variablenauswahl über eine normale Konfigurationsliste mit Checkboxen. Alle Änderungen werden erst über die Schaltfläche Übernehmen bestätigt.
- Laufzeitcode enthält keine Aufrufe von `IPS_SetProperty` oder `IPS_ApplyChanges`. Die bisherige Funktion `WLT_SetVariableEnabled` entfällt.
- Gespeicherte Einzel- und Gruppenauswahlen bleiben bei Migration von 1.1.0 erhalten. Neue und zeitweise fehlende Datenfelder setzen bestehende Entscheidungen nicht zurück.
- Vorbereitete deutsche Store-Texte und Hinweise für Reviewer ergänzt.
- Tests um verzögerte Übernahme, unveränderte Eigenschaften, Migration, unbekannte Identifikatoren und Store-bezogene Mutationserkennung erweitert.

## 1.1.0 – 18.10.2026

- Alarmgrenzen und Pitmaster-Sollwerte: keine Nachkommastellen, Schrittweite 1 Grad. Bestehende Variablentypen und IDs bleiben erhalten. Gemessene Temperaturen bleiben präzise.
- Neue Gradwerte werden als ganze Zahlen geprüft; Dezimalwerte mit Nachkommastellen werden nicht an das Gerät gesendet.
- Kontrollkästchen für Variablengruppen und jede einzelne bekannte Variable. Die Auswahl wird in der Instanzkonfiguration gespeichert und bleibt nach Neustart erhalten.
- Abwählen entfernt vorhandene Variablen und verhindert ihre automatische Wiederanlage. Erneutes Aktivieren erzeugt neue IDs. Auswahl funktioniert auch bei ausgeschaltetem WLANthermo.
- Status und Fehler werden unabhängig von optionalen Variablen intern geführt. Neue Funktion `WLT_GetLastError` für Konsole und Skripte.
- 72 Modul-/API-Prüfungen für 1.1.0 bestanden; 8 HTTP-Prüfungen des unveränderten Transports aus dem vorherigen Lauf.

## 1.0.0

Erste Version für Symcon 9.0, Mini V3 und Bluetooth-/MEATER-Sensoren.
