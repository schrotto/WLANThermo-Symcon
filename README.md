# WLANThermo für IP-Symcon

PHP-Modul für **IP-Symcon 9.0**, **WLANThermo Mini V3** und daran angebundene **MEATER-/Bluetooth-Fühler**. Kommunikation lokal über HTTP; ein MQTT-Broker und ein MEATER-Cloud-Konto sind nicht erforderlich. Unabhängiges Community-Modul, kein offizielles Produkt von Symcon, WLANThermo oder MEATER.

## Update auf Version 1.3.0

Die neue Version ergänzt eine frei konfigurierbare Variablenverwaltung mit Aktivierung, eigenem Namen, Datentyp, Darstellung und Position. Die Oberfläche ist in Allgemeine Einstellungen, Geräte und Datenquellen, Variablenverwaltung, Erweiterte Einstellungen sowie Diagnose und Aktionen gegliedert.

**Abwählen löscht keine Variablen mehr.** Vorhandene Variablen werden nach Übernehmen ausgeblendet, nicht mehr aktualisiert und ihre Bedienaktionen abgeschaltet. Werte, IDs, Referenzen und Archivzuordnungen bleiben erhalten. Beim Wiederaktivieren wird dieselbe Variable verwendet; ihre vorherige Sichtbarkeit wird wiederhergestellt. Variablen, die bereits mit einer früheren Version gelöscht wurden, können durch dieses Update nicht wiederhergestellt werden.

Alle bisherigen Gruppen und Einzelentscheidungen werden übernommen. Eine deaktivierte Gruppe hat Vorrang vor der Einzelwahl. Noch nie angelegte, deaktivierte Variablen werden nicht erzeugt. Die Auswahl wirkt ausschließlich auf Symcon; die API und Skriptfunktionen bleiben verfügbar. Auch alle Statusvariablen sind optional: Instanzstatus, `WLT_GetLastError` und `WLT_GetSnapshot` funktionieren unabhängig davon. Automationen dürfen deaktivierte Variablen nicht als aktuelle Datenquelle verwenden; deren letzter Wert bleibt absichtlich unverändert.

Die Tabelle zeigt alle bisher erkannten API-Felder. Nach dem ersten erfolgreichen Abruf das Formular erneut öffnen. Pro Zeile sind folgende Einstellungen möglich:

| Spalte | Wirkung |
| --- | --- |
| Aktiv | Aktualisieren und gegebenenfalls neu anlegen; deaktiviert bedeutet vorhandene Variable erhalten und ausblenden |
| Bereich / Variable / Ident / Typ | Automatisch ermittelte Informationen; der Ident bleibt unverändert |
| Eigener Name | Lokale Symcon-Beschriftung; leer lässt den bisherigen Namen bestehen. Den Fühlernamen am Gerät ändert weiterhin die Kanalvariable `CHn_Name`. |
| Darstellung | Automatisch: aktuelle Symcon-Darstellung mit Einheit und passenden Bedienelementen. Wertanzeige: reine Anzeige, bei Temperaturen weiterhin mit Einheit. Bisheriges Profil: kompatible WLT-Profile für ältere Visualisierungen. |
| Position | Reihenfolge im Objektbaum; -1 lässt bestehende Positionen bestehen und nutzt bei Neuanlage die Standardposition |

Name und Position werden bei einer Änderung der Tabellenwerte angewendet, auch wenn das Gerät offline ist. Spätere manuelle Objektänderungen werden durch Polling nicht überschrieben. Die Standarddarstellung kann weiterhin in Symcon mit einer benutzerdefinierten Darstellung übersteuert werden; solche Anpassungen und die Archivkonfiguration verändert das Modul nicht. Einheiten folgen dem Gerät: Celsius oder Fahrenheit, ohne Umrechnung. Alarmgrenzen und Solltemperaturen behalten Float-Typen und sind in ganzen Grad bedienbar.

Das Modul verwendet `IPSModuleStrict` und die seit Symcon 8.0 dokumentierten Darstellungen. Die Mindestversion bleibt wie im Ausgangsmodul **IP-Symcon 9.0**; es wird keine aktuellere Stable-Version vorausgesetzt oder behauptet. PHP-cURL und Netzwerkzugriff auf den Mini V3 sind erforderlich; fehlendes cURL wird beim Abruf verständlich gemeldet. Kein MQTT-Broker und keine externe Bibliothek sind erforderlich.

Zum Aktualisieren die vollständige Bibliothek ersetzen bzw. im eigenen Git-Repository übernehmen und über Module Control aktualisieren. **Die bestehende Instanz beibehalten**: Modul-GUID, Bibliotheks-GUID, Präfix und Idents sind unverändert. Bei direkter Dateikopie den Symcon-Dienst neu starten. Bereits eingerichtete Geräteparameter bleiben erhalten. Die vorbereitete Repositoryadresse lautet `https://github.com/schrotto/WLANThermo-Symcon`; dieses Paket veröffentlicht kein Repository.

Ausgeschaltete Geräte bleiben im Status **106 / Standby**. Der Timer versucht weiter zu lesen; bei Rückkehr des Geräts wird **102 / Aktiv** hergestellt. Authentifizierungs-, Zertifikats- und API-Fehler bleiben Fehler. Unter Diagnose stehen ein Verbindungstest, manueller Abruf, Bluetooth-Aktualisierung und der letzte Hinweis zur Verfügung. Der Verbindungstest führt einen regulären Datenabruf aus. Der Hinweistext wird beim erneuten Öffnen des Formulars aktualisiert.

Vollständige [Variablenübersicht](docs/VARIABLEN.md), [Prüfbericht](docs/VALIDIERUNG.md), [Lieferumfang A–H](docs/LIEFERUMFANG.md) und [Veröffentlichungshinweise](docs/STORE.md) liegen bei.

## Funktionsumfang

- Liest `/data` automatisch (Standard 10 Sekunden), `/settings` alle 5 Minuten und `/getbluetooth` pro Abruf. Manuelle Aktualisierung verfügbar.
- Übernimmt alle von diesen drei Antworten gelieferten Datenfelder: alle Temperaturkanäle, Systemwerte, WLAN-Signal, Akkuinformationen soweit vorhanden, Gerätemodell, Firmware, Fühlertypen, PID-Profile und weitere Einstellungen. Zusätzliche Felder können rekursiv als lesbare Statusvariablen angelegt werden. Gruppen und einzelne Variablen lassen sich über Kontrollkästchen auswählen. Schlüssel, die Zugangsdaten enthalten, werden ausgeblendet.
- Kanalvariablen für Temperatur, Name, Fühlertyp, Alarm Minimum/Maximum, Alarmierungsart, Farbe, festen Fühlertyp und Funkverbindungsstatus. Name, Grenzen, Alarmierungsart, Farbe und veränderbare Fühlertypen sind bedienbar.
- Kennzeichnet fehlende Messwerte (`999`), getrennte Bluetooth-Fühler, entfernte Kanäle und Geräteausfälle. Die letzte Temperatur bleibt sichtbar; `Valid` und `Online` zeigen, ob sie aktuell verwendbar ist.
- Pitmaster: Betriebsart, Solltemperatur, manueller Stellwert, Kanal und PID-Profil. PID-Profile und Systemeinstellungen über JSON-Eingabe; MQTT-/Cloud-Einstellungen ebenfalls über JSON.
- Bluetooth-Liste mit Geräteadresse, Name und gemeldeter Sensoranzahl. Individuelle Checkboxen für jeden Sensorkanal, auch bei mehreren gleichnamigen MEATER-Geräten. Jede Änderung wird sofort gesendet und zurückgelesen.
- HTTP Basic/Digest-Anmeldung, konfigurierbare Zeitlimits, Sperre gegen parallele Abfragen und ein schreibgeschützter Betriebsmodus.

## Installation

Das ZIP enthält eine vollständige Modulbibliothek. Es wurde noch kein öffentliches Git-Repository und kein Module-Store-Eintrag angelegt.

### Direkt auf dem Symcon-Server

1. ZIP entpacken. Den äußeren Ordner `WLANThermo` mit `library.json`, `libs/` und dem inneren Ordner `WLANThermo/` in das Verzeichnis `modules` deiner Symcon-Installation kopieren.
2. Prüfen, dass die Struktur so aussieht:

   ```text
   <Symcon-Datenverzeichnis>/modules/WLANThermo/library.json
   <Symcon-Datenverzeichnis>/modules/WLANThermo/WLANThermo/module.json
   <Symcon-Datenverzeichnis>/modules/WLANThermo/WLANThermo/module.php
   <Symcon-Datenverzeichnis>/modules/WLANThermo/WLANThermo/form.json
   <Symcon-Datenverzeichnis>/modules/WLANThermo/libs/Api.php
   ```

3. Symcon-Dienst neu starten. Bei lokaler Modulentwicklung beschreibt die offizielle SDK-Dokumentation diesen Neustart.
4. In der Verwaltungskonsole eine Instanz **WLANThermo** unter Hersteller **WLANThermo** anlegen.
5. IP-Adresse des Mini V3 eintragen, beispielsweise `192.168.178.50`. Als Benutzername ist `admin` vorbelegt. Falls am WLANthermo ein Admin-Passwort gesetzt ist, dieses eintragen.
6. Konfiguration übernehmen. `Gerät erreichbar` muss anschließend wahr sein, die Kanäle werden automatisch erstellt.

Das tatsächliche Datenverzeichnis hängt vom Betriebssystem ab. Bei einer SymBox ohne Dateizugriff den Weg über ein eigenes Git-Repository verwenden. Das ZIP lässt sich nicht unmittelbar als URL im Module Control eintragen.

### Über ein eigenes Git-Repository

Den **Inhalt** des äußeren Ordners in ein Git-Repository hochladen; `library.json` muss direkt im Repository-Hauptverzeichnis liegen. In Symcon unter **Kerninstanzen → Modules / Module Control → Hinzufügen** die HTTPS-Repository-URL eintragen. Anschließend die Instanz wie oben anlegen. Dieser Weg ermöglicht spätere Updates über Module Control. Eine Veröffentlichung wurde hier nicht ausgeführt.

## MEATER einrichten

1. Fühler laden und aus der Ladeschale nehmen. Bluetooth am Mini V3 aktivieren.
2. Eine konkurrierende Verbindung zur MEATER-App beziehungsweise zum MEATER-Block trennen. Laut WLANthermo kann die Sonde jeweils nur eine aktive Bluetooth-Verbindung bedienen.
3. In der Symcon-Instanz **Bluetooth-Liste aktualisieren** drücken. Die Taste liest die vom Mini V3 gefundene Geräteliste erneut; sie startet keinen eigenen Bluetooth-Scan im Symcon-Server.
4. Das passende Gerät anhand der MAC-Adresse auswählen und die gewünschten **Sensorkanäle** anhaken. Jeder Haken wird sofort gespeichert. Beim klassischen MEATER meldet die API zwei Kanäle. Neuere Modelle können weitere Messstellen liefern; es wird die tatsächlich gemeldete Anzahl verwendet.
5. Danach erscheinen die vom Mini V3 zugewiesenen Temperaturkanäle automatisch in Symcon. Ihre Namen stammen vom WLANthermo. Es werden keine Messstellenbezeichnungen aus einer vermuteten MEATER-Modellversion abgeleitet.

Falls kein Gerät erscheint, zuerst im WLANthermo-Webinterface unter Bluetooth prüfen. Das Symcon-Modul kann nur Geräte nutzen, die der Mini V3 selbst unterstützt und erkennt. Für MEATER 2/Pro/Duo/XL hängt die Unterstützung von WLANthermo- und Bluetooth-Firmware ab; die Release Notes und die verlinkten Hinweise des WLANthermo-Teams beachten. Firmware-Updates werden nicht automatisch durchgeführt.

`selected` ist eine Bitmaske: `1` = erster Sensorkanal, `2` = zweiter, `3` = beide; `63` = sechs Kanäle. Die grafischen Checkboxen erledigen diese Umrechnung.

## Bedienung und Variablen

| Ident | Bedeutung |
| --- | --- |
| `Online` | Letzter vollständiger Hauptabruf erfolgreich |
| `LastUpdate` | Zeitpunkt des letzten erfolgreichen Hauptabrufs |
| `LastError` | Letzter Abruf-/Schreibfehler |
| `BluetoothError` | Fehler nur der optionalen Bluetooth-Verwaltung |
| `CH1_Temperature` | Temperatur von API-Kanal 1 |
| `CH1_Valid` | Aktueller, verwendbarer Messwert |
| `CH1_Present` | Kanal in letzter erfolgreicher Antwort vorhanden |
| `CH1_Connected` | Funkstatus laut Firmware; für kabelgebundene Fühler nicht als Gültigkeitsprüfung verwenden |
| `CH1_OutOfRange` | Gültige Temperatur außerhalb der eingestellten Grenzen |
| `CH1_Name`, `CH1_Min`, `CH1_Max`, `CH1_Alarm`, `CH1_Type`, `CH1_Color` | Bedienbare Kanalparameter, sofern vom Gerät veränderbar |
| `PM0_Mode`, `PM0_Set`, `PM0_Value`, `PM0_Channel`, `PM0_PID` | Pitmaster-Parameter |
| `BluetoothEnabled` | Bluetooth am WLANthermo aktivieren/deaktivieren |
| `SYS_*`, `SET_*`, `BLE_*`, `EXTRA*`, `PMEXTRA*`, `DATA_*`, `PIT_*` | Weitere API-Felder, lesbar; Anzeigename enthält den JSON-Pfad |
| `RawData`, `RawSettings`, `RawBluetooth` | Optionale JSON-Diagnosevariablen |

Temperaturen und Eingaben verwenden die **aktuelle Geräteeinheit** Celsius/Fahrenheit. Das Modul rechnet keine Messwerte um. Die Temperatur `999` ist ein Fehler-/Inaktivwert und keine gültige Temperatur. Alarmierungsart: `0` Aus, `1` Push, `2` Summer, `3` Push und Summer. Push-Dienste werden am WLANthermo eingerichtet; das Modul erzeugt mit `OutOfRange` zusätzlich einen nutzbaren Symcon-Status.

Alle Kanalparameter können über die Variablen beziehungsweise über **Erweiterte Einstellungen (JSON)** im Instanzformular geändert werden. Feste Fühlertypen bleiben geschützt. Solltemperatur ist nur im Modus `auto`, Stellwert nur im Modus `manual` schreibbar. Für den gleichzeitigen Wechsel von Modus und Sollwert die JSON-Eingabe verwenden.

Bei Geräten ohne Akku erscheinen keine erfundenen Akkuwerte. Die Bluetooth-Verwaltungs-API liefert Name, MAC-Adresse, Sensoranzahl und Auswahl; beispielsweise MEATER-Akkustand, Restgarzeit oder MEATER-App-Garprogramme werden darüber nicht bereitgestellt.

## Skriptbeispiele

`12345` durch die Instanz-ID ersetzen. Die Funktionen liefern `true` bei Erfolg oder `false` bei Fehlern; Details stehen in `LastError` oder, falls abgewählt, in `WLT_GetLastError(12345)`. Schreibvorgänge werden nach HTTP-Antwort nochmals am Gerät gelesen und geprüft.

```php
// Sofort lesen.
WLT_Update(12345);

// Alarmgrenzen und Namen setzen (maximal 10 Unicode-Zeichen).
WLT_SetChannel(12345, 1, '{"name":"Garraum","min":90.0,"max":130.0,"alarm":3}');

// Pitmaster 0 gemeinsam auf Automatik und 120 Grad stellen.
WLT_SetPitmaster(12345, 0, '{"typ":"auto","channel":1,"pid":0,"set":120.0}');

// Manuelle Ausgabe und Ausschalten.
WLT_SetPitmaster(12345, 0, '{"typ":"manual","value":30}');
WLT_SetPitmaster(12345, 0, '{"typ":"off"}');

// Bluetooth aktualisieren; MAC aus der eigenen Geräteliste verwenden.
WLT_RefreshBluetooth(12345);
WLT_SetBluetoothEnabled(12345, true);
WLT_SetBluetoothSelection(12345, 'd0:d9:4f:88:de:ce', 3);

// Einzelnen Sensorkanal auswählen: Index beginnt bei 0.
WLT_SetBluetoothChannel(12345, 'd0:d9:4f:88:de:ce', 0, true);

// System und PID-Profil ändern.
WLT_SetSystem(12345, '{"unit":"C","language":"de"}');
WLT_SetPID(12345, 0, '{"Kp":3.8,"Ki":0.01,"Kd":128.0,"opl":true}');

// MQTT am WLANthermo konfigurieren (Modul selbst verwendet weiterhin HTTP).
WLT_SetIoT(12345, '{"PMQhost":"192.168.178.10","PMQport":1883,"PMQon":true,"PMQint":10}');

// Zuletzt gelesene Daten als JSON, mit ausgeblendeten Zugangsdaten.
$snapshot = json_decode(WLT_GetSnapshot(12345), true);
```

Erlaubte JSON-Felder:

| Funktion | Änderbare Felder |
| --- | --- |
| `SetChannel` | `name`, `typ`, `min`, `max`, `alarm`, `color` |
| `SetPitmaster` | `channel`, `pid`, `value`, `set`, `typ` |
| `SetSystem` | `ap`, `host`, `language`, `unit`, `autoupd`, `prerelease` |
| `SetPID` | `name`, `aktor`, `Kp`, `Ki`, `Kd`, `DCmmin`, `DCmmax`, `opl`, `SPmin`, `SPmax`, `link`, `tune`, `jp` |
| `SetIoT` | `PMQhost`, `PMQport`, `PMQuser`, `PMQpass`, `PMQqos`, `PMQon`, `PMQint`, `CLon`, `CLtoken`, `CLint`, `CCLon`, `CCLurl`, `CCLint` |

JSON-Zahlen als Zahlen und boolesche Werte als `true`/`false` übergeben. Fühlertyp-IDs, PID-IDs und Aktortypen werden gegen die Gerätedaten geprüft. Firmware kann Felder ignorieren oder einschränken; dann meldet die Rückleseprüfung einen Fehler. Erweiterte Custom-Cloud-Felder sind firmwareabhängig.

## Archivierung und Automationen

Die gewünschten Temperaturvariablen in Symcon zur Archivierung aktivieren; das Modul verändert die Archivkonfiguration nicht. Für Temperaturalarme `Online`, `CHn_Present` und `CHn_Valid` gemeinsam prüfen. Bei Geräteausfall bleiben die letzten Messwerte erhalten, werden aber ungültig markiert. Ein Fehler allein in `/getbluetooth` beeinträchtigt die weiterhin über `/data` verfügbaren Messwerte nicht.

Kanalnummern werden vom Mini V3 vergeben. Beim Entfernen oder Neuzuordnen von Bluetooth-Kanälen können Nummern und damit deren Zuordnung wechseln. Deshalb nach einer solchen Änderung die Namen und Archiv-/Automationszuordnungen kontrollieren. Entfernte Kanalvariablen werden zur Erhaltung bestehender Referenzen nicht automatisch gelöscht. Sonstige zusätzliche API-Felder behalten bei Wegfall ihren letzten Wert; ihre Aktualität ist über `LastUpdate`/`GetSnapshot()` einzuordnen.

Die API-Antworten werden vollständig ausgelesen, soweit es die drei implementierten Datenendpunkte betrifft. Nicht implementiert sind direkte Bluetooth-Verbindungen vom Symcon-Server, MEATER-Cloud, WLAN-Zugangsdatenänderung, Firmware-Update, Werksreset sowie Verwaltungs-/Diagnoseendpunkte außerhalb dieser Schnittstellen. Version 1.3.0 wurde hier automatisiert geprüft; ein erneuter Test der Änderungen am realen Mini V3 und in Symcon 9.0 steht noch aus. Details zur geprüften Firmwarebasis und zu den Tests stehen in `docs/VALIDIERUNG.md`.

## Offizielle Grundlagen

- [Symcon PHP-SDK](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
- [Modulstruktur](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/struktur/), [IPSModuleStrict](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
- [Module Control](https://www.symcon.de/en/service/documentation/module-reference/core-instances/module-control/)
- [WLANThermo API-Übersicht](https://wlanthermo.de/threads/api-informationen-zur-softwareschnittstelle.151/)
- [Offizielle ESP32 HTTP-API](https://github.com/WLANThermo-nano/WLANThermo_ESP32_Software/wiki/HTTP)
- [MEATER am Mini V3](https://wlanthermo.de/threads/meater-als-normalen-temperaturf%C3%BChler-im-mini-v3-aktivieren.2744/)
- [Neuere MEATER-Modelle: Auskünfte des WLANthermo-Teams](https://wlanthermo.de/threads/kompatibilit%C3%A4t-meater1-1-meater2-tempspike-testlauf.4566/page-7)
- [Offizielle Firmware-Releases](https://github.com/WLANThermo-nano/WLANThermo_ESP32_Software/releases)
- [Offizieller HTTP-/Bluetooth-Handler, geprüfter Stand](https://github.com/WLANThermo-nano/WLANThermo_ESP32_Software/blob/06d456ca232bc26784d3349f79f222de41436921/src/WebHandler.cpp)
- [Offizielle Bluetooth-Bedienoberfläche](https://github.com/WLANThermo-nano/WLANThermo_ESP32_Software/blob/06d456ca232bc26784d3349f79f222de41436921/webui/src/components/Bluetooth.vue)

Die Firmwaregrundlage und die Besonderheiten der HTTP-/Bluetooth-Anbindung wurden aus der Dokumentation der Ausgangsversion übernommen. In Version 1.3.0 wurden diese Endpunkte nicht erweitert; ihre Kompatibilität mit dem Benutzergerät ist weiterhin am Zielsystem zu prüfen. Das Modul wurde eigenständig implementiert; Firmwarecode wird nicht mitgeliefert.

### Technische Entscheidungen in Version 1.3.0

- [MaintainVariable](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/maintainvariable/): nur mit `true` aufrufen, weil `false` eine Variable löscht.
- [Darstellungen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/darstellungen/): Temperatur als Wertanzeige mit °C/°F, Sollwerte als Schieberegler in 1-Grad-Schritten, Prozent als 0–100, Modi und Alarmierung als Aufzählung, Zeitstempel als Datum/Uhrzeit, Bluetooth als Schalter.
- [Konfigurationslisten](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/konfigurationsformulare/list/): gespeicherte Auswahl und neue Felder nach stabilem Ident zusammenführen; Änderungen ausschließlich mit Übernehmen speichern.
- [Store-Reviewvorgaben](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/): Name, Position und Sichtbarkeit nur als Folge der im Formular erklärten Benutzerauswahl verändern; keine Änderungen an fremden Objekten, Archivierung oder benutzerdefinierten Profilen/Aktionen.
- [IPS_SetHidden](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/objektverwaltung/ips-sethidden/) und [IPS_GetObject](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/objektverwaltung/ips-getobject/): vorherige Sichtbarkeit persistent merken und bei Reaktivierung wiederherstellen.

Die Oberfläche ist unmittelbar deutsch beschriftet; eine `locale.json` ist daher für die mitgelieferte deutsche Sprache nicht erforderlich. Externe Symcon-Variablen werden nicht eingebunden; ein `SelectVariable` wäre dafür ohne Funktion. Es gibt keinen Parent-/Child-Datenfluss und deshalb keine leeren ReceiveData-/ForwardData-Methoden. Die Ressourcen bestehen ausschließlich aus dem registrierten Timer und werden von der Symcon-Basisklasse verwaltet.
