# WLANThermo für IP-Symcon

PHP-Modul für **IP-Symcon 9.0**, **WLANThermo Mini V3** und daran angebundene **MEATER-/Bluetooth-Fühler**. Kommunikation lokal über HTTP; ein MQTT-Broker und ein MEATER-Cloud-Konto sind nicht erforderlich. Unabhängiges Community-Modul, kein offizielles Produkt von Symcon, WLANThermo oder MEATER.

## Version 1.11.4: Anfangsbreite der Fühlerauswahl stabilisieren

Die Rückmeldung betrifft die Symcon-Kachelvisualisierung im Browser: Die einzelnen Fühlerbuttons funktionieren erst nach einer Änderung der Rasterbreite/-höhe, nicht erst durch Maximieren. Die rechte Kopfspalte bekommt nun ausdrücklich die volle Breite ihrer Grid-Spalte. Zuvor konnte sie durch ihre Endausrichtung und prozentuale Kindbreiten von ihrer Inhaltsgröße abhängig sein. Namen und Buttons dürfen innerhalb der zugewiesenen Breite schrumpfen, statt darüber hinauszuragen. Die mittige Temperaturkachel bleibt unverändert angeordnet.

Die in 1.11.3 ergänzte Unterdrückung von Pointer-/Klickereignissen wurde wieder entfernt. Native Ereignisse bleiben damit für Symcons normale Verarbeitung verfügbar. Die Ansichtsauswahl selbst wird weiterhin unmittelbar auf den Buttonklick gestartet. Es werden keine künstlichen Größenänderungen, Resize-Ereignisse oder periodischen Seiten-Reloads ausgelöst.

Die tatsächliche Hit-Test-Situation auf der betroffenen Symcon-Seite konnte hier nicht reproduziert werden; diese Änderungen stabilisieren die bekannten Breiten- und Ereignisabhängigkeiten. Nach dem Update die Visualisierung vollständig neu laden und die Auswahl **vor einer Größenänderung** prüfen.

## Version 1.11.3: Direkte Fühlerbuttons ohne Details-Container

Die gemeldete nicht anklickbare Drahtfühlerauswahl wurde überarbeitet. Einzelne Fühler stehen jetzt in einem normalen Container; die Fühlerauswahl hängt nicht mehr von einem geöffneten Details-Element mit verborgenem Summary ab. Ab fünf Fühlern öffnet ein echter Button die Liste mit explizitem Sichtbarkeitszustand und `aria-expanded`. Escape und Erhalt der geöffneten Auswahl bei Live-Daten bleiben vorhanden.

Die Kopf-Bedienelemente erhalten eine eigene Ebenenreihenfolge vor der Temperaturkachel. Kinder der Fühlerbuttons sind keine eigenen Pointer-Ziele; Symbol und Name gehören damit zur gesamten Buttonfläche. Pointer-/Klickereignisse werden nicht an übergeordnete Kachel-Gesten weitergegeben. Geräte-Schreibschutz sperrt die reine Ansichtsauswahl weiterhin nicht.

Die genaue Ursache im betroffenen Client wurde hier nicht reproduziert. Die Änderungen sichern die bekannten möglichen Eingabekonflikte im bisherigen Container ab; DOM-Verträge ersetzen keinen echten Hit-Test. Nach dem Update die Visualisierung vollständig neu laden, im Browser gegebenenfalls mit **Strg+F5**, damit auch das geänderte HTML geladen wird.

## Version 1.11.2: Neuinstallation ohne SDK-Konstanten

Der gemeldete Fehler `Undefined constant "INSTANCE_VISUALIZATION_TYPE_HTML"` beim Übernehmen einer neuen Instanz ist korrigiert. Die Initialisierung prüft die Verfügbarkeit der Visualisierungskonstanten. Fehlen sie, verwendet sie die [offiziell dokumentierten Werte](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setvisualizationtype/) **1 für HTML** und **0 für ausgeschaltet**, ohne globale Konstanten nachzudefinieren. Optionales Vollbild wird weiterhin nur bei vorhandener Vollbild-Konstante verwendet, sonst normales HTML.

Die Testlaufzeit stellt HTML-/NONE-Konstanten jetzt absichtlich nicht bereit. Damit werden Neuinstallation, Vollbild-Rückfall, Abschalten und Wiederaktivierung unter derselben Fehlersituation geprüft. Beim Abschalten werden zusätzlich die Versand-Hashes zurückgesetzt, damit Wiederaktivierung immer vollständige Initialdaten einschließlich Bildern sendet.

Das Update umfasst module.php und libs/Visualization.php; am besten das gesamte Bibliothekspaket aktualisieren. Bei direktem Dateiaustausch Symcon neu starten und anschließend die Instanzänderungen erneut übernehmen. GUIDs, Eigenschaften, Variablen und IDs bleiben erhalten. Ein erneuter Test auf deiner tatsächlichen Neuinstallation ist noch erforderlich.

## Version 1.11.1: Kopftemperatur exakt mittig

Die obere Temperaturkachel ist auf breiten Ansichten zur gesamten Visualisierung zentriert. Gleich breite äußere Kopfspalten ersetzen die zuvor asymmetrische Aufteilung. Auf schmalen Ansichten bleibt die eigene, zentrierte Zeile erhalten. Nach dem Update Kachel/IPSView neu laden.

## Version 1.11.0: Kompakter Kopf und skalierbare Fühlerauswahl

Der freigegebene Entwurf ist umgesetzt: Titel höher links, Verbindung und Aktualisierung direkt darunter, Temperaturkachel mit Bildern in der Mitte und Drahtfühlerauswahl oben rechts über den Geräteinformationen. Die Fühlerübersicht benötigt keine eigene Schalterzeile mehr und rückt damit näher an den Kopfbereich.

Bis vier gültige Drahtfühler stehen die Schalter direkt im Kopf. Drei bzw. vier Buttons bilden ein zweispaltiges Raster mit zwei Zeilen; lange Namen werden sichtbar gekürzt, bleiben aber vollständig in Tooltip/Bedienlabel erhalten. Ab **fünf** Drahtfühlern erscheint stattdessen **Fühler auswählen**. Die native aufklappbare Liste zeigt alle Fühler einschließlich ausgeblendeter Kacheln. Ihre Buttons behalten die bisherigen Sichtbarkeitseinstellungen. Die Liste bleibt bei Live-Daten und nach einer Auswahl geöffnet; Escape schließt sie mit Fokus zurück am Schalter. Bei sinkender Fühlerzahl kehren die direkten Buttons zurück.

Das aufgeklappte Menü liegt über dem Inhalt und vergrößert die Kopfzeile nicht. Die Kopftemperatur bleibt oben ausgerichtet, auch wenn die direkte Buttonleiste mehrere Zeilen benötigt. Auf schmalen Flächen werden Kopfbereiche weiterhin umgebrochen; der Menüinhalt bleibt innerhalb der Fensterbreite. Alle bisherigen Sichtbarkeits-, Warn-, Vorgaben- und Pushover-Funktionen bleiben erhalten.

Nach dem Update Kachel/IPSView neu laden. Der zuvor gezeigte Bildentwurf ist eine gestalterische Vorlage; reale Pixelabstände und die Menübedienung im Zielclient sind noch zu prüfen.

## Version 1.10.2: Kurze Rückmeldungen und Bilder an der Kopftemperatur

Die Infoleiste mit Rückmeldungen wie „Fühler eingeblendet“ wird nach **vier Sekunden** automatisch vollständig ausgeblendet. Fehlerrückmeldungen bleiben **acht Sekunden** sichtbar. Ein neuer Hinweis erhält seine eigene Anzeigedauer; ein älterer Timer kann ihn nicht vorzeitig entfernen. Dauerhafte Geräte-/Pushover-Fehler bleiben weiterhin im separaten Fehlerbereich sichtbar.

Wenn die Kopftemperatur einen gültigen Wert zeigt, stehen die hochgeladenen Bilder jetzt direkt in ihrer Kachel: **Bild 1 links, Bild 2 rechts** vom Messwert. Name und Temperatur bleiben im mittleren Bereich; auch bei nur einem Bild wird dafür Platz symmetrisch reserviert. Die vorhandene Anordnung „untereinander“ zeigt beide Bilder links vom Messwert. Ohne gültige/eingeschaltete Kopftemperatur erscheinen die Bilder wieder beim Titel, sodass Uploads nicht verschwinden. Einzelnes Ausblenden und die ursprünglichen Uploads bleiben erhalten.

Der linke Titelbereich wird damit bei sichtbarer Kopftemperatur von den Bildern entlastet. Die Darstellung skaliert auf schmalen Flächen herunter. Nach dem Update Kachel/IPSView neu laden. Echte Zielclient-Darstellung bleibt zu prüfen.

## Version 1.10.1: Direkte Vorgabenwahl und mehr Platz im Kopfbereich

Die Drahtfühler-Buttons zeigen nur noch Augen-Icon und Fühlernamen; die bisher zusätzlich sichtbare Kanalnummer entfällt. Die Kanalnummer bleibt intern zur eindeutigen Zuordnung sowie im barrierefreien Bedienlabel erhalten.

Ein Klick auf die Vorgaben-Wertfläche öffnet jetzt direkt das native Auswahlmenü. Der vorgelagerte Aufklappschritt entfällt. Nach einer Auswahl erscheint **Übernehmen**; erst dieser Klick schreibt den Grenzwert. Eine bestätigte Vorgabe bleibt weiterhin mit Name und Temperatur sichtbar und nach Neuladen erhalten. Im Schreibschutz ist das native Menü gesperrt. Die gesamte Wertfläche ist der Auswahlbereich; der Tastaturfokus wird sichtbar umrandet.

Auf breiten Flächen bekommt der linke Titel-/Bildbereich mehr Breite und die obere Temperaturkachel rückt etwas nach rechts. Titelbilder sind von 56 × 62 auf 80 × 88 Pixel vergrößert, auf kleinen Flächen von 40 × 46 auf 54 × 60 Pixel. Sie bleiben proportional ohne Zuschnitt. Auf schmalen Flächen gilt weiterhin das umgebrochene Kopflayout. Nach dem Update Kachel/IPSView neu laden; Menüöffnung, Bildgrößen und Abstände müssen im echten Client noch geprüft werden.

## Version 1.10.0: Drahtfühler direkt in der Kachel ein-/ausblenden

Neben **Fühlerübersicht** steht eine kompakte Leiste mit Augen-Buttons für aktuell gültige kabelgebundene Fühler. Jeder Button zeigt Kanalnummer und Anzeigenamen. Anklicken blendet seine Kachel aus; der durchgestrichene Button bleibt zum erneuten Einblenden sichtbar. Bluetooth-/MEATER-Sensoren und die separate Kopftemperatur werden nicht über diese Leiste geschaltet. Bei schmaler Kachel bricht die Leiste unter die Überschrift um.

Die Ansichtsauswahl wird in der Instanz gespeichert und bleibt nach Neuladen oder erneutem Start der Modullaufzeit erhalten. Sie ist an den konfigurierten Gerätehost gebunden. Die Buttons ändern weder Gerätekanäle noch Variablen, Archive oder Modul-Eigenschaften und benötigen keinen Geräte-Schreibzugriff. Auch bei deaktivierter Gerätebedienung kann die reine Ansichtsauswahl verwendet werden. Der bestehende Schalter „Anzeigen“ in der Modul-Kanaltabelle bleibt übergeordnet: Dort ausgeschlossene Fühler werden von der Leiste nicht freigegeben.

Für über die Leiste ausgeblendete Drahtfühler werden keine Pushover-Zielmeldungen ausgelöst; Temperaturabfrage, Variablen und interne Verläufe laufen weiter. Veraltete Kachelanfragen können ihre Obergrenze nicht verstellen. Andere Sensoren behalten ihre Funktionen. Offene Eingaben anderer Fühler bleiben beim Umschalten erhalten. Wird ein zuvor versteckter Kanal als Kopftemperatur gewählt, erscheint er dort unabhängig von der unteren Auswahl.

Auch wenn alle Fühlerkacheln ausgeblendet sind, bleiben Leiste und ein Hinweis zum Wiederherstellen erreichbar. Ungültige/abgesteckte Fühler bekommen keine Temperaturkachel und keinen Schalter; bei Wiederverbindung gilt die gespeicherte Auswahl. Nach dem Update Kachel/IPSView neu laden. Die tatsächliche Darstellung im Zielclient bleibt zu prüfen.

## Version 1.9.0: Zielwarnung und optionale Pushover-Nachrichten

Die Temperatur wird standardmäßig **1 °C vor der Obergrenze rot** und bleibt ab Zielerreichung rot. Im Modul sind **0,5 / 1 / 2 °C Vorlauf** oder ausschließlich Zielerreichung wählbar. Bei Fahrenheit entspricht der Vorlauf derselben Temperaturdifferenz, z. B. 1 °C = 1,8 °F. Optional kann der Wert sanft pulsieren. Animationen aus und reduzierte Bewegung unterdrücken das Pulsieren. Bei MEATER wird nur der Kernwert rot, nicht der Garraummesswert. Ausgeschlossene/ungültige Kanäle, Kopfkanal und MEATER-Garraum gehören nicht zu den Kern-Zielwarnungen.

### Pushover einrichten

1. **Pushover App-/API-Token** und **User-/Gruppenschlüssel** im Modul eintragen. Optional einen einzelnen Pushover-Gerätenamen angeben; leer bedeutet alle Geräte des Empfängers.
2. **Pushover-Benachrichtigungen aktivieren** einschalten. Vorwarnung und Meldung beim Erreichen sind getrennt wählbar. Der gewählte Vorlauf gilt auch für die Vorwarnung. Pushover ist standardmäßig aus.
3. In **Fühler in der Kachel** bestimmt der Schalter **Pushover**, welche eingeblendeten Kern-/Einzelfühler melden. Kopfkanal und MEATER-Garraum bleiben ausgenommen. Ein deaktivierter visueller Farbeffekt schaltet den separat aktivierten Nachrichtenversand nicht ab.

Eine Nachricht enthält den eigenen Fühlernamen sowie Ist- und Zielwert, z. B. „Rumpsteak links: Kerntemperatur fast erreicht. Ist 69,5 °C, Ziel 70,0 °C.“ Der Versand erfolgt serverseitig beim erfolgreichen Geräteabruf und benötigt keine geöffnete Visualisierung oder Geräte-Schreibfreigabe. Das Aktivieren kann bereits beim nächsten Abruf eine Meldung auslösen, wenn die Schwelle schon erreicht ist. Es wird kein gesonderter Test-Push beim Speichern gesendet.

Pro Schwelle wird nach bestätigter Annahme nur einmal gemeldet. Der Meldezustand bleibt über Neustarts erhalten. Kurzzeitiges Pendeln löst keine erneuten Meldungen aus. Ein neues Ziel/eine neue Messquelle startet einen neuen Zyklus; alternativ werden die Schwellen nach fünf Minuten durchgehend beobachteter Abkühlung mindestens 2 °C unter den Vorwarnbereich neu freigegeben. Lange Datenlücken unterbrechen diese Abkühlzeit. Wird der Vorwarnbereich zwischen zwei Abrufen übersprungen, folgt direkt die Zielmeldung. Bei mehreren Fühlern erfolgt höchstens ein Versandversuch pro Abruf.

Temporäre Netz-/Serverfehler führen frühestens nach 60 Sekunden zum nächsten Versuch. Eine unklare Antwort kann trotz dieser Begrenzung eine doppelte Zustellung verursachen. Bei API-Ablehnung (4xx oder Status ungleich 1) stoppt die Wiederholung. Schlüssel/Empfänger/Kontingent prüfen und danach **Pushover-Fehler zurücksetzen** verwenden; Änderungen der relevanten Einstellungen erlauben ebenfalls einen neuen Versuch. Der Hinweis steht im Modul und in der Kachel. Versandfehler unterbrechen die Thermometerabfrage nicht. Pushover-Bestätigung bedeutet Annahme durch den Dienst, keine nachgewiesene Handy-Zustellung.

Die optionale Integration sendet Titel, Fühlername und Temperaturwerte über HTTPS an Pushover. Schlüssel bleiben serverseitig und erscheinen nicht in Kacheldaten oder Gerätesnapshot. Transport mit TLS-Prüfung, ohne Weiterleitungen und mit fünf Sekunden Gesamtlaufzeit; normale Priorität. Grundlage ist die [offizielle Pushover-API](https://pushover.net/api). Keine neue Statusvariable notwendig. Echter Versand mit deinem Konto und Clientdarstellung sind noch zu prüfen.

Der Sammlungsblock heißt jetzt **Vorgaben**. Die MEATER-Obergrenze steht rechts unter dem Vorgabenblock. Nach dem Update Kachel/IPSView neu laden.

## Version 1.8.0: Vorlagen als Wertkachel und eigene Titelbilder

Die Kerntemperatur-Sammlung verwendet jetzt denselben kompakten, aufklappbaren Wertblock wie die Obergrenze. Geschlossen bleiben Temperatur und Name der ausgewählten Vorlage sichtbar. Auswahl und Übernehmen stehen im aufgeklappten Bereich. Erst ein bestätigter Schreib-/Rücklesevorgang speichert die Vorlage pro Fühler in einem Instanzattribut. Danach bleibt sie auch nach erneutem Laden der Kachel bzw. der Modullaufzeit sichtbar. Eine manuelle Grenzänderung, eine nicht mehr passende Obergrenze, eine geänderte Messquelle oder eine entfernte/geänderte Vorlage wird nicht als alte bestätigte Auswahl dargestellt. Eine noch nicht übernommene Auswahl ist nur lokal sichtbar und wird bei einem Neuladen nicht gespeichert.

Im Visualisierungsbereich der Instanz lassen sich **Titelbild 1** und optional **Titelbild 2** hochladen und jeweils ein-/ausblenden. Die Anordnung wählt zwischen einem Bild vor und einem hinter dem Titel oder beiden Bildern untereinander vor dem Titel. Ohne eingeblendete Bilder erscheint die bisherige Flamme. Bilder werden proportional und ohne Zuschnitt dargestellt. PNG, JPEG, GIF und WebP bis 256 KiB und 2048 × 2048 Pixel pro Bild sind zulässig. Die Bilder bleiben in der Instanz gespeichert; externe Bildserver und zusätzliche Statusvariablen werden nicht benötigt. Ausblenden löscht den Upload nicht.

Der Upload verwendet das offizielle Symcon-Formularelement [SelectFile](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/configuration-forms/selectfile/), das Dateiinhalt als Base64-Eigenschaft speichert. Die Kachel erhält geprüfte Bilddaten direkt. Vollständiges Laden enthält beide Bilder; unveränderte Bilder werden bei anschließenden Live-Updates ausgespart und im Client erhalten.

Die Fühler-/Messwertzählung rechts über der Übersicht wurde entfernt. Geräte-/Variablennamen und IDs bleiben erhalten. Nach dem Update Kachel/IPSView neu laden. Reale Upload-Bedienung und Bilddarstellung im Zielclient sind noch zu prüfen.

## Version 1.7.0: Eigene Fühlernamen und Kerntemperatur-Sammlung

In **Fühler in der Kachel → Eigener Anzeigename** kannst du jeden Fühler für die Visualisierung benennen. Das Original bleibt in der Tabelle sichtbar. Leere Anzeigenamen verwenden den bisherigen Namen. Bei einer gemeinsamen MEATER-Kachel bestimmt der eigene Anzeigename des Kernkanals den Titel. Die automatische MEATER-Zuordnung verwendet weiterhin die Originalnamen; Gerätekanäle, Variablen und IDs werden nicht umbenannt.

Unter **Kerntemperatur-Sammlung** lassen sich Vorlagen mit Name und Temperatur hinzufügen, ändern und entfernen. Als gewünschte Startwerte sind „Rumpsteak · 57 °C“ und „Pulled Pork · 94 °C“ hinterlegt. Die Werte werden in Celsius gespeichert; bei Fahrenheit am Gerät werden Anzeige und Schreibwert umgerechnet und auf ganze Grad gerundet. Zulässig sind bis zu 100 Einträge, ganze Temperaturen von -30 bis 300 °C und Namen bis 240 Bytes. Eine leere Sammlung blendet die Auswahl aus.

In jeder bedienbaren Fühlerkachel steht rechts eine kleine Sammlungsauswahl. Auswahl allein schreibt nichts. **Übernehmen** setzt die gewählte Temperatur als Obergrenze genau dieses Kanals über den bestehenden Schreib-/Rücklesepfad. Schreibschutz, ausgeblendete/ungültige Fühler und automatische Min-Anpassung bleiben berücksichtigt. MEATER-Garraummessstellen erhalten keine Kerntemperatur-Sammlung. Auswahl bleibt bei Live-Daten bestehen; entfernte oder veränderte Vorlagen werden zurückgesetzt und nicht nach Listenposition ersetzt.

Geräteinfos stehen rechtsbündig im Kopfbereich. Die Obergrenzenanzeige ist in ihrer kleinen Fläche zentriert und neben der Sammlung weiter nach links gerückt. Auf schmalen Flächen wird die Sammlung unter den Messwerten angeordnet. Nach dem Update Kachel/IPSView neu laden. Die echte Clientdarstellung bleibt zu prüfen.

## Version 1.6.2: Aufgeräumter Kopfbereich und direkt sichtbare Grenzen

Die Kopftemperatur steht auf breiten Flächen größer und mittig zwischen Titel und Gerätestatus. Das zusätzliche WLANTHERMO-Label, die Kanaltexte in Kopf- und Fühlerkacheln sowie das Obergrenzen-Bearbeitungsfeld der Kopfkachel wurden entfernt. Die Kopfkachel ist jetzt eine reine Temperaturanzeige. Geräteinformationen, Akku/WLAN, Bluetooth und Aktualisierungszeit stehen kompakt oben rechts bei der Verbindungsanzeige. Die Einstellung für zusätzliche Gerätedetails blendet diese Informationen weiterhin aus.

In jeder bedienbaren Fühlerkachel steht die Obergrenze oben neben dem Messwert, bei gepaarten MEATER-Messstellen unter den beiden Messwerten. Der Grenzwert ist auch bei geschlossenem Bearbeitungsfeld und bei Schreibschutz sichtbar. Durch Aufklappen wird nur die Eingabe geöffnet. Im Schreibschutz bleibt der Editor geschlossen und verborgen; MEATER-Garraummessstellen erhalten weiterhin kein Ziel und keine Eingabe. Der untere doppelte Grenzwert und der bisherige Bearbeitungsblock am Kachelende entfallen.

Auf schmalen Flächen wandert die mittige Kopftemperatur in eine eigene Zeile. Keine Änderungen an Gerätekanälen, Variablen oder IDs. Kachel/IPSView nach dem Update neu laden. Die mitgelieferte Vorschau verwendet synthetische Messwerte; die Darstellung auf dem echten Client ist noch zu prüfen.

## Version 1.6.1: Freier Titel und Temperaturkachel im Kopfbereich

**Titel der Kachel** ersetzt den bisherigen festen Text „Grillstation“. Unter **Kompakte Temperaturkachel oben im Kopfbereich anzeigen** lässt sich zwischen Titel und Verbindungsanzeige ein einzelner Temperaturkanal anzeigen. **Temperaturkanal für die Kachel im Kopfbereich** wählt die Kanalnummer, **Name der Temperaturkachel** die freie Beschriftung, etwa „Grilltemperatur“ oder „Räucherkammertemperatur“. Standardmäßig ist die Kopfanzeige für Kanal 1 mit dem Namen „Grilltemperatur“ eingeschaltet. Leere Texte verwenden die Standardbeschriftungen.

Der dort sichtbare Kanal entfällt in der unteren Fühlerübersicht, auch wenn er Teil eines MEATER-Paares ist. Wird die Kopfanzeige abgeschaltet, erscheint der weiterhin freigegebene und gültige Kanal wieder unten. Fühler-/Messwertzähler beziehen sich auf die untere Übersicht. Wenn kein weiterer Fühler verbleibt, entfällt der gesamte leere Übersichtsbereich.

Ausgeschlossene, ungültige oder nicht erreichbare Kanäle erscheinen auch oben nicht; es gibt keinen automatischen Ersatzkanal. Die Kopfkachel zeigt Messwert und Einheit. Bei bedienbaren Kanälen ist die Obergrenze dort weiterhin aufklappbar; Schreibschutz und Rückleseprüfung bleiben wirksam. MEATER-Garraummessstellen bleiben reine Anzeigen. Offene Eingaben bleiben bei Messwertaktualisierungen erhalten und werden beim Kanalwechsel verworfen.

Auf schmalen Kacheln rückt die Kopftemperatur unter Titel/Verbindung. Die bestehende große optionale Hauptanzeige bleibt separat konfigurierbar. Texte ändern nur die Visualisierung; Geräte-/Variablennamen und IDs bleiben erhalten. Nach dem Update Kachel/IPSView neu laden, bei direkter Dateikopie zusätzlich Symcon neu starten.

## Version 1.6.0: Ein MEATER, eine gemeinsame Kachel

Ein klassischer MEATER mit zwei Messstellen erscheint als eine gemeinsame Kachel: **Kerntemperatur** und **Garraumtemperatur** stehen nebeneinander. Obergrenze, Fortschritt und Restzeit beziehen sich auf den Kern. Die Garraumtemperatur ist eine reine Messwertanzeige ohne Obergrenzenfeld, Zielwarnung oder Restzeitprognose. Das gilt auch, wenn der Garraum als optionaler Hauptkanal gewählt wird. Normale kabelgebundene Garraumfühler behalten ihre bisherige Bedienung.

Die HTTP-Kanalliste des geprüften Firmwarestands enthält keine Bluetooth-Adresse oder lokale Messstellen-ID. Eine Paarung allein anhand benachbarter Kanalnummern wäre daher unzuverlässig. Das Modul erkennt eindeutige Namenspaare wie `MeaterKT`/`MeaterGT` oder `MEATER 1 Kerntemperatur`/`MEATER 1 Garraumtemperatur`. Beide Kanäle müssen Bluetooth-Kanäle sein und denselben eindeutigen Namensteil vor der Messstellenbezeichnung tragen. Bei mehreren gleich benannten oder anders benannten Geräten bitte in **MEATER-Zuordnung** pro Thermometer einen Anzeigenamen, den Kernkanal und den Garraumkanal eintragen. Diese ausdrückliche Zuordnung hat Vorrang. Hiermit lassen sich auch selbst benannte Messstellen zuordnen. Zusätzliche Messstellen neuerer MEATER-Modelle werden nicht automatisch erraten.

Nur freigegebene, aktuell gültige Messwerte erscheinen. Fehlt der Kern, bleibt ein gültiger Garraummesswert als reine Anzeige sichtbar; fehlt der Garraum, bleibt nur der Kern mit seiner Bedienung. Es werden keine alten Werte oder Platzhalter für ausgeschlossene Messstellen eingeblendet. Ohne eindeutige Zuordnung bleiben die Kanäle getrennt.

Die Bezeichnungen ändern ausschließlich die Visualisierung. Gerätekanäle, Variablen, IDs und Archivdaten werden nicht umbenannt oder zusammengelegt. Die allgemeine Skriptfunktion bleibt unverändert. Kachel-Schreibversuche auf erkannte/zugeordnete Garraummessstellen werden auch serverseitig abgewiesen. Nach dem Update Kachel/IPSView neu laden; bei direkter Dateikopie zusätzlich Symcon neu starten.

## Version 1.5.2: Restzeit im Hauptbereich

Die optionale große Hauptanzeige zeigt die geschätzte Restdauer deutlich hervorgehoben unter der Temperatur. Darunter steht der voraussichtliche Zeitpunkt zum Erreichen der Obergrenze. Bei Plateau, Abkühlung oder unzureichenden Messwerten erscheint der passende Hinweis anstelle einer veralteten Schätzung. Die Hauptanzeige bleibt über **Große Haupttemperatur oben anzeigen** einschaltbar; die Prognose lässt sich weiterhin separat deaktivieren. Die Vorschau zeigt die Hauptanzeige eingeschaltet mit synthetischen Testdaten.

## Version 1.5.1: Kompakte Restzeit und automatische Grenzanpassung

Die Restzeitschätzung belegt in jeder Fühlerkarte nur noch **eine schmale Zeile**, z. B. „ca. 23 Min. · gegen 18:35“. Ein Klick auf diese Zeile öffnet Verlauf, Trenddetails, Erläuterungen und den Verlauf-Neustart. Die Details sind standardmäßig geschlossen und bleiben bei Live-Aktualisierungen geöffnet, wenn du sie geöffnet hast. Die normale Temperaturanzeige bleibt im Vordergrund.

Der Fortschrittsbalken gleitet bei neuen Messwerten weich zum aktualisierten Wert. Während einer gültigen Aufheizprognose läuft ein dezenter Lichtakzent über den gefüllten Teil. Bei Plateau, Abkühlung oder Zielerreichung endet der Lichtlauf. Das sind rein visuelle Effekte: Der Balkenwert bleibt der gemessene Temperaturfortschritt, keine künstliche Zeitfortschreibung. Die Einstellung **Animationen** und `prefers-reduced-motion` schalten die Bewegung ab.

### Obergrenze ändern, ohne die Untergrenze anzeigen zu müssen

Beim Schreiben einer Obergrenze über die Kachel wird der aktuelle Kanal unter der bestehenden Instanzsperre frisch vom Gerät gelesen. Liegt seine Untergrenze über der neuen Obergrenze, passt das Modul sie im selben Schreibaufruf auf **Obergrenze minus 1 Grad** an. Beispiel: bisher Min 150, neue Obergrenze 120 → Min 119, Max 120. Am unteren erlaubten API-Wert -30 werden beide Grenzen -30, da -31 nicht zulässig ist. Die Gradschritte verwenden die aktuelle Geräteeinheit.

Bereits passende Untergrenzen bleiben unverändert. Die Kachel sendet die beiden Werte gemeinsam und bestätigt sie erst nach dem Rücklesen; eine automatische Anpassung wird in der Rückmeldung genannt. Der globale Schreibschutz bleibt wirksam. Die allgemeine Skriptfunktion `WLT_SetChannel` bleibt strikt: Sie passt Untergrenzen nicht ohne ausdrückliche Angabe an. Zusätzliche Variablen werden nicht benötigt.

Nach dem Update die Kachel/IPSView neu laden; bei direkter Dateikopie zusätzlich den Symcon-Dienst neu starten. Die mitgelieferte Kachelvorschau verwendet den tatsächlichen Kachelcode mit synthetischen Testdaten. Layout und Animation auf deinem konkreten IPSView-Client müssen noch geprüft werden.

## Version 1.5.0: Restzeitschätzung bis zur Obergrenze

Die Fühlerkarten bieten eine **ungefähre Restzeit**, den **voraussichtlichen Zielzeitpunkt** und den **Temperaturanstieg pro Minute**; seit 1.5.1 stehen die ausführlichen Trendangaben in den aufklappbaren Details. Ziel ist ausschließlich die eingestellte Obergrenze. Die optionale große Hauptanzeige zeigt ebenfalls die Restzeit. Min-Wert und Min-Eingabe bleiben aus der Kachel entfernt.

Die kleine Verlaufsgrafik enthält nur tatsächlich abgerufene Messpunkte. Eine punktierte Linie markiert die Obergrenze. Ein gestrichelter Abschnitt schreibt den aktuellen Trend fort, solange eine Schätzung möglich ist. Um den Messverlauf nicht zusammenzudrücken, zeigt die Grafik höchstens einen zusätzlichen Trendabschnitt von der Länge des sichtbaren Messfensters (mindestens fünf Minuten); weiter entfernte Zielzeiten stehen als Text. Eine lange Restzeit bedeutet deshalb nicht, dass die gestrichelte Linie im Diagrammausschnitt bereits die Obergrenze erreicht.

### Verwendung

1. Modul aktualisieren und Kachel bzw. IPSView neu laden. Bei direkter Dateikopie den Symcon-Dienst neu starten.
2. Unter Premium-Visualisierung ist **Restzeit bis zur Obergrenze schätzen** standardmäßig aktiv. Das Diagramm lässt sich separat ein-/ausschalten. Standard für das Trendfenster: **10 Minuten**, einstellbar von 5 bis 60 Minuten.
3. Nach mindestens **3 Minuten und 6 Messpunkten** wird nur bei ausreichend gleichmäßigem Anstieg eine Restzeit angezeigt. Bei langem Abrufintervall dauert das Sammeln entsprechend länger; der normale 10-Sekunden-Abruf ist geeignet.
4. Für einen neuen Garvorgang die Restzeitzeile aufklappen und am jeweiligen Fühler **Verlauf neu starten** drücken. Das ändert keine Geräteparameter und funktioniert auch bei global deaktivierten Geräteschreibzugriffen, sofern die Kachelbedienung erlaubt ist. Der nächste tatsächliche Geräteabruf beginnt den neuen Verlauf.
5. Ändert sich die Obergrenze, wird die Restzeit aus dem bisherigen Temperaturverlauf neu berechnet. Ein neuer Garvorgang benötigt dagegen einen neuen Startpunkt.

### Berechnung und Grenzen

Die Schätzung verwendet eine lineare Ausgleichsgerade über das gewählte Zeitfenster. Zusätzlich wird der jüngste Trend geprüft. Restzeit = Abstand zur Obergrenze / positiver Temperaturanstieg. Die Anzeige bezeichnet sich ausdrücklich als **Trend-Schätzung**; sie modelliert weder Fleischart noch Nachgaren oder eine zukünftige Änderung der Heizleistung.

- Bei Plateau, Abkühlung, zu wenig Temperaturanstieg oder stark schwankendem Verlauf erscheint ein Hinweis statt einer Restzeit. Auch eine über zwölf Stunden entfernte lineare Prognose wird nicht als belastbare Zeit angezeigt.
- Voraussetzung für die Schätzung sind unter anderem mindestens 0,3 °C Temperaturanstieg im Fenster, ein Anstieg über 0,02 °C/min und eine ausreichend gleichmäßige Messreihe. Für Fahrenheit werden diese Kriterien äquivalent umgerechnet. Diese Prüfkriterien sind Plausibilitätsfilter, keine statistische Garantie.
- Bei ungültigen/entfernten Fühlern wird der zugehörige Verlauf verworfen. Fühlername/-typ, Geräteadresse oder Einheit ändern den Verlaufskontext. Eine Unterbrechung von mehr als dem größeren Wert aus zwei Minuten und drei Abrufintervallen startet neu. Auch ein abrupter Sprung von mindestens 6 °C innerhalb einer Minute startet konservativ neu.
- Ein identisch benannter Ersatzfühler oder ein neues Gargut lässt sich nicht immer automatisch erkennen. Dafür gibt es den manuellen Neustart des Verlaufs.
- Große Fleischstücke können lange Temperaturplateaus entwickeln; diese und wechselnde Garraumtemperaturen können eine vorherige Prognose stark verändern. Die Anzeige wird mit neuen Messwerten nachgeführt. Sie ist keine Zusicherung einer Garzeit.

### Balken und Lizenzverbrauch

Sobald ein Startwert vorhanden und die Obergrenze höher als dieser Startwert ist, zeigen Balken und Ring den **Temperaturfortschritt seit Start**: `(aktuell − Start) / (Obergrenze − Start)`. Das ist kein Anteil der bereits abgelaufenen Garzeit. Ohne geeigneten Startwert bleibt die bisherige Temperaturanzeige relativ zur positiven Obergrenze verfügbar.

Es werden **keine zusätzlichen Symcon-Variablen** und kein Archiv benötigt. Der Verlauf liegt je Fühler in einem internen Buffer: höchstens 361 Messpunkte, für die Kachel höchstens 48 echte Punkte. Nach einem Symcon-Neustart beginnt die Lernphase neu; dauerhaftes Archivieren wird nicht automatisch aktiviert. Eine deaktivierte Prognose verwirft ihre internen Reihen beim nächsten Abruf. Die vorhandene IPSView-TileHtml-Anbindung bleibt bestehen.

## Version 1.4.3: In der Visu nur die Obergrenze

Die Kachel zeigt und bearbeitet je Fühler nur noch die **Obergrenze**. Der Min-Wert und seine Eingabe sind aus Fühlerkarten, Hauptanzeige und Kacheldaten entfernt. Status und Warnfarbe beziehen sich ausschließlich auf die Obergrenze: verbleibende Temperaturdifferenz, erreicht oder überschritten. Eine Unterschreitung der am Gerät hinterlegten Untergrenze wird in der Kachel nicht als Überschreitung angezeigt.

Änderungen über die Kachel senden ausschließlich `max` an den Mini V3. Bestehende Untergrenzen am Gerät, optionale Min-Statusvariablen, deren IDs und die Skriptfunktionen bleiben erhalten. Die bestehende Gerätevalidierung verhindert weiterhin widersprüchliche Grenzen; seit 1.5.1 kann die Kachel eine kollidierende Untergrenze wie oben beschrieben automatisch anpassen.

Balken und optionaler Ring zeigen für positive Obergrenzen die aktuelle Temperatur relativ zur Obergrenze mit einer festen Basis von 0 in der gewählten Geräteeinheit. Sie zeigen weder eine Restzeit noch einen zeitlichen Garfortschritt. Bei einer Obergrenze von 0 oder darunter wird diese Verhältnisgrafik ausgeblendet; die Obergrenze als Zahl bleibt sichtbar. Ab Version 1.5.0 ergänzt die oben beschriebene Restzeitschätzung diese Temperaturanzeige.

Nach dem Update die Kachel bzw. IPSView-View neu laden, damit das neue Obergrenzenformular geladen wird. Bei direkter Dateikopie zusätzlich den Symcon-Dienst neu starten.

## Version 1.4.2: Gültige Fühler und IPSView

Die Fühlerübersicht zeigt ausschließlich aktuelle, gültige Temperaturwerte. Nicht angeschlossene Fühler (Fehlerwert 999), fehlende/nicht numerische Werte und getrennte Bluetooth-Fühler werden automatisch ausgeblendet. Bei Geräteausfall sind keine aktuellen Fühlerkarten sichtbar; ein Hinweis und der Verbindungsstatus bleiben erhalten. Nach dem nächsten erfolgreichen Abruf kehren gültige Fühler zurück. Bestehende Symcon-Variablen, ihre IDs und Archivzuordnungen werden dadurch nicht gelöscht oder verändert.

Die **große Haupttemperatur oben ist standardmäßig aus**. Unter Premium-Visualisierung kann sie optional eingeschaltet werden, z. B. für den Garraum. Im Feld **Kanal für die optionale Hauptanzeige** den gewünschten Kanal einstellen. Ist ein fester Hauptkanal ungültig oder ausgeblendet, verschwindet die Hauptanzeige; es wird kein anderer Fühler als Ersatz eingeblendet. Mit Kanal 0 wird der erste gültige Fühler genutzt; nur in diesem automatischen Modus kann die Auswahl lokal per Fühlerkopf/Tastatur gewechselt werden.

### IPSView als TileHtml

Die Kachel wird standardmäßig als normaler HTML-SDK-Instanztyp bereitgestellt. Dieser ist der vorgesehene Weg für **TileHtml** in IPSView:

1. Im IPSView-Designer ein **TileHtml** auf der gewünschten Seite platzieren.
2. In dessen Eigenschaft **ID** die ID der WLANThermo-Modulinstanz eintragen – keine Variablen-ID.
3. Premium-Visualisierung in der Instanz aktivieren und **Erweiterte HTML-Vollbildansicht verwenden** ausgeschaltet lassen.
4. View auf den IPSView-Client übertragen/neu laden und eine zur Fühlerzahl passende Breite/Höhe einstellen. Im Designer kann nur ein Symbol erscheinen; der tatsächliche Inhalt wird im Client geladen.

Es wird weder eine zusätzliche HTML-Box noch eine Container-Variable benötigt. Live-Daten und Bedienung verwenden weiterhin das offizielle HTML-SDK. Die [IPSView-Herstellerdokumentation zu TileHtml](https://docu.brownson.at/viewdesigner/WebHelp/DesignerRepositoryTypeTile.html) beschreibt diese Zuordnung per Instanz-ID. Die Einbindung ist entsprechend vorbereitet; ohne deinen konkreten IPSView-Client kann der tatsächliche Anzeige-/Bedienbetrieb nicht als getestet bestätigt werden.

Für die Symcon-Kachelvisualisierung kann **Erweiterte HTML-Vollbildansicht verwenden** optional aktiviert werden. Dieser Typ wird nur verwendet, wenn die Laufzeit ihn unterstützt (Symcon ab 9.1). Die Einstellung ist standardmäßig aus, auch beim Update. In niedrigen Kacheln bleibt die Fühlerübersicht jetzt auch ohne große Hauptanzeige sichtbar.

## Hotfix 1.4.1: Instanzerstellung korrigiert

Version 1.4.0 übergab PHP-Arrays an `UpdateVisualizationValue()`. Die gemeldete native Symcon-Wertkonvertierung wies diese mit „Cannot auto-convert value for parameter Value (Type is not supported)“ zurück; dadurch konnte bereits die Instanzerstellung scheitern.

Version 1.4.1 überträgt alle Live-Nachrichten einschließlich Deaktivierung und Fehlerquittierungen als **JSON-Text**. `handleMessage()` unterstützt diesen Text bereits. Der letzte Versandstand wird nur nach erfolgreichem SDK-Aufruf gespeichert. Die Testsimulation weist Array-/Objektübergaben jetzt ausdrücklich zurück. Fünf zusätzliche Regressionstests prüfen die Typgrenze sowie die Initialisierung ohne Host und im deaktivierten Zustand.

Die Moduldateien vollständig ersetzen und bei direkter Dateikopie den Symcon-Dienst neu starten. Modul-/Bibliotheks-GUIDs, Variablen-Idents und vorhandene Konfigurationen bleiben erhalten. Bei Installation über das eigene Repository die Dateien dort übernehmen und über Module Control aktualisieren.

## Version 1.4.0: Grill-Kachel ohne zusätzliche Variablen

Die eigene **Grillstation** zeigt große Temperaturwerte, Fühlerkarten mit Obergrenzen, Pitmaster-Betriebsart und Sollwert bzw. manuellen Stellwert, Geräteverbindung, Akku/WLAN und letzten Datenstand. Bei aktivierter Hauptanzeige mit Kanal 0 wechseln Kanalüberschriften oder die Tastatur den Hauptfühler. Obergrenzen, Pitmaster und Bluetooth lassen sich direkt bedienen. Änderungen werden erst nach dem Rücklesen am Mini V3 bestätigt. Es gibt keine erfundenen Verlaufsdaten: Ring und Balken zeigen ausschließlich die aktuelle Temperatur relativ zur Obergrenze, keinen Garfortschritt.

**Neue Instanzen erzeugen standardmäßig 0 Statusvariablen.** Alle Daten werden intern gepuffert und über das offizielle HTML-SDK direkt an die Kachel übertragen. Keine HTML-Box, keine Container-, Platzhalter- oder versteckten Hilfsvariablen. Die Kachel funktioniert auch bei vollständig abgewählten Variablen. Bei bestehenden Instanzen wird der bisherige Variablenumfang übernommen; vorhandene IDs, Werte und gespeicherte Einzelentscheidungen bleiben erhalten.

### Variablen auswählen und Lizenzen sparen

Unter **Variablenverwaltung · Lizenzoptimiert** bestimmt der Standard die Felder ohne gespeicherte Einzelentscheidung:

- Automatisch: Neuinstallation keine Variablen; Update bisheriger Umfang.
- Keine Variablen: nur ausdrücklich aktivierte Tabellenzeilen.
- Empfohlen: `Online`, je Fühler `Temperature` und `Valid` (bei drei Kanälen sieben Variablen).
- Alle angebotenen Felder: vollständiger Umfang innerhalb der aktivierten Gruppen.

Gespeicherte Zeilen haben Vorrang vor dem Standard; deaktivierte Gruppen haben weiterhin Vorrang vor den Zeilen. Die Sammelaktionen **Alle auswählen**, **Alle abwählen** und **Empfohlene Auswahl** bereiten Tabellenwerte aus dem zuletzt gespeicherten Stand vor. Vorherige Änderungen zuerst übernehmen; anschließend den Vorschlag mit **Übernehmen** speichern. Die Sammelauswahl ändert keine Gruppen und keinen Standard für künftig neu erkannte Felder. Ein Zähler zeigt erkannte, ausgewählte, bestehende, zusätzlich benötigte und ungenutzte Variablen beim erneuten Öffnen des Formulars.

Pro Zeile sind Aktivierung, eigener Name, Typinformation, Darstellung, Position und optional eine vorhandene Zielvariable verfügbar. Eigener Name leer und Position -1 erhalten bisherige Objektänderungen. Eigene Tabellenwerte werden nur bei einer Änderung dieser Einstellung angewendet; spätere manuelle Namen/Positionen überschreibt Polling nicht. Namen in der Tabelle betreffen Symcon, nicht den Fühlernamen am Gerät.

**Abwählen erhält bestehende Variablen ausgeblendet mit letzter ID und letztem Wert. Ausblenden spart noch keine Lizenz.** Die Variablen werden dann nicht aktualisiert oder bedient. Automationen müssen die aktuelle Aktivierung berücksichtigen; ein eingefrorener letzter Wert ist kein aktueller Messwert. Beim Wiederaktivieren werden dieselbe ID und die vorherige Sichtbarkeit verwendet. Bereits vor diesem Update gelöschte Variablen können nicht wiederhergestellt werden.

Zum tatsächlichen Freigeben von Variablenplätzen gibt es unter Diagnose **Ungenutzte Modulvariablen entfernen …**. Erst die Auswahl übernehmen, dann die gesonderte Checkbox bestätigen und **Jetzt dauerhaft entfernen** ausführen. Nur eigene deaktivierte bzw. durch externe Ziele ersetzte Variablen werden gelöscht. IDs, Verknüpfungen und Archivzuordnungen dieser gelöschten Variablen gehen verloren; bei späterer Neuanlage entstehen neue IDs. Externe Zielvariablen und weiterhin ausgewählte eigene Variablen werden niemals durch diese Funktion gelöscht.

### Vorhandene Variablen verwenden

Die Tabelle bietet `SelectVariable` als **Vorhandene Zielvariable**. ID 0 bedeutet eine eigene Modulvariable. Ein externes Datenziel muss existieren, beschreibbar sein und genau denselben Datentyp haben. Dieselbe Variable darf nicht mehreren aktiven Feldern zugewiesen werden. Schreibgeschützte Statusvariablen anderer Module und eigene Modulvariablen werden als Ziel abgewiesen. Ausgewählte Ziele werden als Symcon-Referenzen registriert.

Das Modul aktualisiert nur den Wert eines zugewiesenen Ziels. Name, Position, Darstellung, Aktionen und Archivierung des Ziels bleiben unverändert. Es wird keine Aktion zur Gerätesteuerung an der externen Variable eingerichtet; dafür die Kachel oder die WLT-Skriptfunktionen verwenden. Bereits vorhandene eigene Variablen werden bei einer Zielzuordnung ausgeblendet erhalten und können gesondert bereinigt werden. Die Zielzuordnung wirkt auch auf Statusfelder; ein deaktiviertes Feld schreibt nicht mehr in sein Ziel.

### Premium-Visualisierung konfigurieren

**Premium-Visualisierung** bietet Aktivierung, Automatisch/Hell/Dunkel, Akzentfarbe, Kompaktmodus, Zusatzinformationen, Symbole, reduzierte Animation, Bedienfreigabe, Pitmaster-Anzeige, Hauptkanal und individuelle Fühlerauswahl. Die Fühlerauswahl der Kachel ist vollständig unabhängig von der Variablenanlage. Nach dem ersten Abruf das Formular erneut öffnen. Bei aktivierter Hauptanzeige nutzt Hauptkanal 0 den ersten gültigen sichtbaren Fühler; in diesem automatischen Modus kann lokal ein anderer gewählt werden.

Die Kachel passt das Layout per Breite und Höhe an. Kleine Kacheln zeigen vor allem Haupttemperatur, Status und Aktualisieren; größere zeigen Fühler, Bedienelemente und Pitmaster. Auf Touchgeräten sind die Eingaben vergrößert; Tastaturfokus, Fehlertexte und `prefers-reduced-motion` werden berücksichtigt. Die tatsächlichen Layouts in den Symcon-Clients sind noch zu prüfen.

**IP-Symcon ab 9.0** bleibt Mindestversion. Unter 9.0 wird `INSTANCE_VISUALIZATION_TYPE_HTML` verwendet. Ist die optionale erweiterte HTML-Vollbildansicht eingeschaltet und bietet die Laufzeit den erst ab 9.1 dokumentierten Typ `INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN`, wird dieselbe Kachel auch maximiert verwendet. Für 9.0 wird keine eigene HTML-Vollbildansicht zugesichert; der Host kann dort beim Maximieren die normale Objektliste zeigen. WebFront ist nicht Ziel dieser HTML-SDK-Kachel.

### Datenfluss und Sicherheit

`GetVisualizationTile()` liefert initial HTML mit eingebettetem CSS, JavaScript und sicher kodiertem JSON. `UpdateVisualizationValue()` sendet anschließend aktuelle, auf die Kachel beschränkte Daten als JSON-Text. `handleMessage()` aktualisiert bestehende Elemente. Es gibt keine JavaScript-Abfrageintervalle und keine externen Schriften, Bibliotheken oder Webhooks. Optionale Pushover-Nachrichten ab 1.9.0 verwenden den externen Pushover-Dienst.

`requestAction('TileAction', ...)` sendet strukturierte Aktionen. PHP prüft Aktivierung, Kachelbedienfreigabe, globalen Schreibschutz, sichtbare Kanäle/Pitmaster, Aktion und Parameter. Die vorhandenen geprüften Gerätefunktionen validieren die Werte und lesen Änderungen zurück. Eine Anfragekennung verbindet Rückmeldung und Bedienung; ausstehende Aktionen sind gesperrt. Der einmalige Rückmelde-Timeout folgt dem konfigurierten HTTP-Timeout. Ein Timeout bestätigt oder verwirft keine Geräteänderung: danach zuerst aktualisieren. Allein die Kachelberechtigung erweitert niemals den globalen Schreibzugriff.

Unbekannte oder fehlende Messwerte werden nicht als Fühlerkarte angezeigt. Bei Nichterreichbarkeit bleibt die Instanz in Standby 106, der Modultimer läuft weiter, Bedienung wird gesperrt und die Anzeige erholt sich nach erfolgreichem Abruf. Konfigurations-, Authentifizierungs- und API-Fehler bleiben sichtbar. Hinweise stehen in der Kachel und im Konfigurationsformular auch ohne Fehler-Statusvariable zur Verfügung.

### Update und Vorschau

Die vollständige Bibliothek im eigenen Repository übernehmen und über Module Control aktualisieren; die bestehende Instanz beibehalten. Modul-/Bibliotheks-GUIDs, Präfix und Daten-Idents bleiben unverändert. Bei direkter Dateikopie den Symcon-Dienst neu starten. Die vorbereitete Repositoryadresse in den Metadaten wurde in dieser Sitzung nicht veröffentlicht.

[Designvorschau mit Testdaten](docs/KACHEL-VORSCHAU.html), [Variablenübersicht](docs/VARIABLEN.md), [Lieferumfang A–I](docs/LIEFERUMFANG.md), [vollständiger Quellcode](docs/QUELLCODE.md) und [Prüfbericht](docs/VALIDIERUNG.md). Die Vorschau nutzt denselben von PHP erzeugten Kachelcode, hat aber keine Verbindung zu Symcon oder einem Gerät.

## Funktionsumfang

- Liest `/data` automatisch (Standard 10 Sekunden), `/settings` alle 5 Minuten und `/getbluetooth` pro Abruf. Manuelle Aktualisierung verfügbar.
- Übernimmt alle von diesen drei Antworten gelieferten Datenfelder: alle Temperaturkanäle, Systemwerte, WLAN-Signal, Akkuinformationen soweit vorhanden, Gerätemodell, Firmware, Fühlertypen, PID-Profile und weitere Einstellungen. Zusätzliche Felder können rekursiv als lesbare Statusvariablen angelegt werden. Gruppen und einzelne Variablen lassen sich über Kontrollkästchen auswählen. Schlüssel, die Zugangsdaten enthalten, werden ausgeblendet.
- Kanalvariablen für Temperatur, Name, Fühlertyp, Alarm Minimum/Maximum, Alarmierungsart, Farbe, festen Fühlertyp und Funkverbindungsstatus. Name, Grenzen, Alarmierungsart, Farbe und veränderbare Fühlertypen sind bedienbar.
- Kennzeichnet fehlende Messwerte (`999`), getrennte Bluetooth-Fühler, entfernte Kanäle und Geräteausfälle. Die letzte Temperatur bleibt in vorhandenen Symcon-Variablen erhalten; `Valid` und `Online` zeigen, ob sie aktuell verwendbar ist. In der Kachel sind nur gültige aktuelle Fühler sichtbar.
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
5. Danach erscheinen die vom Mini V3 zugewiesenen Temperaturkanäle automatisch in Symcon. Ihre ursprünglichen Namen stammen vom WLANthermo. Seit 1.6.0 kann die Visualisierung eindeutig benannte oder ausdrücklich zugeordnete MEATER-Messstellen als Kern/Garraum zusammenfassen; zusätzliche Messstellen werden nicht aus einer vermuteten Modellversion abgeleitet.

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

Die API-Antworten werden vollständig ausgelesen, soweit es die drei implementierten Datenendpunkte betrifft. Nicht implementiert sind direkte Bluetooth-Verbindungen vom Symcon-Server, MEATER-Cloud, WLAN-Zugangsdatenänderung, Firmware-Update, Werksreset sowie Verwaltungs-/Diagnoseendpunkte außerhalb dieser Schnittstellen. Version 1.4.0 wurde hier automatisiert geprüft; ein erneuter Test der Änderungen am realen Mini V3 und in Symcon 9.0 steht noch aus. Details zur geprüften Firmwarebasis und zu den Tests stehen in `docs/VALIDIERUNG.md`.

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

Die Firmwaregrundlage und die Besonderheiten der HTTP-/Bluetooth-Anbindung wurden aus der Dokumentation der Ausgangsversion übernommen. In Version 1.4.0 wurden diese Endpunkte nicht erweitert; ihre Kompatibilität mit dem Benutzergerät ist weiterhin am Zielsystem zu prüfen. Das Modul wurde eigenständig implementiert; Firmwarecode wird nicht mitgeliefert.

### Technische Entscheidungen

- [MaintainVariable](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/maintainvariable/): zur normalen Registrierung nur mit `true` aufrufen. Löschen erfolgt gesondert ausschließlich nach expliziter Bestätigung über `UnregisterVariable`.
- [Darstellungen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/darstellungen/): Temperatur als Wertanzeige mit °C/°F, Sollwerte als Schieberegler in 1-Grad-Schritten, Prozent als 0–100, Modi und Alarmierung als Aufzählung, Zeitstempel als Datum/Uhrzeit, Bluetooth als Schalter.
- [Konfigurationslisten](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/konfigurationsformulare/list/): gespeicherte Auswahl und neue Felder nach stabilem Ident zusammenführen; Änderungen ausschließlich mit Übernehmen speichern.
- [Store-Reviewvorgaben](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/): Name, Position und Sichtbarkeit nur als Folge der im Formular erklärten Benutzerauswahl verändern; keine Änderungen an fremden Objekten, Archivierung oder benutzerdefinierten Profilen/Aktionen.
- [IPS_SetHidden](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/objektverwaltung/ips-sethidden/) und [IPS_GetObject](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/objektverwaltung/ips-getobject/): vorherige Sichtbarkeit persistent merken und bei Reaktivierung wiederherstellen.

Die Oberfläche ist unmittelbar deutsch beschriftet; eine `locale.json` ist daher für die mitgelieferte deutsche Sprache nicht erforderlich. Externe Datenziele werden über `SelectVariable` ausgewählt und auf Existenz, Datentyp und Schreibbarkeit geprüft. Es gibt keinen Parent-/Child-Datenfluss und deshalb keine leeren ReceiveData-/ForwardData-Methoden. Timer und HTML-Kachel werden über die Symcon-Basisklasse verwaltet; externe Datenziele werden als Referenzen geführt.

### Offizielle Grundlagen der Kachel

- [HTML-SDK](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/)
- [SetVisualizationType](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setvisualizationtype/) mit Versionsgrenze des Vollbildtyps ab 9.1
- [GetVisualizationTile](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getvisualizationtile/)
- [UpdateVisualizationValue](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/updatevisualizationvalue/)
- [handleMessage](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/handlemessage/)
- [requestAction](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/requestaction/) für Aktionen ohne Statusvariablen
- [Kachelvisualisierung](https://www.symcon.de/de/service/dokumentation/komponenten/kachel-visualisierung/)
- [IPS_GetVariable](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/variablenverwaltung/ips-getvariable/) zur Typprüfung vorhandener Ziele

### Kachel in Symcon einrichten

1. Bibliothek über Module Control installieren/aktualisieren und die WLANThermo-Instanz konfigurieren.
2. Unter Premium-Visualisierung die Grill-Kachel aktivieren und Einstellungen übernehmen.
3. Die Instanz in einer für die Kachelvisualisierung sichtbaren Kategorie platzieren bzw. dort verlinken; Berechtigungen und Startkategorie der Visualisierung beachten.
4. Im Bearbeitungsmodus der Kachelvisualisierung die Instanz als Kachel anzeigen, bei Bedarf die angebotene individuelle Darstellung wählen und eine passende Kachelgröße einstellen.
5. Mit mindestens mittlerer Kachelgröße die Fühler und Pitmaster prüfen. Kleine Kacheln reduzieren den Inhalt absichtlich. Ab unterstütztem Vollbildtyp auch die maximierte Ansicht prüfen.
6. Optional nur die für Archivierung oder Automationen benötigten Statusvariablen aktivieren. Für die Kachel selbst sind keine erforderlich.

### Grundlagen der Prognose

- [Symcon GetBuffer](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getbuffer/) und [SetBuffer](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setbuffer/): interner, begrenzter Zustand ohne Lizenzvariablen; bei Dienstneustart nicht persistent.
- [MEATER: Grenzen von Garzeitprognosen](https://support.meater.com/hc/en-us/articles/36914985836315-Cook-Time-Estimate-in-the-MEATER-App): Hintergrund zu Temperaturplateaus und wechselnden Bedingungen. Das Modul verwendet eine eigene einfache Trendberechnung, nicht den MEATER-Algorithmus.

Quellen zur MEATER-Zuordnung: [offizielle HTTP-Kanalliste](https://github.com/WLANThermo-nano/WLANThermo_ESP32_Software/blob/06d456ca232bc26784d3349f79f222de41436921/src/API.cpp), [dynamische Kanalverwaltung](https://github.com/WLANThermo-nano/WLANThermo_ESP32_Software/blob/06d456ca232bc26784d3349f79f222de41436921/src/temperature/TemperatureGrp.cpp).
