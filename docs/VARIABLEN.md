# Variablenübersicht – Version 1.3.0

Alle Variablen sind optional. Standardmäßig sind alle Gruppen aktiv; nur die drei Raw-Variablen sind wegen `ExposeRaw=false` deaktiviert. Die Einzelwahl steht initial für alle Idents auf aktiv und wird zusätzlich durch die Gruppenauswahl begrenzt. Eine deaktivierte vorhandene Variable bleibt ausgeblendet und behält ihren letzten Wert; ihre Daten sind dann nicht aktuell.

## Feste und wiederholte Idents

`n` bezeichnet die vom Gerät gemeldete Kanalnummer, `i` die Pitmaster-ID. Das Modul setzt keine feste Anzahl von Kanälen oder Bluetooth-Geräten voraus.

| Ident | Typ | Automatische Darstellung / Einheit | Funktion | Standard |
| --- | --- | --- | --- | --- |
| Online | Boolean | Ja/Nein | Hauptdatenabruf erfolgreich | Aktiv |
| LastUpdate | Integer | Datum/Uhrzeit | Letzter erfolgreicher Abruf | Aktiv |
| LastError | String | Text | Letzter Haupt-/Schreibfehler | Aktiv |
| BluetoothError | String | Text | Optionaler Bluetooth-Fehler | Aktiv |
| Device | String | Text / JSON | Geräteinformationen | Aktiv |
| BluetoothEnabled | Boolean | Schalter | Bluetooth am Mini V3 | Aktiv, wenn Bluetooth verfügbar |
| CHn_Temperature | Float | Wertanzeige, 1 Nachkommastelle, °C/°F | Gemessene Temperatur | Aktiv |
| CHn_Min, CHn_Max | Float | Schieberegler, 1 Grad, °C/°F | Alarmgrenzen | Aktiv |
| CHn_Name | String | Texteingabe | Fühlername am Gerät | Aktiv |
| CHn_Alarm | Integer | Aufzählung: Aus / Push / Summer / Push + Summer | Alarmierungsart | Aktiv |
| CHn_Type | Integer | Werteingabe | Fühlertyp, feste Typen nicht bedienbar | Aktiv |
| CHn_Color | String | Texteingabe #RRGGBB | Farbe am Gerät; String-Typ bleibt erhalten | Aktiv |
| CHn_Fixed | Boolean | Ja/Nein | Fühlertyp fest vorgegeben | Aktiv |
| CHn_Connected | Boolean | Ja/Nein | Funkverbindung laut API | Aktiv |
| CHn_Valid | Boolean | Ja/Nein | Messwert aktuell und verwendbar | Aktiv |
| CHn_Present | Boolean | Ja/Nein | Kanal in letzter Hauptantwort vorhanden | Aktiv |
| CHn_OutOfRange | Boolean | Ja/Nein | Gültige Temperatur außerhalb der Grenzen | Aktiv |
| PMi_Set | Float | Schieberegler, 1 Grad, °C/°F | Solltemperatur, im Automatikmodus bedienbar | Aktiv |
| PMi_Mode | String | Aufzählung Aus / Manuell / Automatisch | Betriebsart | Aktiv |
| PMi_Value | Integer | Schieberegler 0–100 % | Stellwert, im manuellen Modus bedienbar | Aktiv |
| PMi_Channel, PMi_PID | Integer | Werteingabe | Kanal / PID-Profil | Aktiv |
| RawData, RawSettings, RawBluetooth | String | Text / JSON | Optionale Rohdaten, Settings redigiert | Inaktiv |

Zusätzliche API-Blätter werden als `SYS_`, `SET_`, `BLE_`, `EXTRAn_`, `PMEXTRAi_`, `DATA_` und `PIT_` plus SHA-1-Kurzkennung des JSON-Pfads angelegt. Der Ident bleibt bei identischem Pfad stabil, der Name enthält den vollständigen Pfad. Typen folgen den gelieferten JSON-Werten (Boolean, Integer, Float oder String). Ihre automatische Darstellung ist eine Wertanzeige ohne angenommene Einheit; für unbekannte Firmwarefelder wird keine Einheit erfunden. Der konkrete Umfang hängt vom Gerät ab. Fehlende Felder erzeugen keine erfundenen Variablen. Zugangsdaten in Settings werden redigiert. Reine Datenfelder bekommen keine Aktion.

`Automatisch`, `Wertanzeige` und `Bisheriges Profil` sind pro Zeile wählbar. Wertanzeige zeigt Modi und Alarmarten als Rohwert; Automatisch verwendet die beschriftete Auswahl. Die Auswahl einer Darstellung aktiviert keine Schreibberechtigung. Benutzerdefinierte Symcon-Darstellungen und -Aktionen werden nicht gesetzt.

## Vollständiger Katalog der mitgelieferten Testdaten

Diese Liste ist ein Beispiel aus den repräsentativen Fixtures, keine Abfrage des Benutzergeräts. Die tatsächliche Liste wird in der Instanz vollständig aufgebaut.

| Ident | Typ | Funktion / API-Pfad | Standard |
| --- | --- | --- | --- |
| `BLE_46c3f7ffdba48bab` | String | Bluetooth/devices/0/address | Aktiv |
| `BLE_e2d6409c95526697` | Integer | Bluetooth/devices/0/count | Aktiv |
| `BLE_74e45764ce6a8ed5` | String | Bluetooth/devices/0/name | Aktiv |
| `BLE_6bdc96ad56156f42` | Integer | Bluetooth/devices/0/selected | Aktiv |
| `BLE_376efec7839ec34c` | String | Bluetooth/devices/1/address | Aktiv |
| `BLE_2abc9467f1c6aed7` | Integer | Bluetooth/devices/1/count | Aktiv |
| `BLE_3fe949d8a3b47c37` | String | Bluetooth/devices/1/name | Aktiv |
| `BLE_08a5fb6773af46c9` | Integer | Bluetooth/devices/1/selected | Aktiv |
| `BLE_053f4b808bac9063` | String | Bluetooth/devices/2/address | Aktiv |
| `BLE_3fe9347afa8935d4` | Integer | Bluetooth/devices/2/count | Aktiv |
| `BLE_da1330a150274780` | String | Bluetooth/devices/2/name | Aktiv |
| `BLE_47bedac5870391a6` | Integer | Bluetooth/devices/2/selected | Aktiv |
| `BLE_78e37e053cb72f30` | Boolean | Bluetooth/enabled | Aktiv |
| `SET_7f2703a30a690aa4` | String | Einstellungen/aktor/0 | Aktiv |
| `SET_6b039d69edb8beba` | String | Einstellungen/aktor/1 | Aktiv |
| `SET_aa4b97710900a7bf` | String | Einstellungen/device/cpu | Aktiv |
| `SET_2e0f852d4f669ec4` | String | Einstellungen/device/device | Aktiv |
| `SET_fc4e7a0b9cc1333e` | String | Einstellungen/device/serial | Aktiv |
| `SET_0ea9cb3c44a128b8` | String | Einstellungen/device/sw_version | Aktiv |
| `SET_2325a429a4a6e365` | Boolean | Einstellungen/features/bluetooth | Aktiv |
| `SET_394ea2330aeb58a7` | Boolean | Einstellungen/features/pitmaster | Aktiv |
| `SET_133c9b330df21a58` | String | Einstellungen/iot/CLtoken | Aktiv |
| `SET_52640e04195fed3f` | String | Einstellungen/iot/PMQhost | Aktiv |
| `SET_d4d1e73c251f12f2` | String | Einstellungen/iot/PMQpass | Aktiv |
| `SET_a164f673efde7edb` | Integer | Einstellungen/pid/0/aktor | Aktiv |
| `SET_babb0e36aa7f44dc` | Float | Einstellungen/pid/0/DCmmax | Aktiv |
| `SET_7dfd4fb8ebb0411b` | Float | Einstellungen/pid/0/DCmmin | Aktiv |
| `SET_4be2e7bd24ee22a4` | Integer | Einstellungen/pid/0/id | Aktiv |
| `SET_bd69a4a167f99c65` | Integer | Einstellungen/pid/0/jp | Aktiv |
| `SET_2452f85ac1f0c38e` | Float | Einstellungen/pid/0/Kd | Aktiv |
| `SET_49231909d2eb701b` | Float | Einstellungen/pid/0/Ki | Aktiv |
| `SET_d5e27847bc837076` | Float | Einstellungen/pid/0/Kp | Aktiv |
| `SET_da5532469922eaa9` | Integer | Einstellungen/pid/0/link | Aktiv |
| `SET_922ac14cc817ca8b` | String | Einstellungen/pid/0/name | Aktiv |
| `SET_1a0d1ec962d101b8` | Boolean | Einstellungen/pid/0/opl | Aktiv |
| `SET_83f4d3f616b27fa5` | Float | Einstellungen/pid/0/SPmax | Aktiv |
| `SET_444c27a30a554fc8` | Float | Einstellungen/pid/0/SPmin | Aktiv |
| `SET_f99ec92ffff7722e` | Boolean | Einstellungen/pid/0/tune | Aktiv |
| `SET_d05569bcb3b4f4fd` | Boolean | Einstellungen/sensors/0/fixed | Aktiv |
| `SET_25485ca8d0d69da0` | String | Einstellungen/sensors/0/name | Aktiv |
| `SET_4c17f5b686f83330` | Integer | Einstellungen/sensors/0/type | Aktiv |
| `SET_760c849994c6e977` | Boolean | Einstellungen/sensors/1/fixed | Aktiv |
| `SET_913bb11361e60848` | String | Einstellungen/sensors/1/name | Aktiv |
| `SET_97eab4cd087d7f02` | Integer | Einstellungen/sensors/1/type | Aktiv |
| `SET_2736741dd2f7ded5` | String | Einstellungen/system/ap | Aktiv |
| `SET_adf863ee238ffdbc` | Boolean | Einstellungen/system/autoupd | Aktiv |
| `SET_c9f6509b23694549` | String | Einstellungen/system/host | Aktiv |
| `SET_8946651a7251a5e4` | String | Einstellungen/system/language | Aktiv |
| `SET_861b5bb1c7936ca0` | Boolean | Einstellungen/system/prerelease | Aktiv |
| `SET_f6b10851b1ecab2f` | String | Einstellungen/system/unit | Aktiv |
| `SET_f759f451fd949aec` | String | Einstellungen/system/version | Aktiv |
| `SYS_b8aa912b038a8849` | Boolean | System/charge | Aktiv |
| `SYS_acca76032d341144` | Integer | System/online | Aktiv |
| `SYS_9c6abf33849c531c` | Integer | System/rssi | Aktiv |
| `SYS_56c611d072cc405b` | Integer | System/soc | Aktiv |
| `SYS_c5c7ed87a5a2a364` | String | System/time | Aktiv |
| `SYS_955d545b903796a6` | String | System/unit | Aktiv |
| `CH1_Alarm` | Integer | Kanal 1 · Garraum: Alarmierung | Aktiv |
| `CH1_Max` | Float | Kanal 1 · Garraum: Alarm Maximum | Aktiv |
| `CH1_Min` | Float | Kanal 1 · Garraum: Alarm Minimum | Aktiv |
| `CH1_Color` | String | Kanal 1 · Garraum: Farbe | Aktiv |
| `CH1_Connected` | Boolean | Kanal 1 · Garraum: Funkverbindung (API) | Aktiv |
| `CH1_Type` | Integer | Kanal 1 · Garraum: Fühlertyp | Aktiv |
| `CH1_Fixed` | Boolean | Kanal 1 · Garraum: Fühlertyp fest | Aktiv |
| `CH1_Present` | Boolean | Kanal 1 · Garraum: Kanal vorhanden | Aktiv |
| `CH1_Valid` | Boolean | Kanal 1 · Garraum: Messwert gültig | Aktiv |
| `CH1_Name` | String | Kanal 1 · Garraum: Name | Aktiv |
| `CH1_Temperature` | Float | Kanal 1 · Garraum: Temperatur | Aktiv |
| `CH1_OutOfRange` | Boolean | Kanal 1 · Garraum: Temperatur außerhalb Grenzen | Aktiv |
| `CH13_Alarm` | Integer | Kanal 13 · MeaterKT: Alarmierung | Aktiv |
| `CH13_Max` | Float | Kanal 13 · MeaterKT: Alarm Maximum | Aktiv |
| `CH13_Min` | Float | Kanal 13 · MeaterKT: Alarm Minimum | Aktiv |
| `CH13_Color` | String | Kanal 13 · MeaterKT: Farbe | Aktiv |
| `CH13_Connected` | Boolean | Kanal 13 · MeaterKT: Funkverbindung (API) | Aktiv |
| `CH13_Type` | Integer | Kanal 13 · MeaterKT: Fühlertyp | Aktiv |
| `CH13_Fixed` | Boolean | Kanal 13 · MeaterKT: Fühlertyp fest | Aktiv |
| `CH13_Present` | Boolean | Kanal 13 · MeaterKT: Kanal vorhanden | Aktiv |
| `CH13_Valid` | Boolean | Kanal 13 · MeaterKT: Messwert gültig | Aktiv |
| `CH13_Name` | String | Kanal 13 · MeaterKT: Name | Aktiv |
| `CH13_Temperature` | Float | Kanal 13 · MeaterKT: Temperatur | Aktiv |
| `CH13_OutOfRange` | Boolean | Kanal 13 · MeaterKT: Temperatur außerhalb Grenzen | Aktiv |
| `CH14_Alarm` | Integer | Kanal 14 · MeaterGT: Alarmierung | Aktiv |
| `CH14_Max` | Float | Kanal 14 · MeaterGT: Alarm Maximum | Aktiv |
| `CH14_Min` | Float | Kanal 14 · MeaterGT: Alarm Minimum | Aktiv |
| `CH14_Color` | String | Kanal 14 · MeaterGT: Farbe | Aktiv |
| `CH14_Connected` | Boolean | Kanal 14 · MeaterGT: Funkverbindung (API) | Aktiv |
| `CH14_Type` | Integer | Kanal 14 · MeaterGT: Fühlertyp | Aktiv |
| `CH14_Fixed` | Boolean | Kanal 14 · MeaterGT: Fühlertyp fest | Aktiv |
| `CH14_Present` | Boolean | Kanal 14 · MeaterGT: Kanal vorhanden | Aktiv |
| `CH14_Valid` | Boolean | Kanal 14 · MeaterGT: Messwert gültig | Aktiv |
| `CH14_Name` | String | Kanal 14 · MeaterGT: Name | Aktiv |
| `CH14_Temperature` | Float | Kanal 14 · MeaterGT: Temperatur | Aktiv |
| `CH14_OutOfRange` | Boolean | Kanal 14 · MeaterGT: Temperatur außerhalb Grenzen | Aktiv |
| `PMEXTRA0_5e3c06e03c86d21a` | Integer | Pitmaster 0/id | Aktiv |
| `PM0_Channel` | Integer | Pitmaster 0: channel | Aktiv |
| `PM0_PID` | Integer | Pitmaster 0: pid | Aktiv |
| `PM0_Set` | Float | Pitmaster 0: set | Aktiv |
| `PM0_Mode` | String | Pitmaster 0: typ | Aktiv |
| `PM0_Value` | Integer | Pitmaster 0: value | Aktiv |
| `PMEXTRA1_5e3c06e03c86d21a` | Integer | Pitmaster 1/id | Aktiv |
| `PM1_Channel` | Integer | Pitmaster 1: channel | Aktiv |
| `PM1_PID` | Integer | Pitmaster 1: pid | Aktiv |
| `PM1_Set` | Float | Pitmaster 1: set | Aktiv |
| `PM1_Mode` | String | Pitmaster 1: typ | Aktiv |
| `PM1_Value` | Integer | Pitmaster 1: value | Aktiv |
| `BluetoothError` | String | Bluetooth-Hinweis | Aktiv |
| `BluetoothEnabled` | Boolean | Bluetooth aktiviert | Aktiv |
| `Device` | String | Geräteinformationen (JSON) | Aktiv |
| `Online` | Boolean | Gerät erreichbar | Aktiv |
| `EXTRA1_71474a1cc2805328` | Integer | Kanal 1 · Garraum: API/number | Aktiv |
| `EXTRA13_71474a1cc2805328` | Integer | Kanal 13 · MeaterKT: API/number | Aktiv |
| `EXTRA14_71474a1cc2805328` | Integer | Kanal 14 · MeaterGT: API/number | Aktiv |
| `LastUpdate` | Integer | Letzter erfolgreicher Datenabruf | Aktiv |
| `LastError` | String | Letzter Fehler | Aktiv |
| `PIT_293ec82575560cf4` | String | Pitmaster API/type/0 | Aktiv |
| `PIT_db48c4983b69b68c` | String | Pitmaster API/type/1 | Aktiv |
| `PIT_3b6b466d219a6474` | String | Pitmaster API/type/2 | Aktiv |
| `RawBluetooth` | String | RawBluetooth | Inaktiv |
| `RawData` | String | RawData | Inaktiv |
| `RawSettings` | String | RawSettings | Inaktiv |
