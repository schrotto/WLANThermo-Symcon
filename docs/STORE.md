# Veröffentlichung – WLANThermo 1.10.1

Die Metadaten sind weiterhin für `schrotto/WLANThermo-Symcon` vorbereitet. In dieser Sitzung wurden weder Repository noch Store-Eintrag veröffentlicht. Bibliotheks- und Modul-GUIDs bleiben unverändert.

## Vorbereitung

1. Inhalt der Bibliothek in das eigene Git-Repository übernehmen; library.json liegt im Hauptverzeichnis.
2. CI ausführen und den Prüfplan in VALIDIERUNG.md am eigenen Mini V3 und in Symcon wiederholen.
3. Vorschau, Variablentabelle, tatsächlichen Lizenzverbrauch und Verhalten beim Update überprüfen.
4. Erst nach Zielsystemprüfung einen Release-Tag setzen und bei Bedarf im Entwicklerbereich von Symcon einreichen. Eine Store-Annahme ist nicht zugesichert.

## Deutsche Beschreibung

Bindet den WLANThermo Mini V3 und dessen unterstützte Bluetooth-/MEATER-Fühler lokal in Symcon ein. Individuelle Grill-Kachel mit Temperaturen, Alarmbereichen, Pitmaster und Live-Bedienung. Keine zusätzlichen Statusvariablen für die Kachel erforderlich. Optionale Variablen sind einzeln auswählbar; vorhandene Datenziele können wiederverwendet werden. Abwahl schützt bestehende Daten, endgültiges Entfernen erfordert eine gesonderte Bestätigung. Mindestversion Symcon 9.0; individuelle maximierte HTML-Ansicht nur bei unterstütztem Vollbildtyp ab 9.1.

## Technische Reviewhinweise

- Keine automatischen IPS_SetProperty-/IPS_ApplyChanges-Aufrufe.
- Neue Instanzen legen standardmäßig null Variablen an. Alte Instanzen behalten ihren Umfang; keine automatische Löschung.
- Namen/Positionen eigener Variablen werden nur bei geänderter Tabellenwahl gesetzt; Sichtbarkeit bei Abwahl gespeichert und bei Wiederaktivierung restauriert. Benutzerdefinierte Darstellungen, Aktionen und Archivierung werden nicht verändert.
- Die explizite Bereinigung entfernt ausschließlich ungenutzte eigene Statusvariablen nach Checkbox und gesonderter Aktion. Externe Ziele werden nie gelöscht.
- Externe Variablen sind ausdrücklich über SelectVariable zugewiesen, typgeprüft und referenziert. Nur deren Werte werden aktualisiert. Keine Einrichtung einer Geräteaktion an externen Variablen.
- HTML-SDK statt eigener Webhooks oder HTML-Box. Initialdaten sind gegen Script-Ausbruch kodiert; dynamische Namen werden mit textContent eingefügt.
- Die Kachel erhält nur die benötigten Gerätedaten ohne Zugangsdaten. Jede Aktion ist serverseitig freigabe- und parametergeprüft.
- Alle Ressourcen stammen aus dem Bibliotheksordner. Keine externen JavaScript-/CSS-Dienste erforderlich. Optionale Pushover-Nachrichten ab 1.9.0 benötigen ein Pushover-Konto und senden Daten an diesen externen Dienst.
- Die Benutzeroberfläche ist deutsch; weitere Store-Sprachen erst mit vollständiger Übersetzung anbieten.

Offizielle Grundlage: [Einreichen und Review](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/). Testergebnisse und Einschränkungen stehen in VALIDIERUNG.md.

Hotfix 1.4.1 korrigiert die native Wertübergabe: Live-Nachrichten werden als JSON-Text statt als PHP-Array gesendet. Die Instanzerstellung ohne Grundkonfiguration ist durch einen neuen Regressionstest abgesichert.

Version 1.4.2 zeigt nur gültige Fühler, macht die große Hauptanzeige optional und nutzt standardmäßig den normalen HTML-Typ für die dokumentierte IPSView-TileHtml-Einbindung. Die erweiterte HTML-Vollbildansicht ist gesondert einschaltbar.

Version 1.4.3 entfernt die Untergrenze aus der Visualisierung und beschränkt Kacheländerungen auf max. Gespeicherte Geräte-Untergrenzen und optionale Min-Variablen bleiben unverändert erhalten.

Version 1.5.0 ergänzt eine als Trend-Schätzung gekennzeichnete Restzeitprognose, echte Verlaufspunkte und interne, begrenzte Buffer ohne Zusatzvariablen. Keine Archivaktivierung oder externen Datenverbindungen. Prognosegüte und Clientdarstellung müssen vor Veröffentlichung real geprüft werden.

Version 1.5.1 reduziert die Restzeitanzeige auf eine aufklappbare Zeile, ergänzt eine abschaltbare Balkenanimation und passt bei Kacheländerungen kollidierende Untergrenzen im selben Gerätezugriff an. Beide Werte werden vor Bestätigung zurückgelesen; Skriptschnittstellen und Schreibschutz bleiben erhalten.

Version 1.5.2 zeigt die geschätzte Restdauer und den voraussichtlichen Zielzeitpunkt im optionalen Hauptbereich. Die Testdaten-Vorschau blendet diesen Bereich ein. Aktueller Prüfumfang: 218 PHP- und 40 JavaScript-Vertragsprüfungen.

Version 1.6.0: gemeinsame MEATER-Kachel für Kerntemperatur/Garraumtemperatur. Nur der Kern bietet Ziel, Restzeit und Obergrenzenbedienung. Eindeutige Namen oder explizite MEATER-Zuordnung verbinden die Messstellen. Geräte- und Variablennamen bleiben erhalten. 230 PHP- und 48 JavaScript-Vertragsprüfungen erfolgreich; reale IPSView-/Geräteprüfung offen.

Version 1.6.1: frei einstellbarer Titel und kompakte Kopftemperatur mit wählbarem Kanal/Anzeigenamen. Oben angezeigter Kanal entfällt unten; Obergrenzenbedienung und MEATER-Garraum-Anzeigerolle bleiben erhalten. Aktuell 236 PHP-/61 JavaScript-Vertragsprüfungen erfolgreich, reale IPSView-Prüfung offen.

Version 1.6.2: größere mittige Kopftemperatur als reine Anzeige, bereinigte Zusatztexte, Gerätestatus oben rechts. Fühlergrenzen oben stets sichtbar mit ausschließlich aufklappbarer Eingabe. 236 PHP-/60 JavaScript-Vertragsprüfungen erfolgreich; echte Clientprüfung offen.

Version 1.7.0 ergänzt eigene Anzeigenamen pro Fühler und eine im Modul editierbare Kerntemperatur-Sammlung. Auswahl schreibt erst nach Übernehmen. Startvorlagen nach Benutzerwunsch, Celsius-/Fahrenheit-Umrechnung, Schreibschutz und MEATER-Rollen berücksichtigt. 245 PHP-/70 JavaScript-Vertragsprüfungen erfolgreich; echte Clientprüfung offen.

Version 1.8.0: persistente bestätigte Vorlagenwahl als geschlossene Wertkachel, zwei optionale lokale Titelbilder mit Anordnung/Sichtbarkeit, entfernte Fühlerzählung. 258 PHP-/78 JavaScript-Vertragsprüfungen erfolgreich. Reale Upload-/IPSView-Prüfung offen.

Version 1.9.0: einstellbare rote Temperaturwarnung, optionaler Puls und Pushover-Nachrichten mit Namen/Ist/Ziel. Pushover standardmäßig aus, externe HTTPS-Datenübertragung nur bei expliziter Aktivierung. Persistenter Meldezustand ohne neue Variablen, Vorwarnung/Ziel getrennt. 284 PHP-/83 JavaScript-Prüfungen erfolgreich; native Pushover-Zustellung und IPSView offen.

Version 1.10.0 ergänzt eine persistente Drahtfühler-Buttonleiste für die Ansicht. Keine Geräteänderungen oder zusätzlichen Variablen. Versteckte Drahtfühler bleiben abfragbar, lösen aber keine Pushover-Zielmeldung aus. 298 PHP-/92 JavaScript-Vertragsprüfungen erfolgreich; Zielclientprüfung offen.

Version 1.10.1: reine Fühlernamen in der Leiste, mehr Titelbreite/größere Bilder, leicht nach rechts versetzte Kopftemperatur und direkte native Vorgabenwahl. Übernehmen bleibt erforderlich. 298 PHP-/95 JavaScript-Vertragsprüfungen erfolgreich; Clientdarstellung offen.
