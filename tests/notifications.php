<?php

declare(strict_types=1);

class NotificationTestDevice extends TestDevice
{
    public int $clock = 2000000000;
    public array $sent = [];
    public bool $failPush = false;
    public bool $rejected = false;
    protected function notificationTime(): int { return $this->clock; }
    protected function pushoverTransport(array $payload): array
    {
        $this->sent[] = $payload;
        if ($this->failPush) { throw new RuntimeException('SECRET ' . $payload['token']); }
        return ['status'=>$this->rejected ? 0 : 1];
    }
}

$alarm = new NotificationTestDevice(false);
$alarm->deviceData['channel'][1]['max'] = 70.0;
$alarm->deviceData['channel'][1]['temp'] = 68.9; $alarm->Update();
check(end($alarm->visualizationMessages)['channels'][1]['targetAlert'] === '' && $alarm->sent === [], 'Unterhalb des Vorwarnbereichs keine rote Anzeige; Pushover standardmäßig aus');
$alarm->deviceData['channel'][1]['temp'] = 69; $alarm->Update();
check(end($alarm->visualizationMessages)['channels'][1]['targetAlert'] === 'near', 'Ein Grad vor dem Ziel beginnt die Warnanzeige');
$alarm->properties['TargetWarningLeadTenths'] = 5; $alarm->Update();
check(end($alarm->visualizationMessages)['channels'][1]['targetAlert'] === '', 'Halbgrad-Vorlauf warnt nicht bereits ein Grad vor dem Ziel');
$alarm->deviceData['channel'][1]['temp'] = 69.5; $alarm->Update();
check(end($alarm->visualizationMessages)['channels'][1]['targetAlert'] === 'near', 'Halbgrad-Schwelle wird einschließlich Grenzwert erkannt');
$alarm->deviceData['channel'][1]['temp'] = 70; $alarm->Update();
check(end($alarm->visualizationMessages)['channels'][1]['targetAlert'] === 'reached' && end($alarm->visualizationMessages)['channels'][2]['targetAlert'] === '', 'Zielerreichung erkannt; MEATER-Garraum bleibt von Kernwarnung ausgenommen');
$alarm->properties['TargetWarningEnabled'] = false; $alarm->Update();
check(end($alarm->visualizationMessages)['channels'][1]['targetAlert'] === '', 'Visuelle Zielwarnung lässt sich abschalten');
$alarm->properties['PushoverEnabled'] = true; $alarm->properties['PushoverToken'] = str_repeat('T',30); $alarm->properties['PushoverUser'] = str_repeat('U',30);
$alarm->properties['TileChannels'] = '[{"Number":13,"Enabled":true,"DisplayName":"Rumpsteak links"}]';
$alarm->deviceData['channel'][1]['temp'] = 69.5; $alarm->Update();
check(count($alarm->sent) === 1 && str_contains($alarm->sent[0]['message'],'Rumpsteak links') && str_contains($alarm->sent[0]['message'],'69,5 °C') && str_contains($alarm->sent[0]['message'],'70,0 °C'), 'Vorwarnung enthält eigenen Fühlernamen, Istwert und Zieltemperatur');
$alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 1, 'Wiederholtes Messen oberhalb der Vorwarnschwelle erzeugt keine Nachrichtenflut');
$alarm->deviceData['channel'][1]['temp'] = 70; $alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 2 && str_contains($alarm->sent[1]['message'],'Kerntemperatur erreicht.'), 'Separate Zielmeldung folgt direkt bei Erreichen, ohne künstliche Sendepause nach Erfolg');
$alarm->deviceData['channel'][1]['temp'] = 69.4; $alarm->clock += 10; $alarm->Update();
$alarm->deviceData['channel'][1]['temp'] = 70.1; $alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 2, 'Kurzes Pendeln um den Zielwert erzeugt keine erneute Meldung');
$restored = new NotificationTestDevice(false); $restored->properties = $alarm->properties; $restored->attributes = $alarm->attributes; $restored->deviceData = $alarm->deviceData; $restored->Update();
check($restored->sent === [], 'Persistenter Meldezustand verhindert erneuten Versand nach Modullaufzeitwechsel');
$alarm->deviceData['channel'][1]['temp'] = 60;
for ($i=0;$i<=30;$i++) { $alarm->clock += 10; $alarm->Update(); }
$alarm->deviceData['channel'][1]['temp'] = 69.5; $alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 3, 'Nach fünf Minuten deutlicher Abkühlung wird ein neuer Aufheizvorgang wieder gemeldet');
$alarm->deviceData['channel'][1]['max'] = 65; $alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 4 && str_contains($alarm->sent[3]['message'],'65,0 °C'), 'Geänderter Zielwert eröffnet einen neuen Meldezyklus');
check(!str_contains($alarm->GetSnapshot(), str_repeat('T',30)) && !str_contains(json_encode(end($alarm->visualizationMessages)), str_repeat('U',30)), 'Pushover-Zugangsdaten gelangen weder in Gerätesnapshot noch Kacheldaten');
$alarm->properties['TileChannels'] = '[{"Number":13,"Enabled":true,"Notify":false}]'; $alarm->deviceData['channel'][1]['max'] = 60; $alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 4, 'Pushover-Schalter pro Kanal verhindert dessen Nachricht');
$alarm->properties['TileChannels'] = '[{"Number":13,"Enabled":false}]'; $alarm->deviceData['channel'][1]['max'] = 59; $alarm->clock += 10; $alarm->Update();
check(count($alarm->sent) === 4, 'Ausgeblendeter Fühler sendet keine Zielmeldung');
$failure = new NotificationTestDevice(false); $failure->properties['PushoverEnabled'] = true; $failure->properties['PushoverToken'] = str_repeat('T',30); $failure->properties['PushoverUser'] = str_repeat('U',30); $failure->failPush = true;
$failure->deviceData['channel'][1]['max'] = 60; $failure->deviceData['channel'][1]['temp'] = 60; $failure->Update();
check(count($failure->sent) === 1 && $failure->status === 102 && $failure->GetNotificationError() !== '' && !str_contains($failure->GetNotificationError(),str_repeat('T',30)), 'Versandfehler hält Polling aktiv und zeigt nur eine bereinigte Fehlermeldung');
$failure->clock += 10; $failure->Update();
check(count($failure->sent) === 1, 'Fehlgeschlagener Versand wird nicht sofort bei jedem Poll wiederholt');
$failure->clock += 51; $failure->failPush = false; $failure->Update();
check(count($failure->sent) === 2 && $failure->GetNotificationError() === '', 'Nach Sendepause wird erneut versucht und bestätigter Erfolg löscht den Hinweis');
$f = new NotificationTestDevice(false); $f->deviceData['system']['unit'] = 'F'; $f->deviceData['channel'][1]['max'] = 158; $f->deviceData['channel'][1]['temp'] = 156.2; $f->Update();
check(end($f->visualizationMessages)['channels'][1]['targetAlert'] === 'near', 'Ein Grad Celsius Vorlauf entspricht bei Fahrenheit 1,8 Grad Differenz');
$f->properties['PushoverEnabled'] = true; $f->ApplyChanges();
check($f->status === 201, 'Aktiviertes Pushover mit fehlenden Schlüsseln wird als Konfigurationsfehler abgefangen');

$rejected = new NotificationTestDevice(false); $rejected->properties['PushoverEnabled'] = true; $rejected->properties['PushoverToken'] = str_repeat('T',30); $rejected->properties['PushoverUser'] = str_repeat('U',30); $rejected->rejected = true;
$rejected->deviceData['channel'][1]['max'] = 60; $rejected->Update(); $rejected->clock += 120; $rejected->Update();
check(count($rejected->sent) === 1 && str_contains($rejected->GetNotificationError(),'abgewiesen'), 'Pushover-Ablehnung stoppt Wiederholung desselben ungültigen Auftrags auch nach der Sendepause');
$rejected->rejected = false; $rejected->RetryPushover(); $rejected->Update();
check(count($rejected->sent) === 2 && $rejected->GetNotificationError() === '', 'Explizites Zurücksetzen erlaubt nach behobenem Problem den nächsten regulären Versuch');

$failure2 = new NotificationTestDevice(false); $failure2->properties['PushoverEnabled'] = true; $failure2->properties['PushoverToken'] = str_repeat('T',30); $failure2->properties['PushoverUser'] = str_repeat('U',30); $failure2->failPush = true;
$failure2->deviceData['channel'][1]['max'] = 60;
$other = $failure2->deviceData['channel'][0]; $other['number'] = 3; $other['temp'] = 80; $other['max'] = 70; $failure2->deviceData['channel'][] = $other;
$failure2->Update(); $failure2->clock += 10; $failure2->Update();
check(count($failure2->sent) === 1, 'Netzfehler-Sendepause gilt auch für gleichzeitig wartende andere Fühler');
$onlyReached = new NotificationTestDevice(false); $onlyReached->properties['PushoverEnabled'] = true; $onlyReached->properties['PushoverToken'] = str_repeat('T',30); $onlyReached->properties['PushoverUser'] = str_repeat('U',30); $onlyReached->properties['PushoverNear'] = false;
$onlyReached->deviceData['channel'][1]['max'] = 70; $onlyReached->deviceData['channel'][1]['temp'] = 69; $onlyReached->Update();
check($onlyReached->sent === [], 'Vorwarnungsversand lässt sich separat abschalten');
$onlyReached->deviceData['channel'][1]['temp'] = 70; $onlyReached->clock += 10; $onlyReached->Update();
check(count($onlyReached->sent) === 1, 'Zielmeldung bleibt bei abgeschalteter Vorwarnung aktiv');
