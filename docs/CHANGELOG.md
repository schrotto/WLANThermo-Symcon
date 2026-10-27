# Änderungen

## 1.3.0 – 27.10.2026

- Variablen bei Abwahl erhalten statt löschen; IDs, Werte und Archivzuordnungen bleiben bestehen.
- Eigene Namen, Positionen und auswählbare aktuelle Darstellungen in der Variablentabelle.
- Übersichtliches deutsches Formular mit fünf Bereichen und Verbindungstest.
- Bisherige Einzel- und Gruppenauswahl sowie GUIDs/Idents bleiben erhalten.
- Wiederherstellung der vorherigen Sichtbarkeit und Offline-Anpassung der Variablenmetadaten.
- Ungültige Konfigurationen stoppen den Timer und deaktivieren Bedienaktionen; Formular bleibt erreichbar.
- 124 Modulprüfungen und 8 echte HTTP-Transportprüfungen erfolgreich, dazu PHP-Syntax und JSON-/Strukturprüfung.
- Reale Hardware und Symcon-Konsole müssen noch geprüft werden.

## 1.2.1 – 25.10.2026

- Ausgeschaltetes oder vorübergehend nicht erreichbares WLANthermo wird als normaler Standby (106) statt fehlerhafte Instanz (202) behandelt.
- Automatischer Abruf bleibt aktiv; nach dem Einschalten kehrt die Instanz selbstständig in den Aktivstatus (102) zurück.
- Alte Messwerte bleiben erhalten, sind während des Ausfalls aber ungültig; Bedienaktionen werden deaktiviert.
- Anmelde-, Zertifikats-, Konfigurations- und API-Fehler bleiben echte Fehler.
- 109 Modul-/API-Assertions bestanden; zusätzlich eine reale cURL-Verbindungsablehnung korrekt als Nichterreichbarkeit eingeordnet.

## 1.2.0 – 18.10.2026

- Autor: schrotto; Repository- und Dokumentationsadressen für `schrotto/WLANThermo-Symcon` vorbereitet.
- Variablenauswahl über eine normale Konfigurationsliste mit Checkboxen. Alle Änderungen werden erst über die Schaltfläche Übernehmen bestätigt.
- Laufzeitcode enthält keine Aufrufe von `IPS_SetProperty` oder `IPS_ApplyChanges`. Die bisherige Funktion `WLT_SetVariableEnabled` entfällt.
- Gespeicherte Einzel- und Gruppenauswahlen bleiben bei Migration von 1.1.0 erhalten. Neue und zeitweise fehlende Datenfelder setzen bestehende Entscheidungen nicht zurück.
- Vorbereitete deutsche Store-Texte und Hinweise für Reviewer ergänzt.
- Tests um verzögerte Übernahme, unveränderte Eigenschaften, Migration, unbekannte Identifikatoren und Store-bezogene Mutationserkennung erweitert.

## 1.1.0 – 18.10.2026

- Alarmgrenzen und Pitmaster-Sollwerte: keine Nachkommastellen, Schrittweite 1 Grad. Bestehende Variablentypen und IDs bleiben erhalten. Gemessene Temperaturen bleiben präzise.
- Neue Gradwerte werden als ganze Zahlen geprüft; Dezimalwerte mit Nachkommastellen werden nicht an das Gerät gesendet.
- Kontrollkästchen für Variablengruppen und jede einzelne bekannte Variable. Die Auswahl wird in der Instanzkonfiguration gespeichert und bleibt nach Neustart erhalten.
- Abwählen entfernt vorhandene Variablen und verhindert ihre automatische Wiederanlage. Erneutes Aktivieren erzeugt neue IDs. Auswahl funktioniert auch bei ausgeschaltetem WLANthermo.
- Status und Fehler werden unabhängig von optionalen Variablen intern geführt. Neue Funktion `WLT_GetLastError` für Konsole und Skripte.
- 72 Modul-/API-Prüfungen für 1.1.0 bestanden; 8 HTTP-Prüfungen des unveränderten Transports aus dem vorherigen Lauf.

## 1.0.0

Erste Version für Symcon 9.0, Mini V3 und Bluetooth-/MEATER-Sensoren.
