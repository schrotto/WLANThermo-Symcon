# Veröffentlichung für schrotto

Vorbereitet für Version **1.3.0**. Es wurde kein GitHub-Repository erstellt und keine Store-Einreichung durchgeführt. Die endgültige Freigabe erfolgt durch Symcon; eine Annahme des Moduls wird nicht zugesichert.

## GitHub

Vorgesehener Repositoryname: **WLANThermo-Symcon** unter **schrotto**.

1. Auf GitHub unter deinem Account ein öffentliches Repository mit diesem Namen anlegen.
2. Den Inhalt des äußeren ZIP-Ordners hochladen. `library.json` muss direkt im Repository-Hauptverzeichnis liegen. Die Unterordner `WLANThermo`, `libs`, `docs` und `tests` beibehalten; auch `.github/workflows/ci.yml` für die automatischen Prüfungen mit hochladen.
3. Die vorbereiteten URLs lauten `https://github.com/schrotto/WLANThermo-Symcon` und `https://github.com/schrotto/WLANThermo-Symcon#readme`. Bei abweichendem Repositorynamen vor dem Upload in den Metadaten und den Store-Angaben anpassen.
4. Den GitHub-Actions-Testlauf prüfen und die Änderungen am eigenen Mini V3 in Symcon testen. Danach einen Release-Tag wie `v1.3.0` setzen und den Commit für die Einreichung auswählen.

Die MIT-Lizenz liegt bei. Herausgeber/Autor in `library.json` ist `schrotto`. Die bestehenden Modul-/Bibliotheks-GUIDs bleiben unverändert.

## Angaben im Symcon-Entwicklerbereich

Auf [account.symcon.de](https://account.symcon.de/) unter Entwicklerbereich ein neues Modul hinzufügen. Eine mögliche Bundle-ID ist **`io.github.schrotto.wlanthermo`**; deren Verfügbarkeit im Account prüfen. Für die erste Veröffentlichung zunächst den Kanal **Testing oder Beta** wählen. Mindestens eine passende, im Portal angebotene Kategorie auswählen.

Git-URL nach Repositoryerstellung: `https://github.com/schrotto/WLANThermo-Symcon.git`.

Anschließend den hochgeladenen Commit auswählen und die folgenden Texte als **deutsche Lokalisierung** verwenden. Die Bedienoberfläche und Dokumentation sind deutsch; keine zusätzliche englische Store-Lokalisierung anbieten, solange diese nicht vollständig im Modul unterstützt ist.

**Name**

WLANThermo

**Beschreibung**

Bindet einen WLANThermo Mini V3 lokal über HTTP in Symcon ein. Erfasst die vom Gerät bereitgestellten Temperaturkanäle einschließlich unterstützter Bluetooth- und MEATER-Fühler sowie System-, Geräte- und Pitmaster-Daten. Kanalnamen, Alarmgrenzen, Fühlertypen, Pitmaster- und PID-Einstellungen lassen sich ändern. Temperaturvorgaben sind in ganzen Grad bedienbar. Gruppen und einzelne Variablen können über Kontrollkästchen ausgewählt werden. Erfordert Symcon 9.0 und Netzwerkzugriff auf das Gerät. Die unterstützten MEATER-Modelle hängen von der WLANthermo- und Bluetooth-Firmware ab.

**Versionsinformation**

Version 1.3.0: Frei konfigurierbare Variablentabelle mit Namen, Positionen und Darstellungen. Deaktivierte Variablen bleiben mit ihren IDs und Daten erhalten. Neu gegliedertes Formular mit Verbindungstest. Ausgeschaltetes oder nicht erreichbares WLANthermo erzeugt Standby statt eines Instanzfehlers. Automatische Wiederverbindung nach dem Einschalten. Variablenauswahl auf eine Konfigurationsliste mit Checkboxen und ausdrücklicher Übernahme umgestellt. Automatische Änderungen der Instanzeigenschaften entfernt. Bisherige Auswahlen werden erhalten. Temperaturvorgaben bleiben in Schritten von einem Grad bedienbar. Autor- und Repositoryangaben für schrotto ergänzt.

**Dokumentationslink**

`https://github.com/schrotto/WLANThermo-Symcon#readme`

Dieser Link funktioniert erst nach Erstellung und Upload des vorgesehenen Repositorys.

## Technische Hinweise für das Review

- Die Moduleigenschaften werden ausschließlich durch den Benutzer über die normale Übernahme der Konfiguration gespeichert. Im Laufzeitcode gibt es keine Aufrufe von `IPS_SetProperty` oder `IPS_ApplyChanges`. Die dynamische Liste übernimmt gespeicherte Entscheidungen nach Ident; neu erkannte Zeilen werden ergänzt. Nicht mehr verfügbare Identifikatoren bleiben erhalten. Der historische Parameter `DisabledVariables` wird nur als Migrationsvorgabe gelesen.
- Checkboxen der Variablenliste ändern nur die vorgesehenen Symcon-Variablen nach Übernahme. Gruppen sind ebenfalls gewöhnliche Instanzeigenschaften. Ein deutlicher Hinweis erklärt das Ausblenden und die unveränderte ID beim Wiederaktivieren. Vorhandene Daten werden nicht gelöscht.
- Es wird ausschließlich die eigene Datei `WLANThermo/form.json` gelesen und die eigene Hilfsdatei `libs/Api.php` eingebunden. Es erfolgen keine frei konfigurierbaren Dateizugriffe oder Schreibzugriffe auf das Dateisystem.
- Netzwerkzugriffe erfolgen ausschließlich auf die vom Benutzer konfigurierte WLANthermo-Adresse. Die Verbindung ist lokales HTTP beziehungsweise optional HTTPS mit Zertifikatsprüfung und Basic-/Digest-Authentifizierung. Keine Cloud-Anmeldung ist für den Modulbetrieb erforderlich.
- Modulvariablen entstehen unterhalb der eigenen Instanz. Es gibt keine zusätzlichen Skripte oder Ereignisse; regelmäßiger Abruf erfolgt über RegisterTimer. Die Instanz ist auch ohne konfigurierte Geräteadresse erstellbar und zeigt dann einen Konfigurationsstatus.
- Es werden aktuelle Standarddarstellungen und Standardaktionen verwendet. Benutzerdefinierte Darstellungen, Aktionen und Archivkonfiguration bleiben unangetastet. Eigene Namen und Positionen werden ausschließlich nach einer gespeicherten Tabellenänderung angewendet. Deaktivieren blendet die eigene Variable aus; beim Wiederaktivieren wird deren vorherige Sichtbarkeit wiederhergestellt. Diese Wirkung wird vor dem Speichern im Formular erklärt.
- Eigene Profile haben den Präfix `WLT.`. Ein Sprung auf ganzzahlige Temperaturvorgaben erfolgt über eigene Standardprofile bei gleichem Variablentyp. Gemessene Temperaturen behalten ihre Nachkommastelle.
- Fehler, Testumfang und technische Grenzen sind in `VALIDIERUNG.md` dokumentiert. Version 1.3.0 wurde hier ohne reale Symcon-Instanz und ohne Gerät geprüft. Ein zusätzlicher Test am Zielsystem vor Veröffentlichung ist erforderlich.

## Offizielle Grundlagen

- [Store-Einreichung und Reviewvorgaben](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/)
- [Konfigurationslisten: Laden und Speichern](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/konfigurationsformulare/list/)
- [Aktuelle Symcon-Downloads](https://www.symcon.de/de/downloads/)
