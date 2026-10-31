<?php

declare(strict_types=1);

class VisibilityTestDevice extends TestDevice
{
    public int $requests = 0;
    protected function transport(string $path, ?array $payload = null): string { $this->requests++; return parent::transport($path,$payload); }
}
$visibility = new VisibilityTestDevice(false);
$wired = $visibility->deviceData['channel'][0]; $wired['number'] = 2; $wired['name'] = 'Steak';
$visibility->deviceData['channel'][] = $wired; $visibility->Update();
$v = end($visibility->visualizationMessages);
check(count($v['wiredChannels']) === 1 && $v['wiredChannels'][0]['number'] === 2 && $v['wiredChannels'][0]['visible'], 'Leiste enthält ausschließlich gültige kabelgebundene Fühler ohne separat angezeigten Kopfkanal');
$beforeRequests = $visibility->requests; $beforeWrites = count($visibility->writes);
$visibility->properties['AllowWrite'] = false; $visibility->properties['TileControls'] = false;
check(tileRequest($visibility, ['action'=>'visibility','number'=>2,'visible'=>false])['ok'] && $visibility->requests === $beforeRequests && count($visibility->writes) === $beforeWrites, 'Ausblenden funktioniert im Geräte-Schreibschutz ohne HTTP-Abruf oder Geräteänderung');
$v = end($visibility->visualizationMessages);
check(count($v['channels']) === 3 && $v['wiredChannels'][0]['visible'] === false && $visibility->status === 102, 'Verborgene Kachel entfällt aus Daten, ihr Button bleibt zum Einblenden erhalten');
$reloaded = new VisibilityTestDevice(false); $reloaded->deviceData = $visibility->deviceData; $reloaded->attributes['TileWiredVisibility'] = $visibility->attributes['TileWiredVisibility']; $reloaded->Update();
check(end($reloaded->visualizationMessages)['wiredChannels'][0]['visible'] === false, 'Gespeicherte Auswahl bleibt nach erneutem Laden der Modullaufzeit erhalten');
$visibility->properties['AllowWrite'] = true; $visibility->properties['TileControls'] = true;
check(!tileRequest($visibility, ['action'=>'channel','number'=>2,'max'=>130])['ok'] && count($visibility->writes) === $beforeWrites, 'Versteckter Fühler kann nicht über eine veraltete Kachelanfrage verstellt werden');
check(tileRequest($visibility, ['action'=>'visibility','number'=>2,'visible'=>true])['ok'] && count(end($visibility->visualizationMessages)['channels']) === 4, 'Wiedereinblenden stellt die ursprüngliche Kachel wieder her');
$beforeAttribute = $visibility->attributes['TileWiredVisibility'];
check(!tileRequest($visibility, ['action'=>'visibility','number'=>13,'visible'=>false])['ok'] && $visibility->attributes['TileWiredVisibility'] === $beforeAttribute, 'Bluetooth-Kanäle können durch die Drahtfühlerleiste nicht versteckt werden');
check(!tileRequest($visibility, ['action'=>'visibility','number'=>1,'visible'=>false])['ok'], 'Kopfkanal bleibt unabhängig von der unteren Fühlerleiste');
$visibility->properties['TileChannels'] = '[{"Number":2,"Enabled":false}]'; $visibility->Update();
check(end($visibility->visualizationMessages)['wiredChannels'] === [] && !tileRequest($visibility, ['action'=>'visibility','number'=>2,'visible'=>true])['ok'], 'Im Modul gesperrter Kanal wird durch die Leiste nicht wieder freigegeben');
$visibility->properties['TileChannels'] = '[]'; $visibility->Update();
check(!tileRequest($visibility, ['action'=>'visibility','number'=>2,'visible'=>'false'])['ok'], 'Ungültiger Sichtbarkeitstyp wird abgewiesen');
check(!tileRequest($visibility, ['action'=>'visibility','number'=>2,'visible'=>false,'max'=>120])['ok'], 'Ansichtsschalter nimmt keine zusätzlichen Geräteparameter an');
tileRequest($visibility, ['action'=>'visibility','number'=>2,'visible'=>false]);
$visibility->properties['Host'] = '192.0.2.21'; $visibility->Update();
check(end($visibility->visualizationMessages)['wiredChannels'][0]['visible'] === true, 'Auswahl eines anderen Geräts übernimmt nicht die alte Ausblendung');
$visibility->properties['Host'] = '192.0.2.20'; $visibility->properties['TileHeaderChannel'] = 2; $visibility->Update();
check(array_filter(end($visibility->visualizationMessages)['channels'], fn($channel) => $channel['number'] === 2) !== [], 'Als Kopftemperatur gewählter Fühler wird trotz früherer Ausblendung wieder angezeigt');
$pushHidden = new NotificationTestDevice(false); $pushHidden->properties['PushoverEnabled'] = true; $pushHidden->properties['PushoverToken'] = str_repeat('T',30); $pushHidden->properties['PushoverUser'] = str_repeat('U',30);
$pushHidden->deviceData['channel'][1]['temp'] = 30;
$wirePush = $pushHidden->deviceData['channel'][0]; $wirePush['number'] = 2; $wirePush['temp'] = 80; $wirePush['max'] = 90;
$pushHidden->deviceData['channel'][] = $wirePush; $pushHidden->Update();
tileRequest($pushHidden, ['action'=>'visibility','number'=>2,'visible'=>false]); $pushHidden->deviceData['channel'][3]['temp'] = 95; $pushHidden->Update();
check($pushHidden->sent === [], 'Nicht benötigter ausgeblendeter Drahtfühler erzeugt keine Pushover-Zielnachricht');
