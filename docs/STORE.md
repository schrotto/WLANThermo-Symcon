# Veröffentlichung – WLANThermo 1.4.2

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
- Alle Ressourcen stammen aus dem Bibliotheksordner. Keine externen JavaScript-/CSS-Dienste oder Cloud-Konten erforderlich.
- Die Benutzeroberfläche ist deutsch; weitere Store-Sprachen erst mit vollständiger Übersetzung anbieten.

Offizielle Grundlage: [Einreichen und Review](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/). Testergebnisse und Einschränkungen stehen in VALIDIERUNG.md.

Hotfix 1.4.1 korrigiert die native Wertübergabe: Live-Nachrichten werden als JSON-Text statt als PHP-Array gesendet. Die Instanzerstellung ohne Grundkonfiguration ist durch einen neuen Regressionstest abgesichert.

Version 1.4.2 zeigt nur gültige Fühler, macht die große Hauptanzeige optional und nutzt standardmäßig den normalen HTML-Typ für die dokumentierte IPSView-TileHtml-Einbindung. Die erweiterte HTML-Vollbildansicht ist gesondert einschaltbar.
