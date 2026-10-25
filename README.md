# WLANThermo für IP-Symcon

PHP-Modul für **IP-Symcon 9.0**, **WLANThermo Mini V3** und daran angebundene **MEATER-/Bluetooth-Fühler**. Kommunikation lokal über HTTP; ein MQTT-Broker und ein MEATER-Cloud-Konto sind nicht erforderlich. Unabhängiges Community-Modul, kein offizielles Produkt von Symcon, WLANThermo oder MEATER.

## Update auf Version 1.2.1

**Neu: ausgeschaltetes Gerät ohne fehlerhafte Instanz.** Ist das WLANthermo ausgeschaltet, antwortet nicht oder ist die Netzwerkverbindung unterbrochen, verwendet das Modul den normalen Symcon-Status **106 / Standby**. Im Formular steht „WLANthermo ausgeschaltet oder nicht erreichbar“. Das Modul kann den Geräteschalter nicht aus der Ferne erkennen; deshalb gilt dasselbe Verhalten auch bei einem Netzwerkausfall.

`Online` wird falsch, `CHn_Valid` wird falsch und Bedienaktionen werden deaktiviert. Die letzten Temperaturwerte und der Zeitpunkt ihres Abrufs bleiben erhalten. Der Abruf-Timer läuft weiter: Sobald das Gerät wieder antwortet, wechselt die Instanz automatisch auf **102 / Aktiv**, aktualisiert die Messwerte und aktiviert die passenden Aktionen. Dafür muss die Moduleigenschaft **Aktiv** eingeschaltet bleiben. Ein manueller Neustart der Instanz ist nicht nötig.

Der letzte technische Erreichbarkeitshinweis bleibt in `LastError` beziehungsweise `WLT_GetLastError` nachlesbar und wird bei erfolgreichem Abruf gelöscht. Erreichbarkeitsprobleme erzeugen aber keinen Instanz-Fehlerstatus mehr. Konfigurations-, Anmelde-, Zertifikats- und API-Fehler bleiben weiterhin als Fehler erkennbar.

Autor und vorgesehener GitHub-Account: **schrotto**. Die Metadaten sind für das noch anzulegende Repository **`https://github.com/schrotto/WLANThermo-Symcon`** vorbereitet. Falls ein anderer Repositoryname verwendet wird, die URL in `library.json`, `WLANThermo/module.json` und den Veröffentlichungsunterlagen entsprechend ändern. Ein Repository wurde hier nicht erstellt und das Modul nicht veröffentlicht.

Die Dateien im vorhandenen Modulordner durch Version 1.2.1 ersetzen und den Symcon-Dienst neu starten. Bei Installation über ein eigenes Git-Repository die Dateien dort übernehmen und über Module Control aktualisieren. **Die bestehende Instanz beibehalten**: Bibliotheks-ID und Modul-ID sind unverändert.

Alarmgrenzen und Pitmaster-Solltemperaturen bleiben ohne Nachkommastellen mit Schrittweite **1 Grad** bedienbar. Vorhandene Float-Variablen und deren IDs bleiben erhalten; gemessene Temperaturen behalten ihre Nachkommastelle. Neue Temperatureinstellungen akzeptieren ganze Grad (`120` und `120.0` sind erlaubt, `120.5` wird abgewiesen). Bereits am Gerät gespeicherte Temperaturen werden durch das Update nicht verändert.

Im Formular können unter **Variablengruppen anlegen** ganze Datenbereiche aktiviert oder deaktiviert werden. Für eine kleinere Variablenliste insbesondere zusätzliche API-Einstellungen, API-Systemwerte und Bluetoothdetails abwählen.

Unter **Einzelne Variablen auswählen** gibt es eine durchsuchbare Liste. Die gewünschte Zeile bearbeiten, den Haken **Anlegen** setzen oder entfernen und anschließend **Übernehmen** drücken. Du kannst mehrere Auswahlen vor dem Speichern bearbeiten. Eine ausgeschaltete Gruppe hat Vorrang vor den einzelnen Haken. Neue Datenfelder erscheinen nach einem Geräteabruf beim erneuten Öffnen des Formulars.

Anders als in Version 1.1.0 ändern die einzelnen Haken die Konfiguration nicht mehr sofort. Das Modul verwendet keine automatischen Eigenschaftsänderungen oder selbst ausgelöste Übernahme. Bestehende Abwahlen aus Version 1.1.0 werden weiter berücksichtigt, ohne die alte Eigenschaft zu überschreiben; eine anschließend von dir gespeicherte Listenauswahl hat für denselben Ident Vorrang. Auch die Auswahl nicht mehr vorhandener Kanäle bleibt erhalten.

**Abwählen und Übernehmen löscht die jeweilige Symcon-Variable.** Verknüpfungen, Skriptreferenzen und Archivzuordnungen können dadurch ungültig werden. Erneutes Anhaken legt sie mit einer neuen ID an. Weiterhin ausgewählte Variablen behalten ihre IDs. Die Auswahl verändert keine Fühlerkopplung und keine Geräteeinstellungen; intern stehen die API-Daten weiter zur Verfügung. Auch Statusvariablen lassen sich abwählen. Fehler bleiben dann über den Instanzstatus und `WLT_GetLastError($id)` abrufbar.

Hinweise und vorbereitete Texte für die Store-Einreichung stehen in [docs/STORE.md](docs/STORE.md).

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

Die API-Antworten werden vollständig ausgelesen, soweit es die drei implementierten Datenendpunkte betrifft. Nicht implementiert sind direkte Bluetooth-Verbindungen vom Symcon-Server, MEATER-Cloud, WLAN-Zugangsdatenänderung, Firmware-Update, Werksreset sowie Verwaltungs-/Diagnoseendpunkte außerhalb dieser Schnittstellen. Version 1.0.0 wurde vom Anwender als funktionierend bestätigt. Version 1.2.1 wurde hier automatisiert geprüft; ein erneuter Test der Änderungen am realen Mini V3 und in Symcon 9.0 steht noch aus. Details zur geprüften Firmwarebasis und zu den Tests stehen in `docs/VALIDIERUNG.md`.

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

Die HTTP-Wiki-Seite ist älter als die Firmware. Bluetooth-Geräteverwaltung und besondere Schreibdetails wurden daher zusätzlich am offiziellen Firmwarecode geprüft. Das Modul wurde eigenständig implementiert; Firmwarecode wird nicht mitgeliefert.
