# Validierung Version 1.2.0 – 18. Oktober 2026

Zielsystem: IP-Symcon 9.0 und WLANThermo Mini V3 mit MEATER-Fühlern. PHP-Klasse basiert auf `IPSModuleStrict`. Die tatsächlich installierte WLANthermo-Firmware und die konkreten MEATER-Modelle sind unbekannt.

## Geprüfte Grundlagen

- Offizielle Symcon-Dokumentation: Bibliotheks-/Modulstruktur, `IPSModuleStrict`, `RequestAction`, Timer, Konfigurationsformulare und benannte Ereignisvariablen.
- Offizielle WLANThermo ESP32 HTTP-API: `/data`, `/settings`, `/setchannels`, `/setsystem`, `/setpitmaster`, `/setpid`, `/setIoT`.
- Offizielles Repository `WLANThermo-nano/WLANThermo_ESP32_Software`, Commit **06d456ca232bc26784d3349f79f222de41436921**. Bluetooth-Geräteabfrage, Auswahlmaske, Payloads und Authentifizierung anhand von `src/WebHandler.cpp`, API-Felder anhand von `src/API.cpp`; Bluetooth-Bedienung zusätzlich anhand der offiziellen `Bluetooth.vue`.

Zwei Besonderheiten dieses Firmwarestands sind berücksichtigt:

1. Der Pitmaster-Schreibhandler greift positionsbasiert auf den Pitmaster zu. Das Modul übergibt deshalb die vollständige geordnete Liste, damit eine Änderung von Pitmaster 1 nicht Pitmaster 0 trifft. Andere Pitmaster werden mit ihren frisch gelesenen Parametern übertragen.
2. `setBluetooth()` hat im untersuchten Firmwarecode keinen definierten C++-Rückgabewert. Die HTTP-Antwort ist deshalb allein keine verlässliche Bestätigung. Das Modul prüft Bluetooth-Änderungen ausschließlich durch erneutes Lesen der tatsächlichen Auswahl beziehungsweise des Aktivierungsstatus. Bei anderen Schreibendpunkten werden zusätzlich die booleschen HTTP-Antworten ausgewertet.

## Ausgeführte Prüfungen

- PHP-Syntaxprüfung sämtlicher PHP-Dateien mit PHP 8.3.6.
- **95 erfolgreiche Modul-/API-Assertions** mit einer bewusst kleinen Symcon-Vertragssimulation. Geprüft wurden unter anderem Messwerte, MEATER-Kern-/Umgebungskanäle, mehrere gleichnamige Geräte, sechs Sensorkanäle, Formularevent-Ausführung, Bitmasken, Schreibschutz, Eingabevalidierung, PID/Pitmaster, geordnete Pitmaster-Payloads, Rücklesen, Fehlerantworten, ausgeblendete Zugangsdaten, Geräteausfall, Wiederanlauf, entfernte Kanäle und Fahrenheit.
- Zusätzlich geprüft: ganze Grad ohne Änderungen an gemessenen Nachkommastellen, Abweisen von Dezimaleingaben, Profilwechsel bei unveränderten Variablen-IDs, Gruppen- und Einzelauswahl, Übernahme gespeicherter Checkbox-Listeneinträge, dauerhaft unterbundene Wiederanlage, Wiederaktivierung mit neuer ID, Entfernen während Geräteausfall, Migration der bisherigen Variablenliste und vollständiges Abwählen aller Variablen bei weiterhin funktionierendem Abruf.
- **8 erfolgreiche HTTP-Assertions der unveränderten HTTP-Transportimplementierung aus Version 1.0.0 mit echtem PHP-cURL** gegen einen lokalen Python-HTTP-Server: GET, JSON-POST mit Unicode, Basic Auth, Digest Auth mit geprüfter MD5-Antwort, 401, 404, unterbundene Weiterleitung und Timeout.
- Eine erneute Ausführung des unveränderten HTTP-Tests in diesem Änderungslauf war wegen einer lokalen Socket-Sperre der Arbeitsumgebung nicht möglich. Die 8 Transportprüfungen stammen aus dem vorherigen Lauf; die 95 Modul-/API-Prüfungen wurden mit Version 1.2.0 erneut ausgeführt.
- Die neue Liste speichert `Ident` und `Enabled`; beschreibende Spalten werden nicht als Konfiguration gespeichert. Die Liste wird mit `loadValuesFromConfiguration=false` aus den tatsächlichen Benutzereinstellungen plus Katalog ergänzt. Migration aus der 1.1.0-Eigenschaft erfolgt lesend, ohne diese zu ändern. Tests prüfen unter anderem, dass das Laden des Formulars keine Eigenschaften ändert, Haken erst bei Übernahme wirken und die Laufzeit keine automatischen Eigenschafts-/Objektänderungen über verbotene Funktionen ausführt.
- `module.json`, statisches `form.json` und dynamisch erzeugtes Formular gegen die offiziellen Symcon-JSON-Schemas validiert.
- `library.json` strukturell ebenfalls validiert. Besonderheit: Das heruntergeladene offizielle `librarySchema.json` nennt im Versions-Enum nur Versionen bis 6.2. Für diese eine Prüfung wurde das lokale Enum um `9.0` ergänzt. Das Originalschema wurde nicht als aktuell für 9.0 ausgegeben. Die Modulbibliothek selbst verlangt ausdrücklich Version 9.0.
- JSON-Dateien geparst, Formularklasse und Modulname abgeglichen, Archivinhalt und Prüfsumme überprüft.

## Nicht ausgeführt

Der Anwender hat Version 1.0.0 als funktionierend bestätigt. Für die Änderungen in Version 1.2.0 waren hier kein realer Mini V3, kein MEATER-Gerät und keine laufende Symcon-9.0-Installation erreichbar. Die Tests bestätigen daher die Implementierung gegen dokumentierte und simulierte Schnittstellen; sie bestätigen noch keinen Hardwarebetrieb. Besonders Firmwareabweichungen, die Darstellung in der echten Konsole sowie die tatsächliche Bluetooth-Reichweite und Kopplung bleiben am Zielsystem zu prüfen.

Die Testdaten sind selbst erstellte, repräsentative Fixtures. Sie sind keine aufgezeichneten Antworten des Benutzergeräts. Im Test gemeldete Kanalnummern oder MAC-Adressen sind Beispiele, keine festen Gerätevorgaben.

## Wiederholen

```bash
php -n tests/run.php
python3 tests/http_transport.py php
```

Für den zweiten Befehl muss die verwendete PHP-Laufzeit cURL laden. Eine GitHub-Actions-Konfiguration liegt unter `.github/workflows/ci.yml` bei. Die Symcon-Simulation wird ausschließlich von den Tests geladen; das Modul selbst verwendet die echte Basisklasse.
