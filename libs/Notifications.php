<?php

declare(strict_types=1);

final class WLTPushoverRejected extends RuntimeException {}

trait WLTNotifications
{
    private function registerNotifications(): void
    {
        $this->RegisterPropertyBoolean('TargetWarningEnabled', true);
        $this->RegisterPropertyBoolean('TargetWarningBlink', false);
        $this->RegisterPropertyInteger('TargetWarningLeadTenths', 10);
        $this->RegisterPropertyBoolean('PushoverEnabled', false);
        $this->RegisterPropertyString('PushoverToken', '');
        $this->RegisterPropertyString('PushoverUser', '');
        $this->RegisterPropertyString('PushoverDevice', '');
        $this->RegisterPropertyBoolean('PushoverNear', true);
        $this->RegisterPropertyBoolean('PushoverReached', true);
        $this->RegisterAttributeString('TargetNotificationState', '{}');
        $this->RegisterAttributeString('PushoverBlocked', '');
        $this->RegisterAttributeString('PushoverRetryAfter', '0');
    }

    private function validateNotifications(): void
    {
        $this->tileChannelSelection();
        $this->tileChannelNames();
        if (!in_array($this->ReadPropertyInteger('TargetWarningLeadTenths'), [0,5,10,20], true)) { throw new InvalidArgumentException('Ungültiger Temperaturvorlauf.'); }
        if ($this->ReadPropertyBoolean('PushoverEnabled')) {
            foreach (['PushoverToken','PushoverUser'] as $property) {
                if (!preg_match('/^[A-Za-z0-9]{30}$/D', $this->ReadPropertyString($property))) { throw new InvalidArgumentException('Pushover: gültigen App-Token und User-/Gruppenschlüssel eintragen.'); }
            }
            $device = $this->ReadPropertyString('PushoverDevice');
            if ($device !== '' && !preg_match('/^[A-Za-z0-9_-]{1,25}$/D', $device)) { throw new InvalidArgumentException('Pushover: ungültiger Gerätename.'); }
        }
        foreach (json_decode($this->ReadPropertyString('TileChannels'), true, 64, JSON_THROW_ON_ERROR) as $row) {
            if (array_key_exists('Notify', $row) && !is_bool($row['Notify'])) { throw new InvalidArgumentException('Kanalauswahl für Meldungen muss ein Schalter sein.'); }
        }
    }

    /** Ausschließlich aktuelle, freigegebene Kern-/Einzelfühler; Kopf- und MEATER-Garraum ausgeschlossen. */
    private function targetAlertLevel(array $channel, array $data, array $settings): string
    {
        $number = $channel['number'];
        if (!$this->channelHasValidTemperature($channel, $settings) || !($this->tileChannelSelection()[$number] ?? true) ||
            ($this->ReadPropertyBoolean('TileHeaderEnabled') && $number === $this->ReadPropertyInteger('TileHeaderChannel')) ||
            ($this->meaterMetadata($data, $settings)[$number]['sensorRole'] ?? '') === 'ambient' ||
            !isset($channel['max']) || !is_numeric($channel['max']) || !is_finite((float) $channel['max'])) { return ''; }
        $lead = $this->ReadPropertyInteger('TargetWarningLeadTenths') / 10 * (($data['system']['unit'] ?? 'C') === 'F' ? 1.8 : 1);
        if ($channel['temp'] >= $channel['max']) { return 'reached'; }
        return $lead > 0 && $channel['temp'] >= $channel['max'] - $lead ? 'near' : '';
    }

    protected function notificationTime(): int { return time(); }

    public function GetNotificationError(): string { return $this->GetBuffer('PushoverError'); }

    public function RetryPushover(): bool
    {
        return $this->locked(function (): void {
            $this->WriteAttributeString('PushoverBlocked', '');
            $this->WriteAttributeString('PushoverRetryAfter', '0');
            $state = json_decode($this->ReadAttributeString('TargetNotificationState'), true) ?: [];
            foreach ($state as &$entry) { $entry['retryAfter'] = 0; }
            unset($entry);
            $this->WriteAttributeString('TargetNotificationState', WLTApi::encode($state));
            $this->SetBuffer('PushoverError', '');
        }, false);
    }

    private function processTargetNotifications(array $data, array $settings): void
    {
        if (!$this->ReadPropertyBoolean('PushoverEnabled')) { $this->SetBuffer('PushoverError', ''); return; }
        try { $this->validateNotifications(); }
        catch (Throwable $e) { $this->SetBuffer('PushoverError', $e->getMessage()); return; }
        $configKey = hash('sha256', WLTApi::encode([$this->ReadPropertyString('PushoverToken'),$this->ReadPropertyString('PushoverUser'),$this->ReadPropertyString('PushoverDevice'),$this->ReadPropertyString('TileTitle'),$this->ReadPropertyString('TileChannels')]));
        if ($this->ReadAttributeString('PushoverBlocked') === $configKey) { return; }
        $retryAfter = (int) $this->ReadAttributeString('PushoverRetryAfter');
        if ($this->notificationTime() < $retryAfter && $retryAfter - $this->notificationTime() <= 60) { return; }
        $state = json_decode($this->ReadAttributeString('TargetNotificationState'), true) ?: [];
        $rows = json_decode($this->ReadPropertyString('TileChannels'), true) ?: [];
        $notify = array_column($rows, 'Notify', 'Number');
        $names = $this->tileChannelNames();
        $now = $this->notificationTime(); $attempted = false;
        $scale = ($data['system']['unit'] ?? 'C') === 'F' ? 1.8 : 1;
        foreach ($data['channel'] ?? [] as $channel) {
            $number = $channel['number'];
            if (!($notify[$number] ?? true) || !$this->channelHasValidTemperature($channel, $settings) || !isset($channel['max']) || !is_numeric($channel['max']) || !is_finite((float) $channel['max'])) { continue; }
            $level = $this->targetAlertLevel($channel, $data, $settings);
            $fingerprint = hash('sha256', WLTApi::encode([$this->presetSource($channel),$data['system']['unit'] ?? 'C',isset($channel['max']) ? (float) $channel['max'] : null,
                $this->ReadPropertyInteger('TargetWarningLeadTenths'),$this->ReadPropertyString('PushoverToken'),$this->ReadPropertyString('PushoverUser'),$this->ReadPropertyString('PushoverDevice')]));
            $entry = $state[$number] ?? [];
            if (($entry['source'] ?? '') !== $fingerprint) { $entry = ['source'=>$fingerprint,'near'=>false,'reached'=>false,'lastAttempt'=>0,'retryAfter'=>0,'coolSince'=>0,'lastSeen'=>$now]; }
            if ($now < ($entry['lastSeen'] ?? 0)) { $entry['retryAfter'] = 0; }
            if ($now < ($entry['lastSeen'] ?? 0) || $now - ($entry['lastSeen'] ?? 0) > max(120, $this->ReadPropertyInteger('Interval') * 3)) { $entry['coolSince'] = 0; }
            $entry['lastSeen'] = $now;
            $coolLimit = ($channel['max'] ?? -9999) - ($this->ReadPropertyInteger('TargetWarningLeadTenths') / 10 + 2) * $scale;
            if ($channel['temp'] < $coolLimit) {
                if (!$entry['coolSince']) { $entry['coolSince'] = $now; }
                if ($now - $entry['coolSince'] >= 300) { $entry['near'] = $entry['reached'] = false; }
            } else { $entry['coolSince'] = 0; }
            $kind = $level === 'reached' && $this->ReadPropertyBoolean('PushoverReached') && !$entry['reached'] ? 'reached' :
                ($level === 'near' && $this->ReadPropertyBoolean('PushoverNear') && !$entry['near'] && !$entry['reached'] ? 'near' : '');
            $state[$number] = $entry;
            if ($kind === '' || $attempted || $now < ($entry['retryAfter'] ?? 0)) { continue; }
            $attempted = true; $state[$number]['lastAttempt'] = $now; $state[$number]['retryAfter'] = $now + 60;
            // Vor dem Netzaufruf speichern: auch nach Abbruch gilt die Sendepause.
            $this->WriteAttributeString('TargetNotificationState', WLTApi::encode($state));
            $unit = ($data['system']['unit'] ?? 'C') === 'F' ? '°F' : '°C';
            $format = static fn($value): string => number_format((float) $value, 1, ',', '');
            $name = $names[$number] ?? $channel['name'];
            $payload = ['token'=>$this->ReadPropertyString('PushoverToken'),'user'=>$this->ReadPropertyString('PushoverUser'),
                'title'=>$this->ReadPropertyString('TileTitle') ?: 'WLANThermo',
                'message'=>$name . ($kind === 'near' ? ': Kerntemperatur fast erreicht. ' : ': Kerntemperatur erreicht. ') .
                    'Ist ' . $format($channel['temp']) . ' ' . $unit . ', Ziel ' . $format($channel['max']) . ' ' . $unit . '.', 'priority'=>0];
            if ($this->ReadPropertyString('PushoverDevice') !== '') { $payload['device'] = $this->ReadPropertyString('PushoverDevice'); }
            try {
                $reply = $this->pushoverTransport($payload);
                if (($reply['status'] ?? 0) !== 1) { throw new WLTPushoverRejected('Pushover hat die Nachricht abgewiesen.'); }
                $state[$number][$kind] = true; $state[$number]['retryAfter'] = 0;
                if ($kind === 'reached') { $state[$number]['near'] = true; }
                $this->SetBuffer('PushoverError', '');
                $this->WriteAttributeString('PushoverRetryAfter', '0');
            } catch (WLTPushoverRejected $e) {
                $this->WriteAttributeString('PushoverBlocked', $configKey);
                $this->SetBuffer('PushoverError', 'Pushover hat den Versand abgewiesen. Schlüssel, Empfänger oder Kontingent prüfen; danach Pushover-Fehler zurücksetzen.');
            } catch (Throwable $e) {
                $this->WriteAttributeString('PushoverRetryAfter', (string) ($now + 60));
                $this->SetBuffer('PushoverError', 'Pushover-Versand fehlgeschlagen; erneuter Versuch frühestens nach 60 Sekunden.');
            }
        }
        $this->WriteAttributeString('TargetNotificationState', WLTApi::encode($state));
    }

    protected function pushoverTransport(array $payload): array
    {
        if (!extension_loaded('curl')) { throw new RuntimeException('Pushover benötigt PHP-cURL.'); }
        $curl = curl_init('https://api.pushover.net/1/messages.json');
        curl_setopt_array($curl, [CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($payload),CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>5,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
        $body = curl_exec($curl); $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        if ($code >= 400 && $code < 500) { throw new WLTPushoverRejected('Pushover hat den Versand abgewiesen.'); }
        if ($body === false || $code < 200 || $code >= 300) { throw new RuntimeException('Pushover-Übertragung fehlgeschlagen.'); }
        return json_decode($body, true, 32, JSON_THROW_ON_ERROR);
    }
}
