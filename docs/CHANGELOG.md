# Änderungen

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
