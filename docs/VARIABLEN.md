# Variablenübersicht – Version 1.4.0

Alle Variablen sind optional und auf neuen Instanzen standardmäßig **aus**. Die Kachel benötigt keine einzige Variable. Bei Updates bleibt die bisherige Auswahl erhalten. Gruppen allein legen keine Variablen an, sondern begrenzen die durch Standard und Einzelentscheidungen aktivierten Felder. Deaktivierte vorhandene Variablen bleiben mit ihrem letzten Wert erhalten, bis sie ausdrücklich bereinigt werden. Als Datenziel kann pro Feld eine vorhandene beschreibbare Variable passenden Typs verwendet werden.

Die Tabellen beschreiben den Standard einer neuen Instanz ohne gespeicherte Einzelentscheidungen. Aktivieren erfolgt per Zeile, Standardmodus oder vorbereiteter Sammelauswahl. Die empfohlene Auswahl umfasst Online und je Kanal Temperatur/Gültigkeit.

## Feste und wiederholte Idents

`n` bezeichnet die vom Gerät gemeldete Kanalnummer, `i` die Pitmaster-ID. Das Modul setzt keine feste Anzahl von Kanälen oder Bluetooth-Geräten voraus.

| Ident | Typ | Automatische Darstellung / Einheit | Funktion | Standard |
| --- | --- | --- | --- | --- |
| Online | Boolean | Ja/Nein | Hauptdatenabruf erfolgreich | Aus |
| LastUpdate | Integer | Datum/Uhrzeit | Letzter erfolgreicher Abruf | Aus |
| LastError | String | Text | Letzter Haupt-/Schreibfehler | Aus |
| BluetoothError | String | Text | Optionaler Bluetooth-Fehler | Aus |
| Device | String | Text / JSON | Geräteinformationen | Aus |
| BluetoothEnabled | Boolean | Schalter | Bluetooth am Mini V3 | Aus |
| CHn_Temperature | Float | Wertanzeige, 1 Nachkommastelle, °C/°F | Gemessene Temperatur | Aus |
| CHn_Min, CHn_Max | Float | Schieberegler, 1 Grad, °C/°F | Alarmgrenzen | Aus |
| CHn_Name | String | Texteingabe | Fühlername am Gerät | Aus |
| CHn_Alarm | Integer | Aufzählung: Aus / Push / Summer / Push + Summer | Alarmierungsart | Aus |
| CHn_Type | Integer | Werteingabe | Fühlertyp, feste Typen nicht bedienbar | Aus |
| CHn_Color | String | Texteingabe #RRGGBB | Farbe am Gerät; String-Typ bleibt erhalten | Aus |
| CHn_Fixed | Boolean | Ja/Nein | Fühlertyp fest vorgegeben | Aus |
| CHn_Connected | Boolean | Ja/Nein | Funkverbindung laut API | Aus |
| CHn_Valid | Boolean | Ja/Nein | Messwert aktuell und verwendbar | Aus |
| CHn_Present | Boolean | Ja/Nein | Kanal in letzter Hauptantwort vorhanden | Aus |
| CHn_OutOfRange | Boolean | Ja/Nein | Gültige Temperatur außerhalb der Grenzen | Aus |
| PMi_Set | Float | Schieberegler, 1 Grad, °C/°F | Solltemperatur, im Automatikmodus bedienbar | Aus |
| PMi_Mode | String | Aufzählung Aus / Manuell / Automatisch | Betriebsart | Aus |
| PMi_Value | Integer | Schieberegler 0–100 % | Stellwert, im manuellen Modus bedienbar | Aus |
| PMi_Channel, PMi_PID | Integer | Werteingabe | Kanal / PID-Profil | Aus |
| RawData, RawSettings, RawBluetooth | String | Text / JSON | Optionale Rohdaten, Settings redigiert | Aus |

Zusätzliche API-Blätter werden als `SYS_`, `SET_`, `BLE_`, `EXTRAn_`, `PMEXTRAi_`, `DATA_` und `PIT_` plus SHA-1-Kurzkennung des JSON-Pfads angelegt. Der Ident bleibt bei identischem Pfad stabil, der Name enthält den vollständigen Pfad. Typen folgen den gelieferten JSON-Werten (Boolean, Integer, Float oder String). Ihre automatische Darstellung ist eine Wertanzeige ohne angenommene Einheit; für unbekannte Firmwarefelder wird keine Einheit erfunden. Der konkrete Umfang hängt vom Gerät ab. Fehlende Felder erzeugen keine erfundenen Variablen. Zugangsdaten in Settings werden redigiert. Reine Datenfelder bekommen keine Aktion.

`Automatisch`, `Wertanzeige` und `Bisheriges Profil` sind pro Zeile wählbar. Wertanzeige zeigt Modi und Alarmarten als Rohwert; Automatisch verwendet die beschriftete Auswahl. Die Auswahl einer Darstellung aktiviert keine Schreibberechtigung. Benutzerdefinierte Symcon-Darstellungen und -Aktionen werden nicht gesetzt.

## Vollständiger Katalog der mitgelieferten Testdaten

Diese Liste ist ein Beispiel aus den repräsentativen Fixtures, keine Abfrage des Benutzergeräts. Die tatsächliche Liste wird in der Instanz vollständig aufgebaut.

| Ident | Typ | Funktion / API-Pfad | Standard |
| --- | --- | --- | --- |
| `BLE_46c3f7ffdba48bab` | String | Bluetooth/devices/0/address | Aus |
| `BLE_e2d6409c95526697` | Integer | Bluetooth/devices/0/count | Aus |
| `BLE_74e45764ce6a8ed5` | String | Bluetooth/devices/0/name | Aus |
| `BLE_6bdc96ad56156f42` | Integer | Bluetooth/devices/0/selected | Aus |
| `BLE_376efec7839ec34c` | String | Bluetooth/devices/1/address | Aus |
| `BLE_2abc9467f1c6aed7` | Integer | Bluetooth/devices/1/count | Aus |
| `BLE_3fe949d8a3b47c37` | String | Bluetooth/devices/1/name | Aus |
| `BLE_08a5fb6773af46c9` | Integer | Bluetooth/devices/1/selected | Aus |
| `BLE_053f4b808bac9063` | String | Bluetooth/devices/2/address | Aus |
| `BLE_3fe9347afa8935d4` | Integer | Bluetooth/devices/2/count | Aus |
| `BLE_da1330a150274780` | String | Bluetooth/devices/2/name | Aus |
| `BLE_47bedac5870391a6` | Integer | Bluetooth/devices/2/selected | Aus |
| `BLE_78e37e053cb72f30` | Boolean | Bluetooth/enabled | Aus |
| `SET_7f2703a30a690aa4` | String | Einstellungen/aktor/0 | Aus |
| `SET_6b039d69edb8beba` | String | Einstellungen/aktor/1 | Aus |
| `SET_aa4b97710900a7bf` | String | Einstellungen/device/cpu | Aus |
| `SET_2e0f852d4f669ec4` | String | Einstellungen/device/device | Aus |
| `SET_fc4e7a0b9cc1333e` | String | Einstellungen/device/serial | Aus |
| `SET_0ea9cb3c44a128b8` | String | Einstellungen/device/sw_version | Aus |
| `SET_2325a429a4a6e365` | Boolean | Einstellungen/features/bluetooth | Aus |
| `SET_394ea2330aeb58a7` | Boolean | Einstellungen/features/pitmaster | Aus |
| `SET_133c9b330df21a58` | String | Einstellungen/iot/CLtoken | Aus |
| `SET_52640e04195fed3f` | String | Einstellungen/iot/PMQhost | Aus |
| `SET_d4d1e73c251f12f2` | String | Einstellungen/iot/PMQpass | Aus |
| `SET_a164f673efde7edb` | Integer | Einstellungen/pid/0/aktor | Aus |
| `SET_babb0e36aa7f44dc` | Float | Einstellungen/pid/0/DCmmax | Aus |
| `SET_7dfd4fb8ebb0411b` | Float | Einstellungen/pid/0/DCmmin | Aus |
| `SET_4be2e7bd24ee22a4` | Integer | Einstellungen/pid/0/id | Aus |
| `SET_bd69a4a167f99c65` | Integer | Einstellungen/pid/0/jp | Aus |
| `SET_2452f85ac1f0c38e` | Float | Einstellungen/pid/0/Kd | Aus |
| `SET_49231909d2eb701b` | Float | Einstellungen/pid/0/Ki | Aus |
| `SET_d5e27847bc837076` | Float | Einstellungen/pid/0/Kp | Aus |
| `SET_da5532469922eaa9` | Integer | Einstellungen/pid/0/link | Aus |
| `SET_922ac14cc817ca8b` | String | Einstellungen/pid/0/name | Aus |
| `SET_1a0d1ec962d101b8` | Boolean | Einstellungen/pid/0/opl | Aus |
| `SET_83f4d3f616b27fa5` | Float | Einstellungen/pid/0/SPmax | Aus |
| `SET_444c27a30a554fc8` | Float | Einstellungen/pid/0/SPmin | Aus |
| `SET_f99ec92ffff7722e` | Boolean | Einstellungen/pid/0/tune | Aus |
| `SET_d05569bcb3b4f4fd` | Boolean | Einstellungen/sensors/0/fixed | Aus |
| `SET_25485ca8d0d69da0` | String | Einstellungen/sensors/0/name | Aus |
| `SET_4c17f5b686f83330` | Integer | Einstellungen/sensors/0/type | Aus |
| `SET_760c849994c6e977` | Boolean | Einstellungen/sensors/1/fixed | Aus |
| `SET_913bb11361e60848` | String | Einstellungen/sensors/1/name | Aus |
| `SET_97eab4cd087d7f02` | Integer | Einstellungen/sensors/1/type | Aus |
| `SET_2736741dd2f7ded5` | String | Einstellungen/system/ap | Aus |
| `SET_adf863ee238ffdbc` | Boolean | Einstellungen/system/autoupd | Aus |
| `SET_c9f6509b23694549` | String | Einstellungen/system/host | Aus |
| `SET_8946651a7251a5e4` | String | Einstellungen/system/language | Aus |
| `SET_861b5bb1c7936ca0` | Boolean | Einstellungen/system/prerelease | Aus |
| `SET_f6b10851b1ecab2f` | String | Einstellungen/system/unit | Aus |
| `SET_f759f451fd949aec` | String | Einstellungen/system/version | Aus |
| `SYS_b8aa912b038a8849` | Boolean | System/charge | Aus |
| `SYS_acca76032d341144` | Integer | System/online | Aus |
| `SYS_9c6abf33849c531c` | Integer | System/rssi | Aus |
| `SYS_56c611d072cc405b` | Integer | System/soc | Aus |
| `SYS_c5c7ed87a5a2a364` | String | System/time | Aus |
| `SYS_955d545b903796a6` | String | System/unit | Aus |
| `CH1_Alarm` | Integer | Kanal 1 · Garraum: Alarmierung | Aus |
| `CH1_Max` | Float | Kanal 1 · Garraum: Alarm Maximum | Aus |
| `CH1_Min` | Float | Kanal 1 · Garraum: Alarm Minimum | Aus |
| `CH1_Color` | String | Kanal 1 · Garraum: Farbe | Aus |
| `CH1_Connected` | Boolean | Kanal 1 · Garraum: Funkverbindung (API) | Aus |
| `CH1_Type` | Integer | Kanal 1 · Garraum: Fühlertyp | Aus |
| `CH1_Fixed` | Boolean | Kanal 1 · Garraum: Fühlertyp fest | Aus |
| `CH1_Present` | Boolean | Kanal 1 · Garraum: Kanal vorhanden | Aus |
| `CH1_Valid` | Boolean | Kanal 1 · Garraum: Messwert gültig | Aus |
| `CH1_Name` | String | Kanal 1 · Garraum: Name | Aus |
| `CH1_Temperature` | Float | Kanal 1 · Garraum: Temperatur | Aus |
| `CH1_OutOfRange` | Boolean | Kanal 1 · Garraum: Temperatur außerhalb Grenzen | Aus |
| `CH13_Alarm` | Integer | Kanal 13 · MeaterKT: Alarmierung | Aus |
| `CH13_Max` | Float | Kanal 13 · MeaterKT: Alarm Maximum | Aus |
| `CH13_Min` | Float | Kanal 13 · MeaterKT: Alarm Minimum | Aus |
| `CH13_Color` | String | Kanal 13 · MeaterKT: Farbe | Aus |
| `CH13_Connected` | Boolean | Kanal 13 · MeaterKT: Funkverbindung (API) | Aus |
| `CH13_Type` | Integer | Kanal 13 · MeaterKT: Fühlertyp | Aus |
| `CH13_Fixed` | Boolean | Kanal 13 · MeaterKT: Fühlertyp fest | Aus |
| `CH13_Present` | Boolean | Kanal 13 · MeaterKT: Kanal vorhanden | Aus |
| `CH13_Valid` | Boolean | Kanal 13 · MeaterKT: Messwert gültig | Aus |
| `CH13_Name` | String | Kanal 13 · MeaterKT: Name | Aus |
| `CH13_Temperature` | Float | Kanal 13 · MeaterKT: Temperatur | Aus |
| `CH13_OutOfRange` | Boolean | Kanal 13 · MeaterKT: Temperatur außerhalb Grenzen | Aus |
| `CH14_Alarm` | Integer | Kanal 14 · MeaterGT: Alarmierung | Aus |
| `CH14_Max` | Float | Kanal 14 · MeaterGT: Alarm Maximum | Aus |
| `CH14_Min` | Float | Kanal 14 · MeaterGT: Alarm Minimum | Aus |
| `CH14_Color` | String | Kanal 14 · MeaterGT: Farbe | Aus |
| `CH14_Connected` | Boolean | Kanal 14 · MeaterGT: Funkverbindung (API) | Aus |
| `CH14_Type` | Integer | Kanal 14 · MeaterGT: Fühlertyp | Aus |
| `CH14_Fixed` | Boolean | Kanal 14 · MeaterGT: Fühlertyp fest | Aus |
| `CH14_Present` | Boolean | Kanal 14 · MeaterGT: Kanal vorhanden | Aus |
| `CH14_Valid` | Boolean | Kanal 14 · MeaterGT: Messwert gültig | Aus |
| `CH14_Name` | String | Kanal 14 · MeaterGT: Name | Aus |
| `CH14_Temperature` | Float | Kanal 14 · MeaterGT: Temperatur | Aus |
| `CH14_OutOfRange` | Boolean | Kanal 14 · MeaterGT: Temperatur außerhalb Grenzen | Aus |
| `PMEXTRA0_5e3c06e03c86d21a` | Integer | Pitmaster 0/id | Aus |
| `PM0_Channel` | Integer | Pitmaster 0: channel | Aus |
| `PM0_PID` | Integer | Pitmaster 0: pid | Aus |
| `PM0_Set` | Float | Pitmaster 0: set | Aus |
| `PM0_Mode` | String | Pitmaster 0: typ | Aus |
| `PM0_Value` | Integer | Pitmaster 0: value | Aus |
| `PMEXTRA1_5e3c06e03c86d21a` | Integer | Pitmaster 1/id | Aus |
| `PM1_Channel` | Integer | Pitmaster 1: channel | Aus |
| `PM1_PID` | Integer | Pitmaster 1: pid | Aus |
| `PM1_Set` | Float | Pitmaster 1: set | Aus |
| `PM1_Mode` | String | Pitmaster 1: typ | Aus |
| `PM1_Value` | Integer | Pitmaster 1: value | Aus |
| `BluetoothError` | String | Bluetooth-Hinweis | Aus |
| `BluetoothEnabled` | Boolean | Bluetooth aktiviert | Aus |
| `Device` | String | Geräteinformationen (JSON) | Aus |
| `Online` | Boolean | Gerät erreichbar | Aus |
| `EXTRA1_71474a1cc2805328` | Integer | Kanal 1 · Garraum: API/number | Aus |
| `EXTRA13_71474a1cc2805328` | Integer | Kanal 13 · MeaterKT: API/number | Aus |
| `EXTRA14_71474a1cc2805328` | Integer | Kanal 14 · MeaterGT: API/number | Aus |
| `LastUpdate` | Integer | Letzter erfolgreicher Datenabruf | Aus |
| `LastError` | String | Letzter Fehler | Aus |
| `PIT_293ec82575560cf4` | String | Pitmaster API/type/0 | Aus |
| `PIT_db48c4983b69b68c` | String | Pitmaster API/type/1 | Aus |
| `PIT_3b6b466d219a6474` | String | Pitmaster API/type/2 | Aus |
| `RawBluetooth` | String | RawBluetooth | Aus |
| `RawData` | String | RawData | Aus |
| `RawSettings` | String | RawSettings | Aus |
