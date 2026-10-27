# Prüfbericht 1.3.0 – 27. Oktober 2026

Zielversion bleibt IP-Symcon ab 9.0, Basisklasse IPSModuleStrict. Die Firmwareanbindung des Ausgangsmoduls 1.2.1 bleibt bestehen. Das tatsächliche Gerät, dessen Firmware und die Symcon-Konsole waren für diese Prüfung nicht verfügbar.

## Tatsächlich ausgeführt

- PHP 8.3.6: Syntaxprüfung aller fünf PHP-Dateien ohne Fehler. Die Testlaufzeit wurde ausschließlich unter `/tmp` entpackt, ohne Systeminstallation.
- `php -n tests/run.php`: **124 erfolgreiche Assertions**. Darunter Kanal-/Pitmaster-/PID-/Bluetooth-Schreiben und Rücklesen, Geheimnisredaktion, ganze Grad, Celsius/Fahrenheit, Schreibschutz, Fehlerzustände, Standby und automatische Erholung.
- Neue Prüfungen: Abwahl bewahrt IDs und letzte Werte, verhindert neue Variablen und Bedienaktionen, Gruppe hat Vorrang, Wiederaktivierung verwendet dieselbe ID, Migration bisheriger Einstellungen, alle Variablen deaktivierbar, unbekannte Idents bleiben gespeichert, Name/Position/Darstellung gespeichert, Neustart mit leeren Puffern, Offline-Konfiguration, vorherige manuelle Sichtbarkeit und spätere manuelle Objektänderungen bleiben erhalten.
- Ungültige Listen, doppelte Idents, falsche Datentypen, Positionen und Darstellungen werden abgefangen; Formular bleibt lesbar und vorhandene Aktionen werden deaktiviert. Auch ein manueller Abruf bei ungültiger Auswahl wird abgefangen.
- `python3 tests/http_transport.py php`: **8 erfolgreiche echte HTTP-Prüfungen** gegen einen lokalen Testserver. GET, Basic- und Digest-Authentifizierung, UTF-8-POST, HTTP 401, Redirect, HTTP 404 und Timeout. Ausführung außerhalb der Socket-beschränkten Sandbox, ohne Kontakt zu einem realen Gerät.
- `python3 tests/structure.py`: **8 JSON-Dateien gültig**, einschließlich der drei Fixtures und zwei tatsächlich erzeugten Formulare. GUIDs, Klassenname, Präfix, Modultyp, leere Datenflussanforderungen, eindeutige Formularnamen und Übereinstimmung mit registrierten Properties geprüft. Im Modul existiert kein Aufruf zum Löschen einer Variable.

Die Symcon-Simulation prüft gezielte API-Verträge; sie ersetzt nicht die echte Laufzeit. Die Darstellungs-Konstanten werden dort symbolisch nachgebildet. Namen und Positionen werden durch dokumentierte globale Funktionen ausschließlich an eigenen Variablen und nach geänderter Benutzerauswahl angewendet. Sichtbarkeit wird bei Deaktivierung einmalig gespeichert und geändert, bei Reaktivierung wiederhergestellt. Benutzerdefinierte Profile, Aktionen und Logging bleiben unverändert.

## Nicht verifiziert

- Installation und Update in einer echten Symcon-9.0-Konsole; Darstellung der neuen Tabellenfelder, Schieberegler und Aufzählungen in den verschiedenen Visualisierungen.
- Tatsächliche Archivverläufe und Verknüpfungen. Dass die IDs und Werte beim Abwählen unverändert bleiben und kein Löschaufruf erfolgt, wurde simuliert geprüft.
- Reale WLANthermo-/MEATER-Firmware, Bluetooth-Kopplung und Reichweite. Die geerbte Firmwaregrundlage ist laut Ausgangsdokumentation Commit `06d456ca232bc26784d3349f79f222de41436921` des offiziellen ESP32-Repositories. Die vorhandene Bitmasken-/Pitmaster-/Readback-Logik bleibt unverändert.
- Keine aktuelle vollständige Prüfung gegen offizielle JSON-Schemas und keine Module-Store-Freigabe. Frühere Schema- und Testergebnisse der Ausgangsversion werden für diese Version nicht als ausgeführt ausgegeben.

## Wiederholung

```bash
find WLANThermo libs tests -name '*.php' -print0 | xargs -0 -n1 php -l
php -n tests/run.php
python3 tests/structure.py
python3 tests/http_transport.py php
```

HTTP-Tests benötigen PHP-cURL. Die Strukturprüfung benötigt nur Python 3. Das CI führt dieselben Prüfungen aus.

## Prüfung am Zielsystem

1. Bestehende Instanz über Module Control aktualisieren und GUIDs beibehalten.
2. Host und Zugangsdaten übernehmen; Online, Temperatur, Zeitstempel sowie Schreibschutz prüfen.
3. Eine archivierte Variable deaktivieren: ID und letzter Wert bleiben, Variable ist ausgeblendet und nicht bedienbar. Wieder aktivieren: gleiche ID, neue Messwerte, Archiv bleibt zugeordnet.
4. Namen, Position und Darstellungswahl ändern; Konsole und Visualisierung prüfen. Dienst neu starten und gespeicherte Auswahl kontrollieren.
5. Mini V3 ausschalten: Standby und fortgesetzter Timer. Wiedereinschalten: Aktivstatus und aktuelle Werte.
6. Kanalgrenzen, Pitmaster und Bluetooth-Auswahl am Gerät gegenprüfen.

Die aktuellen offiziellen Symcon-Quellen und die dazugehörigen technischen Entscheidungen sind in README.md verlinkt. Es wird keine automatische Veröffentlichung durchgeführt.
