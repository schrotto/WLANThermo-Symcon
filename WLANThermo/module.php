<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/Api.php';

class WLANThermo extends IPSModuleStrict
{
    public function Create(): void
    {
        parent::Create();
        $this->RegisterPropertyBoolean('Active', true);
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('Username', 'admin');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('Interval', 10);
        $this->RegisterPropertyInteger('Timeout', 5);
        $this->RegisterPropertyBoolean('AllowWrite', true);
        $this->RegisterPropertyBoolean('Bluetooth', true);
        $this->RegisterPropertyBoolean('ExposeRaw', false);
        $this->RegisterPropertyString('DisabledVariables', '[]');
        foreach (['ShowSystem', 'ShowSettings', 'ShowBluetoothDetails', 'ShowChannelDetails', 'ShowPitmaster', 'ShowDevice'] as $property) {
            $this->RegisterPropertyBoolean($property, true);
        }
        $this->RegisterAttributeString('VariableCatalog', '{}');
        $this->RegisterAttributeString('Dynamic', '[]');
        $this->RegisterAttributeString('LastHost', '');
        $this->RegisterTimer('Poll', 0, 'WLT_Update($_IPS["TARGET"]);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->profiles();
        $this->variable('Online', 'Gerät erreichbar', 0, '~Switch', 0, $this->stateValue('Online', false));
        $this->variable('LastUpdate', 'Letzter erfolgreicher Datenabruf', 1, '~UnixTimestamp', 1, $this->stateValue('LastUpdate', 0));
        $this->variable('LastError', 'Letzter Fehler', 3, '', 2, $this->stateValue('LastError', ''));
        $this->variable('BluetoothError', 'Bluetooth-Hinweis', 3, '', 3, $this->stateValue('BluetoothError', ''));
        $this->variable('Device', 'Geräteinformationen (JSON)', 3, '', 4, $this->stateValue('Device', ''));
        foreach (['RawData', 'RawSettings', 'RawBluetooth'] as $ident) {
            $this->variable($ident, $ident, 3, '', 9000, '');
        }
        // Include the 1.0 tracking list so deselection also works offline after upgrading.
        $known = array_unique(array_merge(
            json_decode($this->ReadAttributeString('Dynamic'), true),
            array_keys(json_decode($this->ReadAttributeString('VariableCatalog'), true)),
            ['BluetoothEnabled']
        ));
        foreach ($known as $ident) {
            if (!$this->selected($ident) && @$this->GetIDForIdent($ident)) { $this->UnregisterVariable($ident); }
        }
        $this->SetTimerInterval('Poll', 0);
        $this->invalidate();
        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SetStatus(104);
            return;
        }
        try {
            WLTApi::baseURL($this->ReadPropertyString('Host'));
            WLTApi::integer($this->ReadPropertyInteger('Interval'), 5, 3600);
            WLTApi::integer($this->ReadPropertyInteger('Timeout'), 1, 30);
        } catch (Throwable $e) {
            $this->safeSet('LastError', $e->getMessage());
            $this->SetStatus(201);
            return;
        }
        $host = $this->ReadPropertyString('Host');
        if ($this->ReadAttributeString('LastHost') !== $host) {
            $this->SetBuffer('SettingsTime', '0');
            $this->SetBuffer('Settings', '');
            $this->SetBuffer('BluetoothData', '');
            $this->WriteAttributeString('LastHost', $host);
        }
        $this->SetSummary($host);
        $this->SetTimerInterval('Poll', $this->ReadPropertyInteger('Interval') * 1000);
        $this->Update();
    }

    public function GetConfigurationForm(): string
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true, 64, JSON_THROW_ON_ERROR);
        $groups = [];
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        foreach ($catalog as $ident => $meta) {
            $group = $this->variableGroup($ident);
            $field = 'VAR_' . substr(sha1($ident), 0, 16);
            $groups[$group][] = ['type' => 'CheckBox', 'name' => $field,
                'caption' => $meta['name'] . ' [' . $ident . ']',
                'enabled' => $this->groupEnabled($ident),
                'value' => $this->selected($ident),
                'onChange' => 'WLT_SetVariableEnabled($id, ' . var_export($ident, true) . ', $' . $field . ');'];
        }
        ksort($groups, SORT_NATURAL);
        $panels = [];
        foreach ($groups as $group => $items) {
            $panels[] = ['type' => 'ExpansionPanel', 'caption' => $group, 'items' => $items];
        }
        $form['actions'][] = ['type' => 'ExpansionPanel', 'caption' => 'Einzelne Variablen auswählen',
            'items' => array_merge([['type' => 'Label', 'caption' => 'Haken ändern wirkt sofort. Abgewählte Variablen werden gelöscht; beim erneuten Anlegen erhalten sie neue IDs. Gruppen oben mit Übernehmen schalten.']], $panels)];
        $bt = $this->cached('BluetoothData');
        foreach ($bt['devices'] ?? [] as $device) {
            $address = (string) $device['address'];
            $items = [['type' => 'Label', 'caption' => $device['name'] . ' – ' . $address]];
            for ($i = 0; $i < min(32, (int) $device['count']); $i++) {
                $field = 'BT_' . substr(sha1($address), 0, 12) . '_' . $i;
                $items[] = ['type' => 'CheckBox', 'name' => $field, 'caption' => 'Sensorkanal ' . ($i + 1),
                    'value' => (((int) $device['selected'] & (1 << $i)) !== 0),
                    'onChange' => 'if (!WLT_SetBluetoothChannel($id, ' . var_export($address, true) . ', ' . $i . ', $' . $field . ')) { echo WLT_GetLastError($id); }'];
            }
            $form['actions'][] = ['type' => 'ExpansionPanel', 'caption' => 'Bluetooth: ' . $device['name'] . ' (' . $address . ')', 'items' => $items];
        }
        return WLTApi::encode($form);
    }

    public function SetVariableEnabled(string $Ident, bool $Enabled): void
    {
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        if (!isset($catalog[$Ident])) { throw new InvalidArgumentException('Unbekannte Variable.'); }
        $key = 'WLT_' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($key, 1000)) { throw new RuntimeException('Instanz beschäftigt.'); }
        try {
            $disabled = json_decode($this->ReadPropertyString('DisabledVariables'), true);
            $disabled = array_values(array_diff($disabled, [$Ident]));
            if (!$Enabled) { $disabled[] = $Ident; }
            IPS_SetProperty($this->InstanceID, 'DisabledVariables', WLTApi::encode($disabled));
        } finally {
            IPS_SemaphoreLeave($key);
        }
        IPS_ApplyChanges($this->InstanceID);
        $this->ReloadForm();
    }

    public function Update(): bool
    {
        return $this->locked(function (): void {
            $this->refresh(false);
        }, false);
    }

    public function RefreshBluetooth(): bool
    {
        $ok = $this->locked(function (): void {
            $this->refresh(true);
        }, false);
        $this->ReloadForm();
        return $ok;
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if ($Ident === 'BluetoothEnabled') {
            $ok = $this->SetBluetoothEnabled((bool) $Value);
        } elseif (preg_match('/^CH(\d+)_(Name|Min|Max|Alarm|Type|Color)$/', $Ident, $m)) {
            $fields = ['Name' => 'name', 'Min' => 'min', 'Max' => 'max', 'Alarm' => 'alarm', 'Type' => 'typ', 'Color' => 'color'];
            $ok = $this->SetChannel((int) $m[1], WLTApi::encode([$fields[$m[2]] => $Value]));
        } elseif (preg_match('/^PM(\d+)_(Set|Mode|Value|Channel|PID)$/', $Ident, $m)) {
            $fields = ['Set' => 'set', 'Mode' => 'typ', 'Value' => 'value', 'Channel' => 'channel', 'PID' => 'pid'];
            $ok = $this->SetPitmaster((int) $m[1], WLTApi::encode([$fields[$m[2]] => $Value]));
        } else {
            throw new InvalidArgumentException('Unbekannter oder nicht schreibbarer Ident.');
        }
        if (!$ok) {
            throw new RuntimeException($this->stateValue('LastError', '') ?: 'Instanz ist beschäftigt. Bitte erneut versuchen.');
        }
    }

    public function SetChannel(int $Number, string $Changes): bool
    {
        return $this->locked(function () use ($Number, $Changes): void {
            $data = $this->readData();
            $settings = $this->readSettings();
            $channel = WLTApi::item($data['channel'], 'number', $Number);
            $changes = $this->changes($Changes, ['name', 'typ', 'min', 'max', 'alarm', 'color']);
            foreach ($changes as $key => $v) {
                switch ($key) {
                    case 'name': WLTApi::text($v, 10); break;
                    case 'min': case 'max': $this->wholeDegrees($v, -30, 999); break;
                    case 'alarm': WLTApi::integer($v, 0, 3); break;
                    case 'color':
                        if (!is_string($v) || !preg_match('/^#[a-fA-F0-9]{6}$/', $v)) {
                            throw new InvalidArgumentException('Farbe muss #RRGGBB entsprechen.');
                        }
                        break;
                    case 'typ':
                        WLTApi::integer($v, 0, 255);
                        $sensor = WLTApi::item($settings['sensors'] ?? [], 'type', $v);
                        if (!empty($channel['fixed']) || !empty($sensor['fixed'])) {
                            throw new InvalidArgumentException('Dieser Fühlertyp wird vom WLANthermo fest zugewiesen.');
                        }
                }
            }
            $merged = array_replace($channel, $changes);
            if ((float) $merged['min'] > (float) $merged['max']) {
                throw new InvalidArgumentException('Minimum darf nicht über Maximum liegen.');
            }
            $this->post('/setchannels', ['number' => $Number] + $changes);
            WLTApi::verify(WLTApi::item($this->readData()['channel'], 'number', $Number), $changes);
            $this->refresh(false);
        });
    }

    public function SetPitmaster(int $ID, string $Changes): bool
    {
        return $this->locked(function () use ($ID, $Changes): void {
            $data = $this->readData();
            $settings = $this->readSettings();
            $changes = $this->changes($Changes, ['channel', 'pid', 'value', 'set', 'typ']);
            $current = WLTApi::item($data['pitmaster']['pm'] ?? [], 'id', $ID);
            $target = array_replace($current, $changes);
            WLTApi::integer($target['channel'], 1, 255);
            WLTApi::item($data['channel'], 'number', $target['channel']);
            WLTApi::integer($target['pid'], 0, 255);
            WLTApi::item($settings['pid'] ?? [], 'id', $target['pid']);
            WLTApi::integer($target['value'], 0, 100);
            if (isset($changes['set'])) { $this->wholeDegrees($changes['set'], 0, 999); }
            if (!in_array($target['typ'], ['off', 'manual', 'auto'], true)) {
                throw new InvalidArgumentException('Modus muss off, manual oder auto sein.');
            }
            if (isset($changes['value']) && $target['typ'] !== 'manual') {
                throw new InvalidArgumentException('Stellwert kann nur im Modus manual gesetzt werden.');
            }
            if (isset($changes['set']) && $target['typ'] !== 'auto') {
                throw new InvalidArgumentException('Sollwert kann nur im Modus auto gesetzt werden (ggf. zusammen mit typ ändern).');
            }
            // Firmware 06d456c uses array position rather than id for its PM pointer.
            // Preserve full, ordered list to avoid writing PM 1 into PM 0.
            $payload = [];
            foreach ($data['pitmaster']['pm'] as $pm) {
                if ((int) $pm['id'] === $ID) {
                    $pm = $target;
                }
                $payload[] = array_intersect_key($pm, array_flip(['id', 'channel', 'pid', 'value', 'set', 'typ']));
            }
            usort($payload, static fn(array $a, array $b): int => $a['id'] <=> $b['id']);
            foreach ($payload as $index => $pm) {
                if ($pm['id'] !== $index) {
                    throw new RuntimeException('Unerwartete Pitmaster-Reihenfolge; Schreiben abgebrochen.');
                }
            }
            $this->post('/setpitmaster', $payload);
            WLTApi::verify(WLTApi::item($this->readData()['pitmaster']['pm'], 'id', $ID), $changes);
            $this->refresh(false);
        });
    }

    public function SetBluetoothEnabled(bool $Enabled): bool
    {
        return $this->locked(function () use ($Enabled): void {
            $bt = $this->readBluetooth();
            $this->post('/setbluetooth', ['enabled' => $Enabled, 'devices' => $bt['devices']], true);
            WLTApi::verify($this->readBluetooth(), ['enabled' => $Enabled]);
            $this->refresh(true);
        });
    }

    /** ChannelIndex is zero-based as in the official selected bitmask. */
    public function SetBluetoothChannel(string $Address, int $ChannelIndex, bool $Enabled): bool
    {
        $ok = $this->locked(function () use ($Address, $ChannelIndex, $Enabled): void {
            $bt = $this->readBluetooth();
            $device = WLTApi::item($bt['devices'], 'address', strtolower($Address));
            WLTApi::integer($ChannelIndex, 0, min(31, (int) $device['count'] - 1));
            $mask = (int) $device['selected'];
            $mask = $Enabled ? $mask | (1 << $ChannelIndex) : $mask & ~(1 << $ChannelIndex);
            $this->writeBluetoothSelection($bt, $device, $mask);
        });
        $this->ReloadForm();
        return $ok;
    }

    public function SetBluetoothSelection(string $Address, int $Mask): bool
    {
        return $this->locked(function () use ($Address, $Mask): void {
            $bt = $this->readBluetooth();
            $device = WLTApi::item($bt['devices'], 'address', strtolower($Address));
            $count = WLTApi::integer((int) $device['count'], 1, 32);
            WLTApi::integer($Mask, 0, (1 << $count) - 1);
            $this->writeBluetoothSelection($bt, $device, $Mask);
        });
    }

    public function SetSystem(string $Changes): bool
    {
        return $this->locked(function () use ($Changes): void {
            $changes = $this->changes($Changes, ['ap', 'host', 'language', 'unit', 'autoupd', 'prerelease']);
            foreach ($changes as $k => $v) {
                if (in_array($k, ['ap', 'host'], true)) {
                    WLTApi::text($v, 13);
                } elseif (in_array($k, ['autoupd', 'prerelease'], true)) {
                    $this->boolean($v);
                } elseif (!in_array($v, $k === 'unit' ? ['C', 'F'] : ['de', 'en'], true)) {
                    throw new InvalidArgumentException('Ungültige Einheit/Sprache.');
                }
            }
            $this->post('/setsystem', $changes);
            WLTApi::verify($this->readSettings()['system'], $changes);
            $this->refresh(true);
        });
    }

    public function SetPID(int $ID, string $Changes): bool
    {
        return $this->locked(function () use ($ID, $Changes): void {
            $s = $this->readSettings();
            $old = WLTApi::item($s['pid'] ?? [], 'id', $ID);
            $changes = $this->changes($Changes, ['name', 'aktor', 'Kp', 'Ki', 'Kd', 'DCmmin', 'DCmmax', 'opl', 'SPmin', 'SPmax', 'link', 'tune', 'jp']);
            foreach ($changes as $k => $v) {
                if ($k === 'name') { WLTApi::text($v, 10); }
                elseif (in_array($k, ['opl', 'tune'], true)) { $this->boolean($v); }
                elseif ($k === 'aktor') { WLTApi::integer($v, 0, count($s['aktor'] ?? []) - 1); }
                elseif ($k === 'link') { WLTApi::integer($v, 0, 1); }
                else { WLTApi::finiteNumber($v, 0, in_array($k, ['Kp', 'Ki', 'Kd'], true) ? 1000000 : 100); }
            }
            $target = array_replace($old, $changes);
            foreach ([['DCmmin', 'DCmmax'], ['SPmin', 'SPmax']] as [$a, $b]) {
                if (isset($target[$a], $target[$b]) && $target[$a] > $target[$b]) {
                    throw new InvalidArgumentException($a . ' darf nicht größer als ' . $b . ' sein.');
                }
            }
            $this->post('/setpid', [['id' => $ID] + $changes]);
            WLTApi::verify(WLTApi::item($this->readSettings()['pid'], 'id', $ID), $changes);
            $this->refresh(true);
        });
    }

    public function SetIoT(string $Changes): bool
    {
        return $this->locked(function () use ($Changes): void {
            $changes = $this->changes($Changes, ['PMQhost', 'PMQport', 'PMQuser', 'PMQpass', 'PMQqos', 'PMQon', 'PMQint', 'CLon', 'CLtoken', 'CLint', 'CCLon', 'CCLurl', 'CCLint']);
            foreach ($changes as $k => $v) {
                if (in_array($k, ['PMQhost', 'PMQuser', 'PMQpass'], true)) {
                    WLTApi::text($v, 29);
                    if (strlen($v) > 29) { throw new InvalidArgumentException('MQTT-Text maximal 29 UTF-8-Bytes.'); }
                } elseif (in_array($k, ['CLtoken', 'CCLurl'], true)) { WLTApi::text($v, 512); }
                elseif (in_array($k, ['PMQon', 'CLon', 'CCLon'], true)) { $this->boolean($v); }
                elseif ($k === 'PMQqos') { WLTApi::integer($v, 0, 2); }
                elseif ($k === 'PMQport') { WLTApi::integer($v, 1, 65535); }
                else { WLTApi::integer($v, 1, 3600); }
            }
            $this->post('/setIoT', $changes);
            // Compare secrets only in local memory; never write them to a buffer.
            $settings = WLTApi::object($this->transport('/settings'));
            WLTApi::verify($settings['iot'] ?? [], $changes);
            unset($settings);
            $this->refresh(true);
        });
    }

    public function GetLastError(): string
    {
        return $this->stateValue('LastError', '');
    }

    public function GetSnapshot(): string
    {
        return WLTApi::encode(['data' => $this->cached('Data'), 'settings' => $this->cached('Settings'),
            'bluetooth' => $this->cached('BluetoothData'), 'online' => $this->stateValue('Online', false),
            'lastUpdate' => $this->stateValue('LastUpdate', 0), 'lastError' => $this->GetLastError()]);
    }

    protected function transport(string $path, ?array $payload = null): string
    {
        return WLTApi::request($this->ReadPropertyString('Host'), $path, $payload,
            $this->ReadPropertyString('Username'), $this->ReadPropertyString('Password'), $this->ReadPropertyInteger('Timeout'));
    }

    private function locked(callable $work, bool $write = true): bool
    {
        $key = 'WLT_' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($key, 1000)) {
            return false;
        }
        try {
            if (!$this->ReadPropertyBoolean('Active')) { throw new RuntimeException('Instanz ist deaktiviert.'); }
            if ($write && !$this->ReadPropertyBoolean('AllowWrite')) { throw new RuntimeException('Schreibzugriffe sind deaktiviert.'); }
            $work();
            $this->safeSet('LastError', '');
            return true;
        } catch (Throwable $e) {
            $this->safeSet('LastError', $e->getMessage());
            $this->SendDebug('Fehler', $e->getMessage(), 0);
            // Even failed readback after a write must not leave stale values valid.
            $this->invalidate();
            $this->SetStatus(202);
            return false;
        } finally {
            IPS_SemaphoreLeave($key);
        }
    }

    private function refresh(bool $force): void
    {
        $settings = $this->cached('Settings');
        if ($force || empty($settings) || time() - (int) $this->GetBuffer('SettingsTime') >= 300) {
            $settings = $this->readSettings();
        }
        $data = $this->readData();
        $this->SetBuffer('Data', WLTApi::encode($data));
        $this->publishData($data, $settings);
        $this->publishTree('SET', 'Einstellungen', $settings, 5000);
        $this->safeSet('Device', WLTApi::encode($settings['device'] ?? []));
        $this->safeSet('BluetoothError', '');
        if ($this->ReadPropertyBoolean('Bluetooth') && !empty($settings['features']['bluetooth'])) {
            try {
                $bt = $this->readBluetooth();
                $this->publishTree('BLE', 'Bluetooth', $bt, 7000);
                $this->variable('BluetoothEnabled', 'Bluetooth aktiviert', 0, '~Switch', 10, (bool) $bt['enabled']);
                $this->safeSet('BluetoothEnabled', (bool) $bt['enabled']);
                $this->action('BluetoothEnabled', true);
                if ($this->ReadPropertyBoolean('ExposeRaw')) { $this->safeSet('RawBluetooth', WLTApi::encode($bt)); }
            } catch (Throwable $e) {
                $this->SetBuffer('BluetoothData', '');
                $this->safeSet('BluetoothError', $e->getMessage());
                $this->staleBluetooth();
            }
        } else {
            $this->SetBuffer('BluetoothData', '');
            $this->staleBluetooth();
        }
        if ($this->ReadPropertyBoolean('ExposeRaw')) {
            $this->safeSet('RawData', WLTApi::encode($data));
            $this->safeSet('RawSettings', WLTApi::encode($settings));
        }
        $this->safeSet('Online', true);
        $this->safeSet('LastUpdate', time());
        $this->SetStatus(102);
    }

    private function readData(): array
    {
        $d = WLTApi::object($this->transport('/data'));
        if (!isset($d['system'], $d['channel']) || !is_array($d['system']) || !is_array($d['channel'])) {
            throw new RuntimeException('Ungültige /data-Struktur.');
        }
        $seen = [];
        foreach ($d['channel'] as $ch) {
            if (!is_array($ch) || !isset($ch['number'], $ch['temp'], $ch['name']) || !is_int($ch['number']) || $ch['number'] < 1 || isset($seen[$ch['number']])) {
                throw new RuntimeException('Ungültige oder doppelte Kanalnummer.');
            }
            $seen[$ch['number']] = true;
        }
        return $d;
    }

    private function readSettings(): array
    {
        $s = WLTApi::object($this->transport('/settings'));
        if (!isset($s['system'], $s['sensors']) || !is_array($s['system']) || !is_array($s['sensors'])) {
            throw new RuntimeException('Ungültige /settings-Struktur.');
        }
        // Keep only redacted settings in persistent Symcon buffers/variables.
        $s = WLTApi::redact($s);
        $this->SetBuffer('Settings', WLTApi::encode($s));
        $this->SetBuffer('SettingsTime', (string) time());
        return $s;
    }

    private function readBluetooth(): array
    {
        if (!$this->ReadPropertyBoolean('Bluetooth')) { throw new RuntimeException('Bluetooth-Abfrage ist deaktiviert.'); }
        $bt = WLTApi::object($this->transport('/getbluetooth'));
        if (!isset($bt['enabled'], $bt['devices']) || !is_bool($bt['enabled']) || !is_array($bt['devices'])) {
            throw new RuntimeException('Bluetooth-API wird von dieser Firmware nicht unterstützt.');
        }
        foreach ($bt['devices'] as &$dev) {
            if (!isset($dev['address'], $dev['name'], $dev['count'], $dev['selected']) || !preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/i', $dev['address'])) {
                throw new RuntimeException('Ungültiger Bluetooth-Geräteeintrag.');
            }
            $dev['address'] = strtolower($dev['address']);
            WLTApi::integer($dev['count'], 1, 32);
            WLTApi::integer($dev['selected'], 0, (1 << $dev['count']) - 1);
        }
        unset($dev);
        $this->SetBuffer('BluetoothData', WLTApi::encode($bt));
        return $bt;
    }

    private function writeBluetoothSelection(array $bt, array $device, int $mask): void
    {
        $this->post('/setbluetooth', ['enabled' => $bt['enabled'], 'devices' => [
            ['address' => $device['address'], 'count' => $device['count'], 'selected' => $mask]
        ]], true);
        WLTApi::verify(WLTApi::item($this->readBluetooth()['devices'], 'address', $device['address']), ['selected' => $mask]);
        $this->refresh(true);
    }

    private function post(string $path, array $payload, bool $bluetooth = false): void
    {
        $reply = trim($this->transport($path, $payload));
        // setBluetooth() in the examined firmware lacks a return statement.
        // Its HTTP boolean cannot be trusted; caller MUST verify by GET.
        if (!$bluetooth && !in_array(strtolower($reply), ['true', '1'], true)) {
            throw new RuntimeException('Gerät hat ' . $path . ' abgelehnt.');
        }
    }

    private function publishData(array $d, array $settings): void
    {
        $profile = ($d['system']['unit'] ?? 'C') === 'F' ? 'WLT.TemperatureF' : 'WLT.TemperatureC';
        $setProfile = ($d['system']['unit'] ?? 'C') === 'F' ? 'WLT.SetpointF' : 'WLT.SetpointC';
        $this->publishTree('SYS', 'System', $d['system'], 30);
        $present = [];
        foreach ($d['channel'] as $index => $ch) {
            $prefix = 'CH' . $ch['number'] . '_';
            $present[] = $prefix;
            $label = 'Kanal ' . $ch['number'] . ' · ' . $ch['name'];
            $position = 100 + $index * 20;
            $map = ['name' => ['Name', 'Name', 3, ''], 'temp' => ['Temperature', 'Temperatur', 2, $profile],
                'min' => ['Min', 'Alarm Minimum', 2, $setProfile], 'max' => ['Max', 'Alarm Maximum', 2, $setProfile],
                'alarm' => ['Alarm', 'Alarmierung', 1, 'WLT.Alarm'], 'typ' => ['Type', 'Fühlertyp', 1, ''],
                'color' => ['Color', 'Farbe', 3, ''], 'fixed' => ['Fixed', 'Fühlertyp fest', 0, '~Switch'],
                'connected' => ['Connected', 'Funkverbindung (API)', 0, '~Switch']];
            foreach ($map as $key => [$suffix, $caption, $type, $p]) {
                if (!array_key_exists($key, $ch)) { continue; }
                $this->variable($prefix . $suffix, $label . ': ' . $caption, $type, $p, $position++, $ch[$key]);
                if (in_array($suffix, ['Name', 'Min', 'Max', 'Alarm', 'Type', 'Color'], true)) {
                    $this->action($prefix . $suffix, $suffix !== 'Type' || empty($ch['fixed']));
                }
            }
            $wireless = (int) ($ch['typ'] ?? -1) === 16;
            foreach ($settings['sensors'] ?? [] as $sensor) {
                if ((int) $sensor['type'] === (int) ($ch['typ'] ?? -1) && stripos($sensor['name'], 'Bluetooth') !== false) { $wireless = true; }
            }
            $valid = is_numeric($ch['temp']) && is_finite((float) $ch['temp']) && (float) $ch['temp'] !== 999.0 && (!$wireless || !empty($ch['connected']));
            $this->variable($prefix . 'Valid', $label . ': Messwert gültig', 0, '~Switch', $position++, $valid);
            $this->variable($prefix . 'Present', $label . ': Kanal vorhanden', 0, '~Switch', $position++, true);
            $this->variable($prefix . 'OutOfRange', $label . ': Temperatur außerhalb Grenzen', 0, '~Switch', $position++,
                $valid && isset($ch['min'], $ch['max']) && ($ch['temp'] < $ch['min'] || $ch['temp'] > $ch['max']));
            $this->publishTree('EXTRA' . $ch['number'], $label . ': API', array_diff_key($ch, $map), $position);
        }
        foreach (json_decode($this->ReadAttributeString('Dynamic'), true) as $ident) {
            if (preg_match('/^(CH\d+_)/', $ident, $m) && !in_array($m[1], $present, true)) {
                if (preg_match('/_(Valid|Present|OutOfRange|Connected)$/', $ident)) { $this->safeSet($ident, false); }
                $this->disableIfExists($ident);
            }
        }
        $activePM = [];
        foreach ($d['pitmaster']['pm'] ?? [] as $index => $pm) {
            $prefix = 'PM' . $pm['id'] . '_';
            $activePM[] = $prefix;
            $map = ['set' => ['Set', 2, $setProfile], 'typ' => ['Mode', 3, 'WLT.Mode'],
                'value' => ['Value', 1, 'WLT.Percent'], 'channel' => ['Channel', 1, ''], 'pid' => ['PID', 1, '']];
            foreach ($map as $key => [$suffix, $type, $p]) {
                if (array_key_exists($key, $pm)) {
                    $id = $prefix . $suffix;
                    $this->variable($id, 'Pitmaster ' . $pm['id'] . ': ' . $key, $type, $p, 2000 + $index * 20, $pm[$key]);
                    $this->action($id, ($suffix !== 'Value' || $pm['typ'] === 'manual') && ($suffix !== 'Set' || $pm['typ'] === 'auto'));
                }
            }
            $this->publishTree('PMEXTRA' . $pm['id'], 'Pitmaster ' . $pm['id'], array_diff_key($pm, $map), 2100);
        }
        foreach (json_decode($this->ReadAttributeString('Dynamic'), true) as $ident) {
            if (preg_match('/^(PM\d+_)/', $ident, $m) && !in_array($m[1], $activePM, true)) { $this->disableIfExists($ident); }
        }
        $this->publishTree('DATA', 'Weitere Daten', array_diff_key($d, array_flip(['system', 'channel', 'pitmaster'])), 3000);
        $this->publishTree('PIT', 'Pitmaster API', array_diff_key($d['pitmaster'] ?? [], ['pm' => true]), 3100);
    }

    private function publishTree(string $prefix, string $label, array $tree, int $position, string $path = ''): void
    {
        foreach ($tree as $key => $v) {
            $p = $path . '/' . $key;
            if (is_array($v)) { $this->publishTree($prefix, $label, $v, $position, $p); }
            elseif ($v !== null) {
                $type = is_bool($v) ? 0 : (is_int($v) ? 1 : (is_float($v) ? 2 : 3));
                $this->variable($prefix . '_' . substr(sha1($p), 0, 16), $label . $p, $type, '', $position, $v);
            }
        }
    }

    private function variable(string $ident, string $name, int $type, string $profile, int $position, mixed $value): void
    {
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        $meta = compact('name', 'type', 'profile', 'position');
        if (($catalog[$ident] ?? null) !== $meta) {
            $catalog[$ident] = $meta;
            $this->WriteAttributeString('VariableCatalog', WLTApi::encode($catalog));
        }
        $keep = $this->selected($ident);
        $this->MaintainVariable($ident, $name, $type, $profile, $position, $keep);
        if (!$keep) { return; }
        $value = match ($type) { 0 => (bool) $value, 1 => (int) $value, 2 => (float) $value, default => (string) $value };
        $this->safeSet($ident, $value);
        $ids = json_decode($this->ReadAttributeString('Dynamic'), true);
        if (!in_array($ident, $ids, true)) {
            $ids[] = $ident;
            $this->WriteAttributeString('Dynamic', WLTApi::encode($ids));
        }
    }

    private function action(string $ident, bool $enabled): void
    {
        if (!@$this->GetIDForIdent($ident)) { return; }
        if ($enabled && $this->ReadPropertyBoolean('AllowWrite')) { $this->EnableAction($ident); }
        else { $this->disableIfExists($ident); }
    }

    private function staleBluetooth(): void
    {
        if (@$this->GetIDForIdent('BluetoothEnabled')) { $this->DisableAction('BluetoothEnabled'); }
    }

    private function invalidate(): void
    {
        $this->safeSet('Online', false);
        $this->staleBluetooth();
        foreach (json_decode($this->ReadAttributeString('Dynamic'), true) as $ident) {
            $this->disableIfExists($ident);
            if (preg_match('/^CH\d+_(Valid|OutOfRange|Connected)$/', $ident)) { $this->safeSet($ident, false); }
        }
    }

    private function changes(string $json, array $allowed): array
    {
        $changes = WLTApi::object($json);
        if (!$changes || array_diff(array_keys($changes), $allowed)) { throw new InvalidArgumentException('Leere Änderung oder unbekannter Parameter.'); }
        return $changes;
    }

    private function boolean(mixed $value): void
    {
        if (!is_bool($value)) { throw new InvalidArgumentException('Boolean true/false erforderlich.'); }
    }

    private function cached(string $key): array
    {
        $value = $this->GetBuffer($key);
        return $value === '' ? [] : json_decode($value, true, 64, JSON_THROW_ON_ERROR);
    }

    private function variableGroup(string $ident): string
    {
        if (preg_match('/^CH(\d+)_/', $ident, $m)) { return 'Kanal ' . $m[1]; }
        if (preg_match('/^(PM|PMEXTRA)(\d+)_/', $ident, $m)) { return 'Pitmaster ' . $m[2]; }
        if (str_starts_with($ident, 'SET_')) { return 'API-Einstellungen'; }
        if (str_starts_with($ident, 'SYS_')) { return 'API-Systemwerte'; }
        if (str_starts_with($ident, 'BLE_')) { return 'API-Bluetoothdetails'; }
        return 'Status und weitere Daten';
    }

    private function groupEnabled(string $ident): bool
    {
        $property = match (true) {
            str_starts_with($ident, 'Raw') => 'ExposeRaw',
            $ident === 'Device' => 'ShowDevice',
            str_starts_with($ident, 'SET_') => 'ShowSettings',
            str_starts_with($ident, 'SYS_') => 'ShowSystem',
            str_starts_with($ident, 'BLE_') => 'ShowBluetoothDetails',
            preg_match('/^(PM\d+_|PMEXTRA|PIT_)/', $ident) === 1 => 'ShowPitmaster',
            preg_match('/^(CH\d+_(Name|Type|Color|Fixed|Connected)$|EXTRA|DATA_)/', $ident) === 1 => 'ShowChannelDetails',
            default => ''
        };
        return $property === '' || $this->ReadPropertyBoolean($property);
    }

    private function selected(string $ident): bool
    {
        return $this->groupEnabled($ident) && !in_array($ident, json_decode($this->ReadPropertyString('DisabledVariables'), true), true);
    }

    private function safeSet(string $ident, mixed $value): void
    {
        if (in_array($ident, ['Online', 'LastUpdate', 'LastError', 'BluetoothError', 'Device'], true)) {
            $this->SetBuffer('State_' . $ident, json_encode($value, JSON_THROW_ON_ERROR));
        }
        if (@$this->GetIDForIdent($ident)) { $this->SetValue($ident, $value); }
    }

    private function stateValue(string $ident, mixed $default): mixed
    {
        $state = $this->GetBuffer('State_' . $ident);
        if ($state !== '') { return json_decode($state, true, 64, JSON_THROW_ON_ERROR); }
        return @$this->GetIDForIdent($ident) ? $this->GetValue($ident) : $default;
    }

    private function disableIfExists(string $ident): void
    {
        if (@$this->GetIDForIdent($ident)) { $this->DisableAction($ident); }
    }

    private function wholeDegrees(mixed $value, int $min, int $max): void
    {
        $number = WLTApi::finiteNumber($value, $min, $max);
        if (floor($number) !== $number) { throw new InvalidArgumentException('Temperatureinstellungen nur in ganzen Grad eingeben.'); }
    }

    private function profiles(): void
    {
        foreach (['WLT.TemperatureC' => ' °C', 'WLT.TemperatureF' => ' °F'] as $name => $suffix) {
            if (!IPS_VariableProfileExists($name)) {
                IPS_CreateVariableProfile($name, 2);
                IPS_SetVariableProfileDigits($name, 1);
                IPS_SetVariableProfileValues($name, -30, 999.9, 0.1);
                IPS_SetVariableProfileText($name, '', $suffix);
            }
        }
        foreach (['WLT.SetpointC' => ' °C', 'WLT.SetpointF' => ' °F'] as $name => $suffix) {
            if (!IPS_VariableProfileExists($name)) { IPS_CreateVariableProfile($name, 2); }
            IPS_SetVariableProfileDigits($name, 0);
            IPS_SetVariableProfileValues($name, -30, 999, 1);
            IPS_SetVariableProfileText($name, '', $suffix);
        }
        if (!IPS_VariableProfileExists('WLT.Alarm')) {
            IPS_CreateVariableProfile('WLT.Alarm', 1);
            foreach (['Aus', 'Push', 'Summer', 'Push + Summer'] as $value => $label) {
                IPS_SetVariableProfileAssociation('WLT.Alarm', $value, $label, '', -1);
            }
        }
        if (!IPS_VariableProfileExists('WLT.Mode')) {
            IPS_CreateVariableProfile('WLT.Mode', 3);
            foreach (['off' => 'Aus', 'manual' => 'Manuell', 'auto' => 'Automatisch'] as $value => $label) {
                IPS_SetVariableProfileAssociation('WLT.Mode', $value, $label, '', -1);
            }
        }
        if (!IPS_VariableProfileExists('WLT.Percent')) {
            IPS_CreateVariableProfile('WLT.Percent', 1);
            IPS_SetVariableProfileValues('WLT.Percent', 0, 100, 1);
            IPS_SetVariableProfileText('WLT.Percent', '', ' %');
        }
    }
}
