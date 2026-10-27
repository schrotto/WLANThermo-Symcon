# Lieferumfang A–H – WLANThermo 1.3.0

## A. Modulübersicht

WLANThermo Mini V3 lokal per HTTP mit Temperaturkanälen, Bluetooth/MEATER und Pitmaster. IP-Symcon ab 9.0, PHP-cURL, Netzwerkzugriff auf das Gerät. Alle Status- und Datenvariablen einzeln auswählbar; vorhandene Daten bei Abwahl geschützt. Mindestversion aus dem Ausgangsmodul beibehalten.

## B. Verzeichnisstruktur

```text
library.json
LICENSE
README.md
.gitignore
.github/workflows/ci.yml
WLANThermo/
  module.json
  module.php
  form.json
libs/
  Api.php
docs/
  CHANGELOG.md
  LIEFERUMFANG.md
  STORE.md
  TESTAUSGABE.txt
  VALIDIERUNG.md
  VARIABLEN.md
tests/
  Runtime.php
  run.php
  structure.py
  http_transport.py
  transport.php
  fixtures/
    data.json
    settings.json
    bluetooth.json
```

## C. Sämtliche Quelldateien

Alle Dateien liegen vollständig und ohne ausgelassene Kernfunktionen in der Bibliothek sowie im ZIP-Paket vor. Die Modulimplementierung ist `WLANThermo/module.php`, der HTTP-/Validierungsteil `libs/Api.php`. Die Test-Simulation wird nur von Tests geladen. Die deutsche Oberfläche benötigt keine zusätzliche locale.json. Ein Hochladen des Bibliotheksinhalts in ein eigenes Git-Repository macht ihn über Module Control installierbar; eine Veröffentlichung ist nicht erfolgt.

## D. Konfigurationsoberfläche

Fünf Bereiche: Allgemeine Einstellungen mit Aktivierung/Host/Anmeldung/Intervall; Geräte und Datenquellen mit automatischer Erkennung und Bluetooth-Abfrage; Variablenverwaltung mit Gruppen und Tabelle; Erweiterte Einstellungen mit Schreibschutz/Timeout/Rohdaten; Diagnose und Aktionen mit letztem Hinweis, Verbindungstest und manuellen Abrufen. Gerätespezifische Bluetooth-Panels und die bisherigen JSON-Schreibfunktionen bleiben verfügbar. Details in README.md.

## E. Variablenübersicht

Siehe VARIABLEN.md: stabile Idents, Typen, Einheiten, Darstellungen, Funktionen und Standardaktivierung einschließlich des vollständigen Beispielkatalogs der Fixtures. Die Tabelle in der echten Instanz enthält alle dort erkannten Felder.

## F. Installationsanleitung

README.md enthält die Schritte für direkte Dateikopie und Module Control, Erstkonfiguration sowie Update einer bestehenden Instanz. Bibliotheks- und Modul-GUIDs bleiben erhalten. Das ZIP enthält eine Bibliothek; es kann nicht direkt als Repository-URL verwendet werden.

## G. Tests und Kompatibilität

124 Modulprüfungen, 8 HTTP-Prüfungen, PHP-Syntax und JSON-/Strukturprüfung erfolgreich. Alle Nachweise und verbleibenden Zielsystemprüfungen stehen in VALIDIERUNG.md. Keine laufende Symcon-Installation und keine reale Hardware wurden hier geprüft.

## H. Verwendete Dokumentation

Aktuelle offizielle Symcon-Dokumentation zu IPSModuleStrict, MaintainVariable, Darstellungen, Konfigurationslisten, Objektverwaltung und Store-Review vor der Umsetzung gelesen. README.md verlinkt die Seiten und begründet die Entscheidungen. Die WLANthermo-API wurde aus dem bestehenden Modul übernommen; ihre bisherige Firmwarebasis ist im Prüfbericht ausdrücklich als übernommen gekennzeichnet.
