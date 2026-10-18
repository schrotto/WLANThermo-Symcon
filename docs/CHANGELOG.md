# Änderungen

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
