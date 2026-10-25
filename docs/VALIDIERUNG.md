# Validierung Version 1.2.1 – 25. Oktober 2026

Zielsystem: IP-Symcon 9.0 und WLANThermo Mini V3 mit MEATER-Fühlern. PHP-Klasse basiert auf `IPSModuleStrict`. Die tatsächlich installierte WLANthermo-Firmware und die konkreten MEATER-Modelle sind unbekannt.

## Geprüfte Grundlagen

- Offizielle Symcon-Dokumentation: Statuscodes (102 aktiv, 106 Standby, ab 200 Fehler), Bibliotheks-/Modulstruktur, `IPSModuleStrict`, `RequestAction`, Timer, Konfigurationsformulare und benannte Ereignisvariablen.
- Offizielle WLANThermo ESP32 HTTP-API: `/data`, `/settings`, `/setchannels`, `/setsystem`, `/setpitmaster`, `/setpid`, `/setIoT`.
- Offizielles Repository `WLANThermo-nano/WLANThermo_ESP32_Software`, Commit **06d456ca232bc26784d3349f79f222de41436921**. Bluetooth-Geräteabfrage, Auswahlmaske, Payloads und Authentifizierung anhand von `src/WebHandler.cpp`, API-Felder anhand von `src/API.cpp`; Bluetooth-Bedienung zusätzlich anhand der offiziellen `Bluetooth.vue`.

Zwei Besonderheiten dieses Firmwarestands sind berücksichtigt:

1. Der Pitmaster-Schreibhandler greift positionsbasiert auf den Pitmaster zu. Das Modul übergibt deshalb die vollständige geordnete Liste, damit eine Änderung von Pitmaster 1 nicht Pitmaster 0 trifft. Andere Pitmaster werden mit ihren frisch gelesenen Parametern übertragen.
2. `setBluetooth()` hat im untersuchten Firmwarecode keinen definierten C++-Rückgabewert. Die HTTP-Antwort ist deshalb allein keine verlässliche Bestätigung. Das Modul prüft Bluetooth-Änderungen ausschließlich durch erneutes Lesen der tatsächlichen Auswahl beziehungsweise des Aktivierungsstatus. Bei anderen Schreibendpunkten werden zusätzlich die booleschen HTTP-Antworten ausgewertet.

## Ausgeführte Prüfungen

- PHP-Syntaxprüfung sämtlicher PHP-Dateien mit PHP 8.3.6.
- **109 erfolgreiche Modul-/API-Assertions** mit einer bewusst kleinen Symcon-Vertragssimulation. Geprüft wurden unter anderem Messwerte, MEATER-Kern-/Umgebungskanäle, mehrere gleichnamige Geräte, sechs Sensorkanäle, Formularevent-Ausführung, Bitmasken, Schreibschutz, Eingabevalidierung, PID/Pitmaster, geordnete Pitmaster-Payloads, Rücklesen, Fehlerantworten, ausgeblendete Zugangsdaten, Geräteausfall, Wiederanlauf, entfernte Kanäle und Fahrenheit.
- Zusätzlich geprüft: ganze Grad ohne Änderungen an gemessenen Nachkommastellen, Abweisen von Dezimaleingaben, Profilwechsel bei unveränderten Variablen-IDs, Gruppen- und Einzelauswahl, Übernahme gespeicherter Checkbox-Listeneinträge, dauerhaft unterbundene Wiederanlage, Wiederaktivierung mit neuer ID, Entfernen während Geräteausfall, Migration der bisherigen Variablenliste und vollständiges Abwählen aller Variablen bei weiterhin funktionierendem Abruf.
- Zusätzlich geprüft: Ausschalten und wiederholte Nichterreichbarkeit führen zu Standby (106), der Timer bleibt aktiv, auch bei Start/Übernahme mit ausgeschaltetem Gerät. Messwertzeitpunkt und letzte Temperatur bleiben erhalten, Gültigkeit und Bedienaktionen werden deaktiviert. Wiedereinschalten stellt Aktivstatus, Gültigkeit und Aktionen automatisch wieder her. Dasselbe Verhalten funktioniert ohne angelegte Statusvariablen. Anmelde-, Zertifikats- und JSON-Fehler bleiben im Fehlerstatus.
- Ein zusätzlicher Aufruf der tatsächlichen PHP-cURL-Implementierung gegen einen nicht erreichbaren lokalen Anschluss (`127.0.0.1:1`) ergab cURL 7 und wurde korrekt als `WLTUnavailableException` eingeordnet. Für DNS-/Verbindungs-/Timeout-/Verbindungsabbruchfehler verwendet der Transport diese eigene Exception; andere Transportfehler bleiben echte Fehler.
- Die früheren **8 erfolgreichen HTTP-Assertions aus Version 1.0.0** sind nur historisch dokumentiert. Der Transport wurde für 1.2.1 um die Unterscheidung von Erreichbarkeitsfehlern erweitert; der HTTP-Test prüft jetzt zusätzlich die Fehlerklasse bei Timeout. Der vollständige lokale HTTP-Testlauf war in dieser Arbeitsumgebung wegen einer Socket-Sperre nicht ausführbar und wird nicht als aktueller erfolgreicher Test ausgegeben. Die GitHub-Actions-Konfiguration führt ihn in einer geeigneten Umgebung aus.
- Die neue Liste speichert `Ident` und `Enabled`; beschreibende Spalten werden nicht als Konfiguration gespeichert. Die Liste wird mit `loadValuesFromConfiguration=false` aus den tatsächlichen Benutzereinstellungen plus Katalog ergänzt. Migration aus der 1.1.0-Eigenschaft erfolgt lesend, ohne diese zu ändern. Tests prüfen unter anderem, dass das Laden des Formulars keine Eigenschaften ändert, Haken erst bei Übernahme wirken und die Laufzeit keine automatischen Eigenschafts-/Objektänderungen über verbotene Funktionen ausführt.
- `module.json`, statisches `form.json` und dynamisch erzeugtes Formular gegen die offiziellen Symcon-JSON-Schemas validiert.
- `library.json` strukturell ebenfalls validiert. Besonderheit: Das heruntergeladene offizielle `librarySchema.json` nennt im Versions-Enum nur Versionen bis 6.2. Für diese eine Prüfung wurde das lokale Enum um `9.0` ergänzt. Das Originalschema wurde nicht als aktuell für 9.0 ausgegeben. Die Modulbibliothek selbst verlangt ausdrücklich Version 9.0.
- JSON-Dateien geparst, Formularklasse und Modulname abgeglichen, Archivinhalt und Prüfsumme überprüft.

## Nicht ausgeführt

Der Anwender hat Version 1.0.0 als funktionierend bestätigt. Für die Änderungen in Version 1.2.1 waren hier kein realer Mini V3, kein MEATER-Gerät und keine laufende Symcon-9.0-Installation erreichbar. Die Tests bestätigen daher die Implementierung gegen dokumentierte und simulierte Schnittstellen; sie bestätigen noch keinen Hardwarebetrieb. Besonders Firmwareabweichungen, die Darstellung in der echten Konsole sowie die tatsächliche Bluetooth-Reichweite und Kopplung bleiben am Zielsystem zu prüfen.

Die Testdaten sind selbst erstellte, repräsentative Fixtures. Sie sind keine aufgezeichneten Antworten des Benutzergeräts. Im Test gemeldete Kanalnummern oder MAC-Adressen sind Beispiele, keine festen Gerätevorgaben.

## Wiederholen

```bash
php -n tests/run.php
python3 tests/http_transport.py php
```

Für den zweiten Befehl muss die verwendete PHP-Laufzeit cURL laden. Eine GitHub-Actions-Konfiguration liegt unter `.github/workflows/ci.yml` bei. Die Symcon-Simulation wird ausschließlich von den Tests geladen; das Modul selbst verwendet die echte Basisklasse.

Statuscode-Grundlage: [Offizielle SetStatus-Dokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setstatus/).
