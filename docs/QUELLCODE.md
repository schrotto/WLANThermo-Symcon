# Vollständiger Quellcode – WLANThermo 1.11.0

Vollständige Laufzeit- und Konfigurationsdateien. Tests und weitere Dokumentation liegen zusätzlich im Paket.

## library.json

````json
{
    "id": "{7FB2B880-36E5-4418-934F-FFEB7D430935}",
    "author": "schrotto",
    "name": "WLANThermo",
    "url": "https://github.com/schrotto/WLANThermo-Symcon",
    "compatibility": {
        "version": "9.0"
    },
    "version": "1.11.0",
    "build": 22,
    "date": 1793404800
}
````

## WLANThermo/module.json

````json
{
    "id": "{E9649ECA-D3A7-4B80-A4AF-9564F243B767}",
    "name": "WLANThermo",
    "type": 3,
    "vendor": "WLANThermo",
    "aliases": [
        "WLANThermo Mini V3",
        "WLANThermo MEATER Bridge"
    ],
    "url": "https://github.com/schrotto/WLANThermo-Symcon#readme",
    "parentRequirements": [],
    "childRequirements": [],
    "implemented": [],
    "prefix": "WLT"
}
````

## WLANThermo/module.php

````php
<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/Api.php';
require_once __DIR__ . '/../libs/VariableManagement.php';
require_once __DIR__ . '/../libs/Visualization.php';
require_once __DIR__ . '/../libs/Forecast.php';
require_once __DIR__ . '/../libs/Notifications.php';

class WLANThermo extends IPSModuleStrict
{
    use WLTVariableManagement;
    use WLTVisualization;
    use WLTForecastSupport;
    use WLTNotifications;
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
        // Read-only migration fallback for configurations saved by version 1.1.0.
        $this->RegisterPropertyString('DisabledVariables', '[]');
        $this->RegisterPropertyString('VariableSelection', '[]');
        $this->RegisterPropertyString('VariableMode', 'automatic');
        $this->RegisterAttributeString('VariableDefaults', '');
        $this->registerVisualization();
        $this->registerNotifications();
        foreach (['ShowSystem', 'ShowSettings', 'ShowBluetoothDetails', 'ShowChannelDetails', 'ShowPitmaster', 'ShowDevice'] as $property) {
            $this->RegisterPropertyBoolean($property, true);
        }
        $this->RegisterAttributeString('VariableCatalog', '{}');
        $this->RegisterAttributeString('Dynamic', '[]');
        $this->RegisterAttributeString('HiddenVariables', '{}');
        $this->RegisterAttributeString('AppliedVariableOptions', '{}');
        $this->RegisterAttributeString('LastHost', '');
        $this->RegisterTimer('Poll', 0, 'WLT_Update($_IPS["TARGET"]);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->SetTimerInterval('Poll', 0);
        if ($this->ReadAttributeString('VariableDefaults') === '') {
            $legacy = $this->ReadAttributeString('VariableCatalog') !== '{}' || $this->ReadAttributeString('Dynamic') !== '[]';
            $this->WriteAttributeString('VariableDefaults', $legacy ? 'all' : 'none');
        }
        $this->SetVisualizationType($this->ReadPropertyBoolean('TileEnabled') ?
            ($this->ReadPropertyBoolean('TileFullscreen') && defined('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN') ? INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN : INSTANCE_VISUALIZATION_TYPE_HTML) : INSTANCE_VISUALIZATION_TYPE_NONE);
        try {
            $this->validateVisualization();
            $this->validateNotifications();
            $this->selectionOverrides();
            $this->synchronizeReferences();
        } catch (Throwable $e) {
            $this->SetBuffer('State_Online', 'false');
            $this->SetBuffer('State_LastError', json_encode($e->getMessage(), JSON_THROW_ON_ERROR));
            $this->invalidateActions();
            $this->SetStatus(201);
            $this->pushVisualization();
            return;
        }
        $this->profiles();
        $this->variable('Online', 'Gerät erreichbar', 0, '~Switch', 0, $this->stateValue('Online', false));
        $this->variable('LastUpdate', 'Letzter erfolgreicher Datenabruf', 1, '~UnixTimestamp', 1, $this->stateValue('LastUpdate', 0));
        $this->variable('LastError', 'Letzter Fehler', 3, '', 2, $this->stateValue('LastError', ''));
        $this->variable('BluetoothError', 'Bluetooth-Hinweis', 3, '', 3, $this->stateValue('BluetoothError', ''));
        $this->variable('Device', 'Geräteinformationen (JSON)', 3, '', 4, $this->stateValue('Device', ''));
        foreach (['RawData', 'RawSettings', 'RawBluetooth'] as $ident) {
            $this->variable($ident, $ident, 3, '', 9000, $this->stateValue($ident, ''));
        }
        // Include the 1.0 tracking list so deselection also works offline after upgrading.
        $known = array_unique(array_merge(
            json_decode($this->ReadAttributeString('Dynamic'), true),
            array_keys(json_decode($this->ReadAttributeString('VariableCatalog'), true)),
            ['BluetoothEnabled']
        ));
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        foreach ($known as $ident) {
            if (!$this->selected($ident)) { $this->retainDisabled($ident); }
            elseif (isset($catalog[$ident]) && @$this->GetIDForIdent($ident)) {
                $meta = $catalog[$ident];
                $this->configureVariable($ident, $meta['name'], $meta['type'], $meta['profile'], $meta['position']);
            }
        }
        $this->SetTimerInterval('Poll', 0);
        $this->invalidate();
        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SetStatus(104);
            $this->pushVisualization();
            return;
        }
        try {
            WLTApi::baseURL($this->ReadPropertyString('Host'));
            WLTApi::integer($this->ReadPropertyInteger('Interval'), 5, 3600);
            WLTApi::integer($this->ReadPropertyInteger('Timeout'), 1, 30);
        } catch (Throwable $e) {
            $this->safeSet('LastError', $e->getMessage());
            $this->SetStatus(201);
            $this->pushVisualization();
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
        try {
            $rows = $this->selectionRows();
        } catch (Throwable $e) {
            $rows = [];
            $form['actions'][] = ['type' => 'Label', 'caption' => 'Variablenauswahl prüfen: ' . $e->getMessage()];
        }
        $this->populateForm($form['elements'], $rows);
        try { $counts = $this->variableCounts(); } catch (Throwable $e) { $counts = 'Variablenkonfiguration prüfen: ' . $e->getMessage(); }
        $this->populatePremiumForm($form['elements'], $counts);
        $form['actions'][0]['items'][0]['caption'] = 'Letzter Hinweis: ' . ($this->GetLastError() ?: 'Kein Fehler gemeldet.');
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

    private function populateForm(array &$elements, array $rows): void
    {
        foreach ($elements as &$element) {
            if (($element['name'] ?? '') === 'VariableSelection') { $element['values'] = $rows; }
            if (isset($element['items'])) { $this->populateForm($element['items'], $rows); }
        }
        unset($element);
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
        if ($Ident === 'TileAction') { $this->visualizationAction($Value); return; }
        if (!$this->selected($Ident)) {
            throw new InvalidArgumentException('Diese Variable ist deaktiviert.');
        }
        if ($Ident === 'BluetoothEnabled') {
            $this->boolean($Value);
            $ok = $this->SetBluetoothEnabled($Value);
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
            $this->changeChannel($Number, $Changes, false);
        });
    }

    /** Nur die Visu darf eine kollidierende Untergrenze automatisch mit anpassen. */
    private function setVisualizationUpperLimit(int $number, mixed $maximum): array
    {
        $adjusted = false;
        $ok = $this->locked(function () use ($number, $maximum, &$adjusted): void {
            $adjusted = $this->changeChannel($number, WLTApi::encode(['max' => $maximum]), true);
        });
        return [$ok, $adjusted];
    }

    private function changeChannel(int $Number, string $Changes, bool $adjustMinimum): bool
    {
        $data = $this->readData();
        $settings = $this->readSettings();
        $channel = WLTApi::item($data['channel'], 'number', $Number);
        if ($adjustMinimum && ($this->meaterMetadata($data, $settings)[$Number]['sensorRole'] ?? '') === 'ambient') {
            throw new InvalidArgumentException('Die MEATER-Garraumtemperatur ist in der Kachel eine reine Messwertanzeige.');
        }
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
        $adjusted = false;
        if ($adjustMinimum && array_key_exists('max', $changes) && !array_key_exists('min', $changes)) {
            if (!isset($channel['min']) || !is_numeric($channel['min'])) {
                throw new RuntimeException('Gerät liefert keine gültige Untergrenze.');
            }
            if ((float) $channel['min'] > (float) $changes['max']) {
                // Gleiche Einheit wie am Gerät; am unteren API-Limit ist Gleichheit zulässig.
                $changes['min'] = max(-30, $changes['max'] - 1);
                $adjusted = true;
            }
        }
        $merged = array_replace($channel, $changes);
        if ((float) $merged['min'] > (float) $merged['max']) {
            throw new InvalidArgumentException('Minimum darf nicht über Maximum liegen.');
        }
        $this->post('/setchannels', ['number' => $Number] + $changes);
        WLTApi::verify(WLTApi::item($this->readData()['channel'], 'number', $Number), $changes);
        $this->refresh(false);
        return $adjusted;
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
            $this->selectionOverrides();
            if (!$this->ReadPropertyBoolean('Active')) { throw new RuntimeException('Instanz ist deaktiviert.'); }
            if ($write && !$this->ReadPropertyBoolean('AllowWrite')) { throw new RuntimeException('Schreibzugriffe sind deaktiviert.'); }
            $work();
            $this->safeSet('LastError', '');
            return true;
        } catch (Throwable $e) {
            $this->safeSet('LastError', $e->getMessage());
            $this->SendDebug($e instanceof WLTUnavailableException ? 'Erreichbarkeit' : 'Fehler', $e->getMessage(), 0);
            // Even failed readback after a write must not leave stale values valid.
            $this->invalidate();
            // Keep polling while the grill thermometer is switched off.
            // Standby (106) is a regular Symcon status, not an instance fault.
            $this->SetStatus($e instanceof WLTUnavailableException ? 106 : 202);
            return false;
        } finally {
            IPS_SemaphoreLeave($key);
            $this->pushVisualization();
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
        $this->recordForecast($data, $settings);
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
        $this->processTargetNotifications($data, $settings);
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
            $valid = $this->channelHasValidTemperature($ch, $settings);
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
        if (!$this->selected($ident)) { $this->retainDisabled($ident); return; }
        $this->configureVariable($ident, $name, $type, $profile, $position);
        $value = match ($type) { 0 => (bool) $value, 1 => (int) $value, 2 => (float) $value, default => (string) $value };
        $this->safeSet($ident, $value);
        $ids = json_decode($this->ReadAttributeString('Dynamic'), true);
        if (!in_array($ident, $ids, true)) {
            $ids[] = $ident;
            $this->WriteAttributeString('Dynamic', WLTApi::encode($ids));
        }
    }

    private function configureVariable(string $ident, string $name, int $type, string $profile, int $position): void
    {
        $options = $this->variableOptions($ident);
        if ($options['TargetID'] > 0) { $this->retainDisabled($ident); return; }
        $this->MaintainVariable($ident, $options['CustomName'] ?: $name, $type,
            $this->presentation($ident, $type, $profile, $options['Presentation']),
            $options['Position'] >= 0 ? $options['Position'] : $position, true);
        $id = $this->GetIDForIdent($ident);
        // Explicit table settings also apply to existing variables. Empty/-1 preserves manual edits.
        $applied = json_decode($this->ReadAttributeString('AppliedVariableOptions'), true);
        $previous = $applied[$ident] ?? [];
        if ($options['CustomName'] !== '' && ($previous['CustomName'] ?? '') !== $options['CustomName']) {
            IPS_SetName($id, $options['CustomName']);
        }
        if ($options['Position'] >= 0 && ($previous['Position'] ?? -1) !== $options['Position']) {
            IPS_SetPosition($id, $options['Position']);
        }
        if ($previous !== $options) {
            $applied[$ident] = $options;
            $this->WriteAttributeString('AppliedVariableOptions', WLTApi::encode($applied));
        }
        $hidden = json_decode($this->ReadAttributeString('HiddenVariables'), true);
        if (array_key_exists($ident, $hidden)) {
            IPS_SetHidden($id, $hidden[$ident]);
            unset($hidden[$ident]);
            $this->WriteAttributeString('HiddenVariables', WLTApi::encode($hidden));
        }
    }

    private function action(string $ident, bool $enabled): void
    {
        if (!@$this->GetIDForIdent($ident)) { return; }
        if ($this->selected($ident) && $this->variableOptions($ident)['TargetID'] === 0 && $enabled && $this->ReadPropertyBoolean('AllowWrite')) { $this->EnableAction($ident); }
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
        return $this->groupEnabled($ident) && ($this->selectionOverrides()[$ident] ?? $this->defaultVariableEnabled($ident));
    }

    private function selectionOverrides(): array
    {
        if (!in_array($this->ReadPropertyString('VariableMode'), ['automatic', 'none', 'recommended', 'all'], true)) {
            throw new InvalidArgumentException('Ungültige Standardauswahl für Variablen.');
        }
        $legacy = json_decode($this->ReadPropertyString('DisabledVariables'), true, 64, JSON_THROW_ON_ERROR);
        $rows = json_decode($this->ReadPropertyString('VariableSelection'), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($legacy) || !array_is_list($legacy) || !is_array($rows) || !array_is_list($rows)) {
            throw new InvalidArgumentException('Variablenauswahl muss eine JSON-Liste sein.');
        }
        $result = [];
        foreach ($legacy as $ident) {
            if (!is_string($ident) || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $ident)) {
                throw new InvalidArgumentException('Ungültiger Variablen-Ident in bisheriger Auswahl.');
            }
            $result[$ident] = false;
        }
        $seen = [];
        $targets = [];
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['Ident']) || !array_key_exists('Enabled', $row) ||
                !is_string($row['Ident']) || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $row['Ident']) || !is_bool($row['Enabled'])) {
                throw new InvalidArgumentException('Ungültiger Eintrag in der Variablenauswahl.');
            }
            if (isset($seen[$row['Ident']])) { throw new InvalidArgumentException('Doppelter Ident in der Variablenauswahl.'); }
            if (isset($row['CustomName']) && (!is_string($row['CustomName']) ||
                preg_match('/[\x00-\x1f]/', $row['CustomName']) || strlen($row['CustomName']) > 200)) {
                throw new InvalidArgumentException('Variablenname muss Text mit höchstens 200 Bytes sein.');
            }
            if (isset($row['Position']) && (!is_int($row['Position']) || $row['Position'] < -1 || $row['Position'] > 1000000)) {
                throw new InvalidArgumentException('Position muss zwischen -1 und 1000000 liegen.');
            }
            if (isset($row['Presentation']) && !in_array($row['Presentation'], ['auto', 'value', 'legacy'], true)) {
                throw new InvalidArgumentException('Unbekannte Variablendarstellung.');
            }
            $target = $row['TargetID'] ?? 0;
            if (!is_int($target) || $target < 0) { throw new InvalidArgumentException('Ungültige Zielvariablen-ID.'); }
            if ($target > 0 && $row['Enabled'] && $this->groupEnabled($row['Ident'])) {
                if (isset($targets[$target])) { throw new InvalidArgumentException('Eine Zielvariable darf nur einem aktiven Datenfeld zugeordnet werden.'); }
                $targets[$target] = true;
                $this->validateTarget($target, $catalog[$row['Ident']]['type'] ?? null);
            }
            $seen[$row['Ident']] = true;
            $result[$row['Ident']] = $row['Enabled'];
        }
        return $result;
    }

    private function selectionRows(): array
    {
        $overrides = $this->selectionOverrides();
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        $idents = array_unique(array_merge(array_keys($catalog), array_keys($overrides)));
        $rows = [];
        foreach ($idents as $ident) {
            $meta = $catalog[$ident] ?? [];
            $options = $this->variableOptions($ident);
            $rows[] = $options + ['Ident' => $ident, 'Enabled' => $overrides[$ident] ?? $this->defaultVariableEnabled($ident),
                'Group' => $this->variableGroup($ident), 'Name' => $meta['name'] ?? $ident,
                'Type' => isset($meta['type']) ? ['Boolean', 'Integer', 'Float', 'String'][$meta['type']] : 'Noch nicht erkannt'];
        }
        usort($rows, static fn(array $a, array $b): int =>
            strnatcasecmp($a['Group'], $b['Group']) ?: strnatcasecmp($a['Name'], $b['Name']));
        return $rows;
    }

    private function variableOptions(string $ident): array
    {
        foreach (json_decode($this->ReadPropertyString('VariableSelection'), true, 64, JSON_THROW_ON_ERROR) as $row) {
            if (($row['Ident'] ?? '') === $ident) {
                return ['CustomName' => $row['CustomName'] ?? '', 'Position' => $row['Position'] ?? -1,
                    'Presentation' => $row['Presentation'] ?? 'auto', 'TargetID' => $row['TargetID'] ?? 0];
            }
        }
        return ['CustomName' => '', 'Position' => -1, 'Presentation' => 'auto', 'TargetID' => 0];
    }

    private function retainDisabled(string $ident): void
    {
        $id = @$this->GetIDForIdent($ident);
        if (!$id) { return; }
        $this->DisableAction($ident);
        $hidden = json_decode($this->ReadAttributeString('HiddenVariables'), true);
        if (!array_key_exists($ident, $hidden)) {
            $hidden[$ident] = IPS_GetObject($id)['ObjectIsHidden'];
            $this->WriteAttributeString('HiddenVariables', WLTApi::encode($hidden));
            IPS_SetHidden($id, true);
        }
    }

    private function invalidateActions(): void
    {
        foreach (json_decode($this->ReadAttributeString('Dynamic'), true) as $ident) { $this->disableIfExists($ident); }
    }

    private function presentation(string $ident, int $type, string $profile, string $mode): array|string
    {
        if ($mode === 'legacy') { return $profile; }
        $value = ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION];
        $unit = str_ends_with($profile, 'F') ? ' °F' : ' °C';
        if (str_starts_with($profile, 'WLT.Temperature') || str_starts_with($profile, 'WLT.Setpoint')) {
            if ($mode === 'auto' && str_starts_with($profile, 'WLT.Setpoint')) {
                return ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => str_starts_with($ident, 'PM') ? 0 : -30, 'MAX' => 999,
                    'STEP_SIZE' => 1, 'DIGITS' => 0, 'SUFFIX' => $unit];
            }
            return $value + ['SUFFIX' => $unit, 'DIGITS' => str_starts_with($profile, 'WLT.Setpoint') ? 0 : 1, 'USAGE_TYPE' => 1];
        }
        if ($ident === 'LastUpdate' && $mode === 'auto') {
            return ['PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME, 'DATE' => 1, 'TIME' => 2];
        }
        if ($ident === 'BluetoothEnabled' && $mode === 'auto') { return ['PRESENTATION' => VARIABLE_PRESENTATION_SWITCH]; }
        if ($type === 0) {
            return $value + ['OPTIONS' => WLTApi::encode([
                ['Value' => false, 'Caption' => 'Nein'], ['Value' => true, 'Caption' => 'Ja']
            ])];
        }
        if ($profile === 'WLT.Percent') {
            if ($mode === 'auto') {
                return ['PRESENTATION' => VARIABLE_PRESENTATION_SLIDER, 'MIN' => 0, 'MAX' => 100,
                    'STEP_SIZE' => 1, 'DIGITS' => 0, 'SUFFIX' => ' %'];
            }
            return $value + ['SUFFIX' => ' %', 'DIGITS' => 0];
        }
        if ($mode === 'auto' && in_array($profile, ['WLT.Mode', 'WLT.Alarm'], true)) {
            $labels = $profile === 'WLT.Mode' ? ['off' => 'Aus', 'manual' => 'Manuell', 'auto' => 'Automatisch'] : ['Aus', 'Push', 'Summer', 'Push + Summer'];
            $options = [];
            foreach ($labels as $v => $label) { $options[] = ['Value' => $v, 'Caption' => $label]; }
            return ['PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION, 'OPTIONS' => WLTApi::encode($options)];
        }
        if ($mode === 'auto' && preg_match('/^(CH\d+_(Name|Type|Color)|PM\d+_(Channel|PID))$/', $ident)) {
            return ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT];
        }
        return $value;
    }

    private function safeSet(string $ident, mixed $value): void
    {
        if (in_array($ident, ['Online', 'LastUpdate', 'LastError', 'BluetoothError', 'Device'], true)) {
            $this->SetBuffer('State_' . $ident, json_encode($value, JSON_THROW_ON_ERROR));
        }
        try { $selected = $this->selected($ident); }
        catch (JsonException | InvalidArgumentException $e) { return; }
        if (!$selected) { return; }
        $target = $this->variableOptions($ident)['TargetID'];
        if ($target > 0) {
            $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
            $this->validateTarget($target, $catalog[$ident]['type'] ?? null);
            SetValue($target, $value);
        } elseif (@$this->GetIDForIdent($ident)) { $this->SetValue($ident, $value); }
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
````

## WLANThermo/form.json

````json
{
    "elements": [
        {
            "type": "Label",
            "caption": "WLANThermo Mini V3 · IP-Symcon ab 9.0 · Version 1.11.0"
        },
        {
            "type": "ExpansionPanel",
            "caption": "Allgemeine Einstellungen",
            "items": [
                {
                    "type": "CheckBox",
                    "name": "Active",
                    "caption": "Aktiv"
                },
                {
                    "type": "ValidationTextBox",
                    "name": "Host",
                    "caption": "IP-Adresse / Hostname (optional http(s):// und Port)"
                },
                {
                    "type": "ValidationTextBox",
                    "name": "Username",
                    "caption": "Admin-Benutzername"
                },
                {
                    "type": "PasswordTextBox",
                    "name": "Password",
                    "caption": "WLANthermo Admin-Passwort (falls gesetzt)"
                },
                {
                    "type": "NumberSpinner",
                    "name": "Interval",
                    "caption": "Abrufintervall (Sekunden)",
                    "minimum": 5,
                    "maximum": 3600
                }
            ],
            "expanded": true
        },
        {
            "type": "ExpansionPanel",
            "caption": "Geräte und Datenquellen",
            "items": [
                {
                    "type": "Label",
                    "caption": "Temperaturkanäle und Bluetooth-Geräte werden über die lokale API des Mini V3 erkannt. Die Liste erscheint nach erfolgreichem Abruf."
                },
                {
                    "type": "CheckBox",
                    "name": "Bluetooth",
                    "caption": "Bluetooth-Geräte abfragen und verwalten"
                }
            ]
        },
        {
            "type": "ExpansionPanel",
            "caption": "Variablenverwaltung · Lizenzoptimiert",
            "items": [
                {
                    "type": "Select",
                    "name": "VariableMode",
                    "caption": "Standard für Datenfelder ohne Einzelentscheidung",
                    "options": [
                        {
                            "caption": "Automatisch: Neuinstallation keine, Update bisheriger Umfang",
                            "value": "automatic"
                        },
                        {
                            "caption": "Keine Variablen",
                            "value": "none"
                        },
                        {
                            "caption": "Empfohlen: Online, Temperaturen und Gültigkeit",
                            "value": "recommended"
                        },
                        {
                            "caption": "Alle angebotenen Felder",
                            "value": "all"
                        }
                    ]
                },
                {
                    "type": "Label",
                    "caption": "Neue Instanzen legen standardmäßig keine Variablen an. Die Kachel benötigt keine Statusvariablen. Gespeicherte Einzelentscheidungen haben Vorrang vor dem Standard. Gruppen haben weiterhin Vorrang."
                },
                {
                    "type": "Label",
                    "caption": "Variablenanzahl wird beim Öffnen berechnet.",
                    "name": "VariableCounts"
                },
                {
                    "type": "Label",
                    "caption": "Zeilen bearbeiten und anschließend Übernehmen. Deaktivieren blendet vorhandene Variablen aus, stoppt deren Aktualisierung und Bedienung und bewahrt ihre Daten. Noch nicht vorhandene Variablen werden nur bei Aktivierung angelegt."
                },
                {
                    "type": "ExpansionPanel",
                    "caption": "Variablengruppen",
                    "items": [
                        {
                            "type": "Label",
                            "caption": "Eine deaktivierte Gruppe hat Vorrang vor der Einzelauswahl. Vorhandene Variablen werden ausgeblendet und nicht gelöscht. IDs, Werte und Archivzuordnungen bleiben erhalten."
                        },
                        {
                            "type": "CheckBox",
                            "name": "ShowSystem",
                            "caption": "Zusätzliche API-Systemwerte (WLAN, Akku, Zeit …)"
                        },
                        {
                            "type": "CheckBox",
                            "name": "ShowSettings",
                            "caption": "Alle API-Einstellungen (Fühlertypen, PID-Profile, Gerätefunktionen …)"
                        },
                        {
                            "type": "CheckBox",
                            "name": "ShowBluetoothDetails",
                            "caption": "Zusätzliche Bluetooth-Gerätedetails"
                        },
                        {
                            "type": "CheckBox",
                            "name": "ShowChannelDetails",
                            "caption": "Kanaldetails (Name, Fühlertyp, Farbe, Funkstatus und Zusatzfelder)"
                        },
                        {
                            "type": "CheckBox",
                            "name": "ShowPitmaster",
                            "caption": "Pitmaster-Variablen und weitere Pitmaster-Daten"
                        },
                        {
                            "type": "CheckBox",
                            "name": "ShowDevice",
                            "caption": "Geräteinformationen als JSON"
                        }
                    ]
                },
                {
                    "type": "Label",
                    "caption": "Eigener Name leer und Position -1 erhalten bisherige Namen und Positionen. Eigener Name ändert nur die Symcon-Beschriftung. Automatisch verwendet aktuelle Symcon-Darstellungen; Bisheriges Profil dient älteren Visualisierungen. Neue API-Felder erscheinen nach erneutem Öffnen."
                },
                {
                    "type": "Label",
                    "caption": "Vorhandene Zielvariable: 0 legt bei Bedarf eine eigene Variable an. Andere IDs dienen als beschreibbares Datenziel gleichen Typs. Profile, Aktionen, Namen, Positionen und Archivierung dieser Ziele bleiben unverändert; Bedienung erfolgt über die Kachel. Eigene alte Variablen bleiben ausgeblendet erhalten."
                },
                {
                    "type": "List",
                    "name": "VariableSelection",
                    "caption": "Variablen verwalten",
                    "rowCount": 15,
                    "add": false,
                    "delete": false,
                    "loadValuesFromConfiguration": false,
                    "columns": [
                        {
                            "name": "Enabled",
                            "caption": "Aktiv",
                            "width": "80px",
                            "save": true,
                            "edit": {
                                "type": "CheckBox"
                            }
                        },
                        {
                            "name": "Group",
                            "caption": "Bereich",
                            "width": "160px",
                            "save": false,
                            "quickFilter": true
                        },
                        {
                            "name": "Name",
                            "caption": "Variable",
                            "width": "auto",
                            "save": false,
                            "quickFilter": true
                        },
                        {
                            "name": "Ident",
                            "caption": "Ident",
                            "width": "190px",
                            "save": true,
                            "quickFilter": true
                        },
                        {
                            "name": "Type",
                            "caption": "Typ",
                            "width": "90px",
                            "save": false
                        },
                        {
                            "name": "CustomName",
                            "caption": "Eigener Name",
                            "width": "180px",
                            "save": true,
                            "edit": {
                                "type": "ValidationTextBox"
                            }
                        },
                        {
                            "name": "Presentation",
                            "caption": "Darstellung",
                            "width": "140px",
                            "save": true,
                            "edit": {
                                "type": "Select",
                                "options": [
                                    {
                                        "caption": "Automatisch",
                                        "value": "auto"
                                    },
                                    {
                                        "caption": "Wertanzeige",
                                        "value": "value"
                                    },
                                    {
                                        "caption": "Bisheriges Profil",
                                        "value": "legacy"
                                    }
                                ]
                            }
                        },
                        {
                            "name": "Position",
                            "caption": "Position",
                            "width": "90px",
                            "save": true,
                            "edit": {
                                "type": "NumberSpinner",
                                "minimum": -1,
                                "maximum": 1000000
                            }
                        },
                        {
                            "name": "TargetID",
                            "caption": "Vorhandene Zielvariable",
                            "width": "170px",
                            "save": true,
                            "edit": {
                                "type": "SelectVariable"
                            }
                        }
                    ],
                    "values": []
                },
                {
                    "type": "Label",
                    "caption": "Sammelauswahl verwendet den zuletzt gespeicherten Stand. Vorherige Zeilenänderungen zuerst übernehmen. Anschließend Vorschlag wählen und erneut Übernehmen drücken. Neue Felder folgen dem Standard oben."
                },
                {
                    "type": "RowLayout",
                    "items": [
                        {
                            "type": "Button",
                            "caption": "Alle auswählen",
                            "onClick": "WLT_PrepareVariableSelection($id, 'all');"
                        },
                        {
                            "type": "Button",
                            "caption": "Alle abwählen",
                            "onClick": "WLT_PrepareVariableSelection($id, 'none');"
                        },
                        {
                            "type": "Button",
                            "caption": "Empfohlene Auswahl",
                            "onClick": "WLT_PrepareVariableSelection($id, 'recommended');"
                        }
                    ]
                }
            ],
            "expanded": true
        },
        {
            "type": "ExpansionPanel",
            "caption": "Premium-Visualisierung",
            "items": [
                {
                    "type": "CheckBox",
                    "name": "TileEnabled",
                    "caption": "Eigene Grill-Kachel aktivieren"
                },
                {
                    "type": "Select",
                    "name": "TileTheme",
                    "caption": "Designmodus",
                    "options": [
                        {
                            "caption": "Automatisch (Gerätefarbschema)",
                            "value": "auto"
                        },
                        {
                            "caption": "Hell",
                            "value": "light"
                        },
                        {
                            "caption": "Dunkel",
                            "value": "dark"
                        }
                    ]
                },
                {
                    "type": "SelectColor",
                    "name": "TileAccent",
                    "caption": "Akzentfarbe"
                },
                {
                    "type": "CheckBox",
                    "name": "TileCompact",
                    "caption": "Kompaktmodus"
                },
                {
                    "type": "CheckBox",
                    "name": "TileDetails",
                    "caption": "Gerät, Akku, WLAN und Zeitstempel anzeigen"
                },
                {
                    "type": "CheckBox",
                    "name": "TileIcons",
                    "caption": "Symbole anzeigen"
                },
                {
                    "type": "CheckBox",
                    "name": "TileAnimations",
                    "caption": "Dezente Animationen aktivieren"
                },
                {
                    "type": "CheckBox",
                    "name": "TileControls",
                    "caption": "Bedienelemente anzeigen und erlauben (zusätzlich zu Schreibzugriffen)"
                },
                {
                    "type": "CheckBox",
                    "name": "TilePitmaster",
                    "caption": "Pitmaster anzeigen"
                },
                {
                    "type": "ValidationTextBox",
                    "name": "TileTitle",
                    "caption": "Titel der Kachel (z. B. Grillstation)"
                },
                {
                    "type": "SelectFile",
                    "name": "TileBrandImage1",
                    "caption": "Titelbild 1 hochladen (PNG/JPEG/GIF/WebP)",
                    "extensions": ".png,.jpg,.jpeg,.gif,.webp"
                },
                {
                    "type": "CheckBox",
                    "name": "TileBrandImage1Visible",
                    "caption": "Titelbild 1 anzeigen"
                },
                {
                    "type": "SelectFile",
                    "name": "TileBrandImage2",
                    "caption": "Titelbild 2 hochladen (optional)",
                    "extensions": ".png,.jpg,.jpeg,.gif,.webp"
                },
                {
                    "type": "CheckBox",
                    "name": "TileBrandImage2Visible",
                    "caption": "Titelbild 2 anzeigen"
                },
                {
                    "type": "Select",
                    "name": "TileBrandLayout",
                    "caption": "Anordnung der Bilder an der Kopftemperatur",
                    "options": [
                        {
                            "caption": "Bild 1 links und Bild 2 rechts vom Temperaturwert",
                            "value": "split"
                        },
                        {
                            "caption": "Beide Bilder untereinander links vom Temperaturwert",
                            "value": "stacked"
                        }
                    ]
                },
                {
                    "type": "Label",
                    "caption": "Maximal 256 KiB und 2048 × 2048 Pixel pro Bild. Bei sichtbarer Kopftemperatur stehen die Bilder in deren Kachel. Ohne gültige Kopftemperatur erscheinen sie wieder am Titel. Ohne eingeblendete Bilder erscheint das Flammensymbol."
                },
                {
                    "type": "CheckBox",
                    "name": "TileHeaderEnabled",
                    "caption": "Kompakte Temperaturkachel oben im Kopfbereich anzeigen"
                },
                {
                    "type": "NumberSpinner",
                    "name": "TileHeaderChannel",
                    "caption": "Temperaturkanal für die Kachel im Kopfbereich",
                    "minimum": 1,
                    "maximum": 255
                },
                {
                    "type": "ValidationTextBox",
                    "name": "TileHeaderName",
                    "caption": "Name der Temperaturkachel (z. B. Grilltemperatur oder Räucherkammertemperatur)"
                },
                {
                    "type": "Label",
                    "caption": "Der Titel und der Name sind frei einstellbar. Die kompakte Temperaturkachel zeigt nur den gewählten, freigegebenen und gültigen Messwert. Bei fehlendem Wert wird sie ausgeblendet. Geräte- und Variablennamen bleiben erhalten."
                },
                {
                    "type": "CheckBox",
                    "name": "TileHero",
                    "caption": "Große Haupttemperatur oben anzeigen (z. B. Garraum)"
                },
                {
                    "type": "NumberSpinner",
                    "name": "TilePrimary",
                    "caption": "Kanal für die optionale Hauptanzeige (0 = erster gültiger Fühler)",
                    "minimum": 0,
                    "maximum": 255
                },
                {
                    "type": "Label",
                    "caption": "In der Kachel erscheinen nur aktuell gültige Fühlerwerte. Nicht angeschlossene Fühler und getrennte Bluetooth-Fühler werden ausgeblendet. Die große Hauptanzeige ist optional und standardmäßig aus. Ein fest gewählter Hauptfühler wird bei ungültigem Wert ausgeblendet und nicht durch einen anderen ersetzt."
                },
                {
                    "type": "Label",
                    "caption": "MEATER: eindeutige Namen wie MeaterKT/MeaterGT oder MEATER 1 KT/MEATER 1 GT werden gemeinsam dargestellt. Bei anderen oder doppelten Namen die Kanäle hier ausdrücklich zuordnen. Bestehende Kanal- und Variablennamen bleiben erhalten."
                },
                {
                    "type": "List",
                    "name": "TileMeaterPairs",
                    "caption": "MEATER-Zuordnung (optional, hat Vorrang vor Namenserkennung)",
                    "rowCount": 3,
                    "add": true,
                    "delete": true,
                    "columns": [
                        {
                            "name": "Name",
                            "caption": "Thermometer",
                            "width": "auto",
                            "add": "MEATER",
                            "edit": {
                                "type": "ValidationTextBox"
                            }
                        },
                        {
                            "name": "Core",
                            "caption": "Kanal Kern",
                            "width": "130px",
                            "add": 13,
                            "edit": {
                                "type": "NumberSpinner",
                                "minimum": 1,
                                "maximum": 255
                            }
                        },
                        {
                            "name": "Ambient",
                            "caption": "Kanal Garraum",
                            "width": "140px",
                            "add": 14,
                            "edit": {
                                "type": "NumberSpinner",
                                "minimum": 1,
                                "maximum": 255
                            }
                        }
                    ]
                },
                {
                    "type": "List",
                    "name": "TileChannels",
                    "caption": "Fühler in der Kachel",
                    "rowCount": 6,
                    "add": false,
                    "delete": false,
                    "loadValuesFromConfiguration": false,
                    "columns": [
                        {
                            "name": "Enabled",
                            "caption": "Anzeigen",
                            "width": "90px",
                            "save": true,
                            "edit": {
                                "type": "CheckBox"
                            }
                        },
                        {
                            "name": "Number",
                            "caption": "Kanal",
                            "width": "70px",
                            "save": true
                        },
                        {
                            "name": "Name",
                            "caption": "Name",
                            "width": "auto",
                            "save": false
                        },
                        {
                            "name": "DisplayName",
                            "caption": "Eigener Anzeigename (optional)",
                            "width": "250px",
                            "save": true,
                            "edit": {
                                "type": "ValidationTextBox"
                            }
                        },
                        {
                            "name": "Notify",
                            "caption": "Pushover",
                            "width": "90px",
                            "save": true,
                            "edit": {
                                "type": "CheckBox"
                            }
                        }
                    ],
                    "values": []
                },
                {
                    "type": "List",
                    "name": "TileCorePresets",
                    "caption": "Kerntemperatur-Sammlung (Temperaturen in °C)",
                    "rowCount": 5,
                    "add": true,
                    "delete": true,
                    "columns": [
                        {
                            "name": "Name",
                            "caption": "Vorlage / Gericht",
                            "width": "auto",
                            "add": "Neue Vorlage",
                            "edit": {
                                "type": "ValidationTextBox"
                            }
                        },
                        {
                            "name": "Temperature",
                            "caption": "Kerntemperatur °C",
                            "width": "180px",
                            "add": 57,
                            "edit": {
                                "type": "NumberSpinner",
                                "minimum": -30,
                                "maximum": 300
                            }
                        }
                    ]
                },
                {
                    "type": "CheckBox",
                    "name": "TargetWarningEnabled",
                    "caption": "Temperatur kurz vor dem Ziel und ab Zielerreichung rot anzeigen"
                },
                {
                    "type": "CheckBox",
                    "name": "TargetWarningBlink",
                    "caption": "Rote Temperatur zusätzlich sanft pulsieren lassen (abschaltbar über Animationen)"
                },
                {
                    "type": "Select",
                    "name": "TargetWarningLeadTenths",
                    "caption": "Vorlauf für rote Anzeige und Vorwarnung (in °C Differenz)",
                    "options": [
                        {
                            "caption": "Nur bei Zielerreichung",
                            "value": 0
                        },
                        {
                            "caption": "0,5 °C vorher",
                            "value": 5
                        },
                        {
                            "caption": "1 °C vorher",
                            "value": 10
                        },
                        {
                            "caption": "2 °C vorher",
                            "value": 20
                        }
                    ]
                },
                {
                    "type": "CheckBox",
                    "name": "PushoverEnabled",
                    "caption": "Pushover-Benachrichtigungen aktivieren (Versand an den Pushover-Dienst)"
                },
                {
                    "type": "PasswordTextBox",
                    "name": "PushoverToken",
                    "caption": "Pushover App-/API-Token"
                },
                {
                    "type": "PasswordTextBox",
                    "name": "PushoverUser",
                    "caption": "Pushover User-/Gruppenschlüssel"
                },
                {
                    "type": "ValidationTextBox",
                    "name": "PushoverDevice",
                    "caption": "Pushover Gerätename (leer = alle Geräte des Empfängers)"
                },
                {
                    "type": "CheckBox",
                    "name": "PushoverNear",
                    "caption": "Nachricht kurz vor Erreichen der Obergrenze"
                },
                {
                    "type": "CheckBox",
                    "name": "PushoverReached",
                    "caption": "Nachricht beim Erreichen der Obergrenze"
                },
                {
                    "type": "Label",
                    "name": "PushoverState",
                    "caption": "Pushover: ausgeschaltet."
                },
                {
                    "type": "Button",
                    "caption": "Pushover-Fehler zurücksetzen (erneut beim nächsten Abruf versuchen)",
                    "onClick": "echo WLT_RetryPushover($id) ? \"Versandsperre zurückgesetzt.\" : \"Instanz ist beschäftigt oder deaktiviert.\";"
                },
                {
                    "type": "Label",
                    "caption": "Meldungen gelten für gültige, eingeblendete Kern-/Einzelfühler mit aktiviertem Pushover-Schalter in der Kanaltabelle. Kopfkanal und MEATER-Garraum sind ausgeschlossen. Pro Schwelle eine bestätigte Meldung; erneute Aktivierung nach 5 Minuten mindestens 2 °C unterhalb des Vorwarnbereichs oder bei geändertem Ziel. Fehler erscheinen in der Kachel; kein Testversand beim Speichern."
                },
                {
                    "type": "Label",
                    "caption": "Vorlagen in der Kachel auswählen und ausdrücklich als Obergrenze übernehmen. Beim Gerätebetrieb in °F erfolgt eine Umrechnung mit Rundung auf ganze Grad. Leere Sammlung blendet die Auswahl aus. Eigene Fühlernamen ändern nur die Visualisierung, nicht Gerätekanäle oder Variablen. Bei MEATER-Paaren wird der Anzeigename des Kernkanals als gemeinsamer Titel verwendet."
                },
                {
                    "type": "Label",
                    "caption": "IPSView: TileHtml auf der Seite platzieren und die ID dieser WLANThermo-Instanz zuweisen. Der normale HTML-Typ ist standardmäßig aktiv. HTML-Vollbild nur bei Bedarf für die Symcon-Kachelvisualisierung einschalten; benötigt Symcon ab 9.1."
                },
                {
                    "type": "CheckBox",
                    "name": "TileFullscreen",
                    "caption": "Erweiterte HTML-Vollbildansicht verwenden (Symcon ab 9.1; für IPSView ausgeschaltet lassen)"
                },
                {
                    "type": "CheckBox",
                    "name": "ForecastEnabled",
                    "caption": "Restzeit bis zur Obergrenze schätzen (ohne zusätzliche Variablen)"
                },
                {
                    "type": "CheckBox",
                    "name": "ForecastChart",
                    "caption": "Temperaturverlauf und gestrichelte Trendfortschreibung anzeigen"
                },
                {
                    "type": "NumberSpinner",
                    "name": "ForecastWindow",
                    "caption": "Trendfenster in Minuten",
                    "minimum": 5,
                    "maximum": 60
                },
                {
                    "type": "Label",
                    "caption": "Nach mindestens 3 Minuten und 6 Messpunkten wird nur bei ausreichend gleichmäßigem Anstieg eine Trend-Schätzung angezeigt. Plateau, Abkühlung oder schwankender Verlauf ergeben keine Restzeit. Verlauf und Starttemperatur werden intern gespeichert; Neustart, Fühlerwechsel oder längere Unterbrechungen starten neu. Für einen neuen Garvorgang beim jeweiligen Fühler „Verlauf neu starten“ wählen. Die Prognose ist keine garantierte Garzeit."
                }
            ]
        },
        {
            "type": "ExpansionPanel",
            "caption": "Erweiterte Einstellungen",
            "items": [
                {
                    "type": "CheckBox",
                    "name": "AllowWrite",
                    "caption": "Werte am WLANthermo ändern erlauben"
                },
                {
                    "type": "NumberSpinner",
                    "name": "Timeout",
                    "caption": "HTTP-Timeout je Anfrage (Sekunden)",
                    "minimum": 1,
                    "maximum": 30
                },
                {
                    "type": "CheckBox",
                    "name": "ExposeRaw",
                    "caption": "Zusätzliche JSON-Diagnosevariablen (Zugangsdaten ausgeblendet)"
                }
            ]
        }
    ],
    "actions": [
        {
            "type": "ExpansionPanel",
            "caption": "Diagnose und Aktionen",
            "items": [
                {
                    "type": "Label",
                    "caption": "Letzter Hinweis: Formular erneut öffnen, um den Hinweis zu aktualisieren."
                },
                {
                    "type": "RowLayout",
                    "items": [
                        {
                            "type": "Button",
                            "caption": "Messwerte jetzt abrufen",
                            "onClick": "echo WLT_Update($id) ? 'Abruf erfolgreich.' : (WLT_GetLastError($id) ?: 'Instanz ist beschäftigt.');"
                        },
                        {
                            "type": "Button",
                            "caption": "Bluetooth-Liste aktualisieren",
                            "onClick": "echo WLT_RefreshBluetooth($id) ? 'Abruf erfolgreich.' : (WLT_GetLastError($id) ?: 'Instanz ist beschäftigt.');"
                        }
                    ]
                },
                {
                    "type": "Button",
                    "caption": "Verbindung testen",
                    "onClick": "echo WLT_Update($id) ? 'Verbindung und Hauptdatenabruf erfolgreich.' : (WLT_GetLastError($id) ?: 'Instanz ist beschäftigt.');"
                },
                {
                    "type": "Label",
                    "caption": "Der Verbindungstest liest Daten vom Gerät und aktualisiert die Variablen. Der Instanzstatus zeigt aktiv, deaktiviert, Standby oder Fehler. Bluetooth-Hinweise stehen separat in BluetoothError."
                }
            ],
            "expanded": true
        },
        {
            "type": "ExpansionPanel",
            "caption": "Erweiterte Einstellungen (JSON)",
            "items": [
                {
                    "type": "Label",
                    "caption": "Beispiele und Parameter stehen in README.md. Änderungen werden direkt am Gerät ausgeführt."
                },
                {
                    "type": "Select",
                    "name": "WriteArea",
                    "caption": "Bereich",
                    "options": [
                        {
                            "caption": "Temperaturkanal",
                            "value": "channel"
                        },
                        {
                            "caption": "Pitmaster",
                            "value": "pitmaster"
                        },
                        {
                            "caption": "PID-Profil",
                            "value": "pid"
                        },
                        {
                            "caption": "System",
                            "value": "system"
                        },
                        {
                            "caption": "MQTT / Cloud",
                            "value": "iot"
                        }
                    ],
                    "value": "channel"
                },
                {
                    "type": "NumberSpinner",
                    "name": "WriteID",
                    "caption": "Kanalnummer / Pitmaster-ID / PID-ID",
                    "minimum": 0,
                    "value": 1
                },
                {
                    "type": "ValidationTextBox",
                    "name": "WriteJSON",
                    "caption": "Änderungen als JSON-Objekt",
                    "value": "{\"min\":10,\"max\":95}"
                },
                {
                    "type": "Button",
                    "caption": "Änderung senden und prüfen",
                    "onClick": "switch ($WriteArea) { case 'channel': $ok = WLT_SetChannel($id, $WriteID, $WriteJSON); break; case 'pitmaster': $ok = WLT_SetPitmaster($id, $WriteID, $WriteJSON); break; case 'pid': $ok = WLT_SetPID($id, $WriteID, $WriteJSON); break; case 'iot': $ok = WLT_SetIoT($id, $WriteJSON); break; case 'system': $ok = WLT_SetSystem($id, $WriteJSON); break; default: throw new Exception('Unbekannter Bereich'); } echo $ok ? 'Änderung durch Rücklesen bestätigt.' : WLT_GetLastError($id);"
                }
            ]
        },
        {
            "type": "PopupButton",
            "caption": "Ungenutzte Modulvariablen entfernen …",
            "popup": {
                "caption": "Modulvariablen dauerhaft entfernen",
                "items": [
                    {
                        "type": "Label",
                        "caption": "Nur nach Übernahme der Auswahl verwenden. Entfernt ausschließlich eigene, deaktivierte oder durch externe Ziele ersetzte Modulvariablen. Ihre IDs und Zuordnungen gehen verloren. Externe Zielvariablen und ausgewählte eigene Variablen werden nicht gelöscht. Ausblenden allein spart keine Variablenlizenz."
                    },
                    {
                        "type": "CheckBox",
                        "name": "ConfirmRemoval",
                        "caption": "Ich bestätige das dauerhafte Entfernen der ungenutzten Modulvariablen.",
                        "value": false
                    },
                    {
                        "type": "Button",
                        "caption": "Jetzt dauerhaft entfernen",
                        "onClick": "echo WLT_RemoveDisabledVariables($id, $ConfirmRemoval) . ' eigene Variablen entfernt.';"
                    }
                ]
            }
        }
    ],
    "status": [
        {
            "code": 102,
            "icon": "active",
            "caption": "Aktiv"
        },
        {
            "code": 104,
            "icon": "inactive",
            "caption": "Deaktiviert"
        },
        {
            "code": 106,
            "icon": "inactive",
            "caption": "Standby – WLANthermo ausgeschaltet oder nicht erreichbar; erneuter Abruf automatisch"
        },
        {
            "code": 201,
            "icon": "error",
            "caption": "Konfiguration prüfen"
        },
        {
            "code": 202,
            "icon": "error",
            "caption": "Abruf oder Änderung fehlgeschlagen – siehe Letzter Fehler"
        }
    ]
}
````

## WLANThermo/module.html

````html
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>WLANThermo · Grillstation</title>
<style>/*WLT_CSS*/</style>
</head>
<body>
<main id="grill" aria-label="WLANThermo Grillstation">
  <header class="topbar">
    <div class="header-brand-column"><div id="brand" class="brand"><span id="brand-default" class="brandmark icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13 2c1 5-5 5-3 10 2-1 3-3 3-5 4 4 6 6 6 9a7 7 0 0 1-14 0c0-4 3-7 8-14Z"/></svg></span><div id="brand-images" class="brand-images" hidden><img id="brand-image-1" class="brand-image" alt="" hidden><img id="brand-image-2-stack" class="brand-image" alt="" hidden></div><div id="brand-title" class="brand-title"><h1 id="tile-title">Grillstation</h1></div><img id="brand-image-2-after" class="brand-image" alt="" hidden></div><div class="top-actions"><span id="connection" class="badge">Warte auf Daten</span><button id="refresh" class="icon-button" type="button" aria-label="Messwerte aktualisieren" title="Messwerte aktualisieren"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 7a7 7 0 0 1 12 0l2 5M4 12l2 5a7 7 0 0 0 12 0"/></svg></button></div></div>
    <section id="header-temperature" class="header-temperature" aria-label="Grilltemperatur" hidden><div id="header-image-left" class="header-image-slot"></div><div class="header-temperature-main"><div class="header-temperature-copy"><h2 id="header-temperature-name">Grilltemperatur</h2></div><div class="header-temperature-reading"><strong id="header-temperature-value">—</strong><span id="header-temperature-unit">°C</span></div></div><div id="header-image-right" class="header-image-slot"></div></section>
    <div class="header-status"><details id="wired-selector" class="wired-selector" hidden><summary id="wired-selector-toggle" class="wired-selector-toggle" hidden>Fühler auswählen <span aria-hidden="true">⌄</span></summary><div id="wired-toolbar" class="wired-toolbar" role="group" aria-label="Kabelgebundene Fühler ein- und ausblenden" hidden></div></details><footer id="details"><span id="device"></span><span id="telemetry"></span><button id="bluetooth" type="button" class="text-button"></button><span id="updated"></span></footer></div>
  </header>
  <section id="hero" class="hero" aria-label="Hauptmesswert" hidden>
    <div class="hero-copy"><span id="hero-channel" class="eyebrow">TEMPERATUR</span><h2 id="hero-name">Noch keine Messwerte</h2><div class="hero-value"><span id="hero-temperature">—</span><span id="hero-unit" class="unit">°C</span></div><div id="hero-state" class="state-line">Verbindung in der Instanz konfigurieren.</div><div id="hero-range" class="range-summary"></div><div id="hero-forecast" class="hero-forecast" hidden><span class="hero-forecast-label">GESCHÄTZTE RESTZEIT</span><strong id="hero-forecast-value"></strong><span id="hero-forecast-eta" class="hero-forecast-eta" hidden></span></div></div>
    <div id="gauge" class="gauge" aria-label="Temperatur im Verhältnis zur Obergrenze"><div class="gauge-core"><svg class="icon probe-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5a2 2 0 0 1 4 0v9a4 4 0 1 1-4 0V5Zm2 3v9"/></svg><span class="gauge-label">OBERGRENZE</span><strong id="gauge-range">—</strong></div></div>
  </section>
  <p id="empty-state" class="empty" role="status" hidden>Keine gültigen Fühlerwerte verfügbar.</p>
  <section id="channels-section" aria-label="Temperaturfühler"><div class="section-title probe-section-heading"><h2>Fühlerübersicht</h2></div><p id="wired-empty" class="empty" role="status" hidden>Alle kabelgebundenen Fühler sind ausgeblendet. Über die Leiste kannst du sie wieder einblenden.</p><div id="channels" class="probe-grid"></div></section>
  <section id="pitmasters-section" aria-label="Pitmaster"><div class="section-title"><h2>Pitmaster</h2><span class="subtle">Regelung am Mini V3</span></div><div id="pitmasters" class="pitmaster-grid"></div></section>

  <p id="notice" role="status" aria-live="polite" hidden></p>
  <p id="device-error" role="status" hidden></p>
</main>
<script id="initial-state" type="application/json">__WLT_INITIAL__</script>
<script>/*WLT_JS*/</script>
</body>
</html>
````

## WLANThermo/tile.css

````css
:root{color-scheme:dark;--bg:#101519;--surface:#191f24;--raised:#212930;--text:#f2f4f5;--muted:#a6b2ba;--border:#303c44;--accent:#f48941;--ok:#7bd9b0;--danger:#ffab89;--shadow:0 18px 52px #0002;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
:root[data-theme="light"]{color-scheme:light;--bg:#f2f4f2;--surface:#fff;--raised:#eaf0ec;--text:#1c2c2a;--muted:#536962;--border:#d4dfd8;--ok:#14714c;--danger:#a93919;--shadow:0 18px 52px #2333280a}
@media(prefers-color-scheme:light){:root:not([data-theme="dark"]){color-scheme:light;--bg:#f2f4f2;--surface:#fff;--raised:#eaf0ec;--text:#1c2c2a;--muted:#536962;--border:#d4dfd8;--ok:#14714c;--danger:#a93919;--shadow:0 18px 52px #2333280a}}
*{box-sizing:border-box}html,body{margin:0;min-height:100%;background:var(--bg);color:var(--text)}body{overflow-x:hidden}button,input,select{font:inherit}button{color:inherit;cursor:pointer}button:disabled{cursor:default;opacity:.45}button:focus-visible,input:focus-visible,select:focus-visible,summary:focus-visible{outline:3px solid var(--accent);outline-offset:3px}button,input,select{min-height:42px;border-radius:10px}button{border:1px solid var(--border);background:var(--raised);padding:9px 13px}button:hover:enabled{border-color:var(--accent)}svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}h1,h2,h3,p{margin:0}h1{font-size:21px;font-weight:650;letter-spacing:-.6px}h2{font-size:16px;font-weight:600}h3{font-size:15px;font-weight:600}#grill{max-width:1440px;margin:auto;padding:24px;display:grid;gap:24px}.topbar,.brand,.top-actions{display:flex;align-items:center;gap:12px}.topbar{justify-content:space-between;min-width:0}.brand{min-width:0}.brandmark{width:43px;height:48px;border:1px solid var(--border);border-radius:14px;display:grid;place-items:center;color:var(--accent);background:var(--surface)}.brandmark svg{width:24px;height:28px;fill:currentColor;stroke:none}.eyebrow{display:block;font-size:10px;letter-spacing:2.1px;font-weight:750;color:var(--muted);margin-bottom:5px}.badge{font-size:11px;font-weight:650;white-space:nowrap;color:var(--muted);padding:7px 10px;border:1px solid var(--border);border-radius:99px}.badge.online{color:var(--ok)}.badge.online:before{content:"";display:inline-block;background:currentColor;width:6px;height:6px;border-radius:50%;margin-right:7px}.icon-button{display:grid;place-items:center;padding:9px;min-width:42px}.hero{display:flex;align-items:center;justify-content:space-between;gap:16px;background:var(--surface);background:radial-gradient(ellipse at 95% 0%,color-mix(in srgb,var(--accent) 11%,transparent),transparent 65%),var(--surface);border:1px solid var(--border);border-radius:20px;padding:28px 32px;box-shadow:var(--shadow);min-width:0}.hero-copy{min-width:0}.hero h2{font-size:20px;overflow-wrap:anywhere}.hero-value{display:flex;align-items:baseline;gap:9px;font-variant-numeric:tabular-nums;line-height:1.15;margin:12px 0}.hero-value>span:first-child{font-size:clamp(48px,8vw,88px);font-weight:550;letter-spacing:-5px}.unit{font-size:24px;color:var(--muted)}.state-line{font-size:12px;color:var(--ok);line-height:1.5}.state-line.warning{color:var(--danger)}.range-summary{color:var(--muted);font-size:12px;margin-top:10px}.gauge{--progress:0deg;width:168px;height:168px;border-radius:50%;background:conic-gradient(var(--accent) var(--progress),var(--raised) 0deg);padding:7px;flex-shrink:0;transform:rotate(-90deg)}.gauge-core{border-radius:50%;background:var(--surface);width:100%;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:7px;transform:rotate(90deg)}.probe-icon{width:28px;height:28px;color:var(--accent)}.gauge-label{font-size:8px;letter-spacing:1.3px;color:var(--muted);font-weight:700}.gauge strong{font-size:14px;font-weight:600}.section-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.section-title>span{font-size:11px;color:var(--muted)}.probe-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr));gap:12px}.probe{background:var(--surface);border:1px solid var(--border);border-radius:15px;padding:17px;min-width:0}.probe.selected{border-color:var(--accent)}.probe.choose{cursor:pointer}.probe-head{display:flex;gap:9px;align-items:start;justify-content:space-between}.probe-head h3{overflow-wrap:anywhere}.probe-kicker{font-size:9px;letter-spacing:1.5px;color:var(--muted);margin-bottom:5px}.probe-status{width:7px;height:7px;background:var(--ok);border-radius:50%;margin-top:5px;flex-shrink:0}.probe.warning .probe-status{background:var(--danger)}.probe.invalid .probe-status{background:var(--muted)}.probe-value{font-size:32px;font-weight:550;letter-spacing:-1px;margin:14px 0 8px;font-variant-numeric:tabular-nums}.probe-value small{font-size:14px;color:var(--muted);font-weight:400;margin-left:6px}.probe-label{font-size:11px;line-height:1.5;color:var(--muted)}.rail{height:4px;border-radius:9px;background:var(--raised);margin:12px 0 7px;overflow:hidden}.rail>span{display:block;height:100%;background:var(--accent);border-radius:9px}.rail-labels{display:flex;justify-content:flex-end;font-size:10px;color:var(--muted)}details{margin-top:12px;border-top:1px solid var(--border);padding-top:10px}summary{font-size:11px;color:var(--muted);cursor:pointer;min-height:26px}.limits-form{display:grid;grid-template-columns:1fr;gap:8px;margin-top:10px}.limits-form button{grid-column:1/-1}.field{display:grid;gap:6px;font-size:10px;color:var(--muted);min-width:0}.field input,.field select{width:100%;min-width:0;background:var(--bg);color:var(--text);border:1px solid var(--border);padding:9px;font-size:13px}.pitmaster-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:12px}.pitmaster{background:var(--surface);border:1px solid var(--border);border-radius:15px;padding:18px;min-width:0}.pm-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}.pm-meta{font-size:11px;color:var(--muted)}.pm-fields{display:grid;grid-template-columns:1.15fr 1fr;gap:10px}.pm-fields .submit{grid-column:1/-1;font-size:12px;font-weight:650;background:var(--raised)}footer{display:flex;flex-wrap:wrap;align-items:center;gap:10px 18px;padding-top:3px;font-size:10px;color:var(--muted)}#updated{margin-left:auto}.text-button{background:none;min-height:36px;font-size:10px;padding:4px 10px}.subtle{color:var(--muted)}#notice,#device-error{font-size:12px;padding:12px 14px;border-radius:10px;background:var(--raised);overflow-wrap:anywhere;line-height:1.5}#notice.error,#device-error{color:var(--danger)}[hidden]{display:none!important}.empty{color:var(--muted);padding:10px 0;font-size:12px}.no-icons .icon{display:none}.no-details footer{display:none}.compact #grill{padding:14px;gap:16px}.compact .hero{padding:20px}.compact .gauge{width:130px;height:130px}.compact .probe{padding:13px}.disabled .hero{opacity:.55}.busy #refresh svg{animation:spin 1.2s linear infinite}.no-animation *{animation:none!important;transition:none!important}@keyframes spin{to{transform:rotate(360deg)}}
@media(min-width:1000px){#grill{padding:32px;gap:28px}.hero{padding:36px 42px}.gauge{width:196px;height:196px}.hero-value>span:first-child{font-size:96px}.probe-grid{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}}
@media(max-width:440px){#grill{padding:14px;gap:18px}.topbar{gap:7px}.brand{gap:8px}.brandmark{width:33px;height:40px}h1{font-size:18px}.eyebrow{font-size:8px;letter-spacing:1.5px}.top-actions{gap:7px}.badge{font-size:9px;padding:6px 8px}.hero{padding:22px 19px;gap:10px}.hero h2{font-size:17px}.hero-value>span:first-child{font-size:58px;letter-spacing:-3px}.unit{font-size:18px}.gauge{width:112px;height:112px;padding:5px}.gauge strong{font-size:11px}.gauge-label{font-size:7px;letter-spacing:.8px}.gauge-core{gap:5px}.probe-icon{width:22px;height:22px}.probe-grid{grid-template-columns:1fr}.probe-value{margin:9px 0 4px}.probe{padding:15px}.pm-fields{gap:9px}#updated{margin-left:0}.subtle{font-size:9px!important}}
@media(max-width:300px){#grill{padding:9px;gap:13px}.brandmark,.gauge,.section-title>.subtle{display:none}.hero-value>span:first-child{font-size:53px}.hero{padding:18px}.badge{max-width:100px;overflow:hidden;text-overflow:ellipsis}h1{font-size:16px}.top-actions{gap:4px}.icon-button{min-width:36px;min-height:38px;padding:6px}}
@media(max-height:340px){#grill{gap:12px;padding:12px}.hero{padding:16px 20px}.hero-value{margin:6px 0}.hero-value>span:first-child{font-size:54px}.gauge{width:115px;height:115px}.hero h2{font-size:16px}.hero .range-summary{margin-top:4px}#pitmasters-section,footer{display:none!important}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}

@media(pointer:coarse){button,input,select{min-height:44px}.field input,.field select{font-size:16px}summary{min-height:44px;display:flex;align-items:center}}

.forecast{margin-top:12px;padding:0;border:0}.forecast-summary{display:flex;align-items:center;gap:8px;min-height:34px;padding:6px 0;list-style:none;color:var(--text);border-radius:6px}.forecast-summary::-webkit-details-marker{display:none}.forecast-title{font-size:12px;font-weight:600;line-height:1.4;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.forecast-eta{margin-left:auto;font-size:10px;color:var(--muted);white-space:nowrap}.forecast-chevron{font-size:14px;color:var(--muted);transition:transform .2s ease}.forecast[open] .forecast-chevron{transform:rotate(180deg)}.forecast-content{padding:6px 0 2px}.forecast:not([open]) .forecast-content{display:none}.hero-forecast{font-size:13px;font-weight:600;line-height:1.5;color:var(--text);margin-top:10px}.forecast-detail{font-size:11px;line-height:1.6;color:var(--muted);overflow-wrap:anywhere}.forecast-chart{display:block;width:100%;height:auto;max-height:120px;margin-top:8px;overflow:visible}.forecast-actual{stroke:var(--accent);stroke-width:2;fill:none;stroke-linejoin:round}.forecast-target{stroke:var(--muted);stroke-width:1;stroke-dasharray:2 4}.forecast-predicted{stroke:var(--accent);stroke-width:1.5;stroke-dasharray:5 4}.forecast-legend{font-size:10px;line-height:1.5;color:var(--muted);margin-top:4px}.forecast-times{display:flex;justify-content:space-between;font-size:10px;color:var(--muted);margin-top:4px}.forecast-reset{min-height:34px;margin-top:8px;font-size:11px;background:none;padding:6px 9px}
.rail{height:7px;border-radius:99px;margin-top:13px;isolation:isolate}.rail>span{position:relative;height:100%;overflow:hidden;border-radius:inherit;background:var(--accent);transition:width .85s cubic-bezier(.22,.61,.36,1)}.rail>span::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,.05) 35%,rgba(255,255,255,.3) 50%,transparent 75%);transform:translateX(-100%);opacity:0}.rail.is-running>span::after{opacity:1;animation:wlt-progress-light 3.6s ease-in-out infinite}@keyframes wlt-progress-light{0%,15%{transform:translateX(-100%)}75%,100%{transform:translateX(100%)}}
.no-animation .rail>span::after{animation:none!important;opacity:0}.no-animation .forecast-chevron{transition:none}@media(prefers-reduced-motion:reduce){.rail>span::after{animation:none!important;opacity:0}.rail>span,.forecast-chevron{transition:none!important}}@media(pointer:coarse){.forecast-summary,.forecast-reset{min-height:44px}}

.hero-forecast{display:flex;flex-direction:column;gap:4px;margin-top:14px;overflow-wrap:anywhere}.hero-forecast-label{font-size:9px;letter-spacing:1.3px;color:var(--muted);font-weight:650}.hero-forecast strong{font-size:13px;font-weight:550;line-height:1.35}.hero-forecast.estimated strong{font-size:clamp(18px,3vw,26px);font-weight:600;color:var(--accent);font-variant-numeric:tabular-nums}.hero-forecast-eta{font-size:11px;color:var(--muted);font-weight:400}
.probe-value.paired-values{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.measurement{min-width:0}.measurement-label{display:block;font-size:11px;font-weight:500;letter-spacing:0;line-height:1.5;color:var(--muted);margin-bottom:5px;overflow-wrap:anywhere}.measurement-reading{font-size:clamp(24px,3vw,32px);line-height:1.25;font-variant-numeric:tabular-nums;letter-spacing:-1px;white-space:nowrap}.paired-values .measurement+.measurement{border-left:1px solid var(--border);padding-left:12px}.measurement-reading small{font-size:12px;margin-left:4px}.probe-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr))}@media(max-width:300px){.probe-value.paired-values{grid-template-columns:1fr;gap:10px}.paired-values .measurement+.measurement{border:0;padding-left:0}}
.brand>div{min-width:0}#tile-title{overflow-wrap:anywhere}.has-header-temperature .topbar{display:grid;grid-template-columns:minmax(0,1fr) minmax(190px,340px) auto;gap:18px}.header-temperature{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:14px;min-width:0;padding:12px 18px;background:var(--surface);border:1px solid var(--border);border-radius:14px}.header-temperature-copy{min-width:0}.header-temperature h2{font-size:13px;line-height:1.4;overflow-wrap:anywhere}.header-temperature-channel{display:block;font-size:10px;line-height:1.5;color:var(--muted);margin-top:3px;overflow-wrap:anywhere}.header-temperature-reading{display:flex;align-items:baseline;gap:5px;flex-shrink:0;font-variant-numeric:tabular-nums}.header-temperature-reading strong{font-size:30px;line-height:1.2;letter-spacing:-1px;font-weight:550}.header-temperature-reading>span{font-size:12px;color:var(--muted)}@media(max-width:700px){.has-header-temperature .topbar{grid-template-columns:minmax(0,1fr) auto;gap:12px}.has-header-temperature .top-actions{grid-column:2;grid-row:1}.header-temperature{grid-column:1/-1;grid-row:2;padding:12px 15px}.header-temperature-reading strong{font-size:28px}}

.header-limits{grid-column:1/-1;width:100%;margin-top:0;padding-top:6px}.header-limits summary{min-height:22px}.header-limits .limits-form{margin-top:6px}.header-temperature{row-gap:6px}
/* Kopfbereich: gleich breite Außenbereiche halten den Temperaturwert wirklich mittig. */
.topbar,.has-header-temperature .topbar{display:grid;grid-template-columns:minmax(0,1fr) minmax(250px,380px) minmax(0,1fr);gap:24px;align-items:center}.brand{gap:14px}.brandmark{width:54px;height:62px;border-radius:18px;border:1px solid color-mix(in srgb,var(--accent) 22%,var(--border));background:linear-gradient(145deg,color-mix(in srgb,var(--accent) 13%,var(--surface)),var(--surface))}.brandmark svg{width:28px;height:32px}#tile-title{font-size:clamp(22px,2.4vw,30px);font-weight:650;line-height:1.2;letter-spacing:-.8px}.header-temperature{display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;width:100%;padding:18px 24px;gap:7px;border-radius:18px;background:radial-gradient(ellipse at top,color-mix(in srgb,var(--accent) 7%,transparent),transparent 80%),var(--surface)}.header-temperature h2{font-size:14px;font-weight:550;color:var(--muted)}.header-temperature-reading{gap:7px}.header-temperature-reading strong{font-size:44px;font-weight:550;letter-spacing:-1.6px}.header-temperature-reading>span{font-size:17px}.header-status{grid-column:3;justify-self:end;display:flex;flex-direction:column;align-items:flex-end;gap:10px;min-width:0;max-width:100%}.header-status .top-actions{gap:9px}.header-status footer{display:flex;justify-content:flex-end;gap:5px 10px;padding:0;max-width:100%;font-size:10px;line-height:1.5}.header-status #device{font-weight:600}.header-status #telemetry{overflow-wrap:anywhere}.header-status #updated{flex-basis:100%;text-align:right;margin:0}.header-status .text-button{min-height:28px;padding:3px 9px;font-size:10px;border-radius:8px}.header-status #bluetooth{margin-left:auto}.no-details .header-status footer{display:none}.probe{display:grid;grid-template-columns:minmax(0,1fr) minmax(130px,160px);column-gap:16px;row-gap:0;align-content:start}.probe-head{grid-column:1/-1;grid-row:1;margin-bottom:8px}.probe>.probe-value{grid-column:1;grid-row:2;align-self:start;margin:12px 0}.probe>.probe-label{grid-column:1/-1;grid-row:3}.probe>.rail{grid-column:1/-1;grid-row:4}.probe>.rail-labels{display:none}.probe>.forecast{grid-column:1/-1;grid-row:5;margin-top:6px}.probe>.probe-limit{grid-column:2;grid-row:2;margin:7px 0 10px;padding:0;border:0;align-self:start;min-width:0}.limit-summary{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:3px 7px;padding:8px 10px;background:var(--raised);border-radius:10px;list-style:none;min-height:56px}.limit-summary::-webkit-details-marker{display:none}.limit-caption{flex-basis:100%;text-align:right;font-size:10px;color:var(--muted)}.limit-summary>span:nth-child(2){font-size:18px;line-height:1.4;font-weight:600;color:var(--text);font-variant-numeric:tabular-nums}.limit-chevron{font-size:12px;color:var(--muted)}.probe-limit[open] .limit-chevron{transform:rotate(180deg)}.probe-limit.read-only .limit-chevron{display:none}.probe-limit.read-only summary{cursor:default}.probe-limit .limits-form{grid-template-columns:minmax(0,1fr);gap:7px;margin-top:8px}.probe-limit .limits-form button{font-size:11px;padding:8px}.probe-limit .field input{font-size:14px}.probe.paired-probe>.paired-values{grid-column:1/-1;grid-row:2;padding-right:0}.probe.paired-probe>.probe-limit{grid-row:3}.probe.paired-probe>.probe-label{grid-row:3;grid-column:1;align-self:center}.probe.paired-probe>.rail{grid-row:4}@media(max-width:940px){.topbar,.has-header-temperature .topbar{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}.header-status{grid-column:2;grid-row:1}.header-status .top-actions{grid-column:auto;grid-row:auto}.header-temperature{grid-column:1/-1;grid-row:2;justify-self:center;max-width:420px}.header-temperature-reading strong{font-size:40px}}@media(max-width:440px){.topbar,.has-header-temperature .topbar{gap:14px 8px}#tile-title{font-size:22px}.brand{gap:9px}.brandmark{width:38px;height:46px;border-radius:13px}.brandmark svg{width:23px;height:27px}.header-status{gap:7px}.header-status footer{font-size:9px;gap:3px 7px}.header-status #telemetry{flex-basis:100%;text-align:right}.header-temperature{padding:17px 20px}.probe{column-gap:12px;grid-template-columns:minmax(0,1fr) minmax(115px,135px)}.limit-summary>span:nth-child(2){font-size:16px}}@media(max-width:300px){.topbar,.has-header-temperature .topbar{grid-template-columns:1fr}.header-status{grid-column:1;grid-row:2;justify-self:stretch;align-items:flex-start}.header-status footer{justify-content:flex-start}.header-status #telemetry,.header-status #updated{text-align:left}.header-temperature{grid-row:3}.probe{grid-template-columns:minmax(0,1fr) 110px}}
/* Grenzwert und Sammlung bilden zwei ruhige Bedienflächen neben dem Messwert. */
.header-status footer{width:100%;justify-content:flex-end}.header-status #telemetry{flex-basis:100%;text-align:right}.header-status #device{margin-left:auto}.header-status #bluetooth{margin-left:0}.probe.has-presets{grid-template-columns:minmax(0,1fr) minmax(95px,115px) minmax(130px,160px);column-gap:12px}.limit-summary{justify-content:center;text-align:center}.limit-caption{text-align:center}.core-presets{grid-column:3;grid-row:2;align-self:start;display:grid;gap:7px;padding:9px 10px;background:var(--raised);border-radius:10px;margin:7px 0 10px;min-width:0}.preset-field{display:grid;gap:6px;min-width:0}.preset-caption{text-align:center;font-size:10px;line-height:1.4;color:var(--muted)}.preset-field select{min-width:0;width:100%;min-height:34px;padding:5px 7px;background:var(--surface);color:var(--text);border:1px solid var(--border);border-radius:7px;font-size:11px}.core-presets>button{min-height:30px;padding:5px 7px;font-size:11px;background:var(--surface);border-radius:7px}.probe.has-presets.paired-probe>.paired-values{grid-column:1/3}.probe.has-presets.paired-probe>.core-presets{grid-row:2/4}.probe.has-presets>.probe-limit{width:100%}.probe-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,440px),1fr))}@media(max-width:520px){.probe.has-presets{grid-template-columns:minmax(0,1fr) minmax(115px,140px)}.probe.has-presets>.core-presets,.probe.has-presets.paired-probe>.core-presets{grid-column:1/-1;grid-row:4;display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:end;margin:8px 0;padding:10px}.probe.has-presets>.rail,.probe.has-presets.paired-probe>.rail{grid-row:5}.probe.has-presets>.forecast{grid-row:6}.probe.has-presets.paired-probe>.paired-values{grid-column:1/-1}.preset-field select{font-size:13px}.core-presets>button{min-height:36px}.preset-caption{text-align:left}}@media(pointer:coarse){.preset-field select,.core-presets>button{min-height:44px}}
.header-status footer{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:5px 10px;text-align:right}.header-status #device{grid-column:1;grid-row:1;margin:0;align-self:center;justify-self:end}.header-status #bluetooth{grid-column:2;grid-row:1;margin:0;justify-self:end}.header-status #telemetry{grid-column:1/-1;grid-row:2;text-align:right}.header-status #updated{grid-column:1/-1;grid-row:3;text-align:right}
/* Sammlung: gleicher ruhiger Wertblock wie die Obergrenze, Bedienung nur aufgeklappt. */
.core-presets{display:block;padding:0;border:0;background:none}.preset-summary{display:grid;grid-template-columns:minmax(0,1fr) auto;align-content:center;gap:3px 5px;min-height:61px;padding:8px 10px;background:var(--raised);border-radius:10px;text-align:center;list-style:none}.preset-summary::-webkit-details-marker{display:none}.preset-summary .preset-caption{grid-column:1/-1;font-size:10px}.preset-value{grid-column:1;font-size:13px;font-weight:550;line-height:1.4;overflow-wrap:anywhere;color:var(--text)}.has-selection .preset-value{font-size:18px;font-variant-numeric:tabular-nums}.preset-name{grid-column:1/-1;grid-row:3;font-size:10px;line-height:1.4;color:var(--muted);overflow-wrap:anywhere}.preset-chevron{grid-column:2;grid-row:2;font-size:12px;color:var(--muted)}.core-presets[open] .preset-chevron{transform:rotate(180deg)}.core-presets.read-only .preset-chevron{display:none}.core-presets.read-only summary{cursor:default}.preset-editor{display:grid;gap:7px;margin-top:8px}.core-presets:not([open]) .preset-editor{display:none}.preset-input-label{font-size:10px;color:var(--muted)}.preset-editor>button{min-height:34px;padding:6px;font-size:11px}.preset-field select{font-size:11px}.brand-images{display:flex;flex-direction:column;gap:7px;flex-shrink:0}.brand-image{width:56px;height:62px;max-width:100%;object-fit:contain;flex-shrink:0}.brand-title{min-width:0}@media(max-width:520px){.probe.has-presets>.core-presets,.probe.has-presets.paired-probe>.core-presets{display:block;padding:0;background:none}.preset-summary{min-height:56px}.brand{flex-wrap:wrap}.brand-image{width:40px;height:46px}}@media(pointer:coarse){.preset-editor>button{min-height:44px}}
:root{--target-alert:#ff625b}:root[data-theme="light"]{--target-alert:#bf2525}@media(prefers-color-scheme:light){:root:not([data-theme="dark"]){--target-alert:#bf2525}}.probe.target-alert:not(.paired-probe)>.probe-value,.probe.target-alert.paired-probe .measurement:first-child .measurement-reading,.hero.target-alert #hero-temperature{color:var(--target-alert)}.probe.target-blink:not(.paired-probe)>.probe-value,.probe.target-blink.paired-probe .measurement:first-child .measurement-reading,.hero.target-blink #hero-temperature{animation:wlt-target-pulse 2s ease-in-out infinite}@keyframes wlt-target-pulse{0%,100%{opacity:1}50%{opacity:.6}}@media(prefers-reduced-motion:reduce){.probe.target-blink>.probe-value,.probe.target-blink .measurement-reading,.hero.target-blink #hero-temperature{animation:none!important}}.probe.has-presets.paired-probe>.core-presets{grid-row:2}.probe.has-presets.paired-probe>.probe-limit{grid-row:3;grid-column:3}.probe.has-presets.paired-probe>.probe-label{grid-row:3;grid-column:1/3}@media(max-width:520px){.probe.has-presets.paired-probe>.probe-limit{grid-column:2;grid-row:3}.probe.has-presets.paired-probe>.probe-label{grid-column:1;grid-row:3}.probe.has-presets.paired-probe>.core-presets{grid-row:4}}
.probe-section-heading{flex-wrap:wrap;align-items:center;gap:12px 18px}.wired-toolbar{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:7px;margin-left:auto}.wired-toggle{display:inline-flex;align-items:center;gap:7px;min-height:36px;padding:6px 10px;border:1px solid color-mix(in srgb,var(--accent) 35%,var(--border));border-radius:10px;background:color-mix(in srgb,var(--accent) 8%,var(--surface));color:var(--text);font-size:11px;line-height:1.4}.wired-toggle-icon{width:16px;height:16px;color:var(--accent);stroke-width:1.6}.wired-number{font-size:10px;font-weight:700;font-variant-numeric:tabular-nums}.wired-name{max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.wired-toggle.is-hidden{background:var(--surface);border-color:var(--border);color:var(--muted)}.wired-toggle.is-hidden .wired-toggle-icon{color:var(--muted)}.wired-toggle[aria-pressed="true"] .wired-number{color:var(--accent)}#wired-empty{padding:3px 0 12px}@media(max-width:520px){.wired-toolbar{justify-content:flex-start;margin-left:0;width:100%}.wired-name{max-width:125px}}@media(pointer:coarse){.wired-toggle{min-height:44px}}
/* Direktwahl: das native Select liegt auf der Wertfläche, ohne vorgelagertes Aufklappen. */
.preset-summary{position:relative;cursor:pointer}.preset-direct-select{position:absolute;inset:0;width:100%;height:100%;min-height:100%;opacity:0;cursor:pointer;padding:0;border:0;font-size:16px}.preset-direct-select:disabled{cursor:default}.preset-direct-select option{background:var(--surface);color:var(--text)}.preset-summary:focus-within{outline:3px solid var(--accent);outline-offset:3px}.core-presets .preset-editor:not([hidden]){display:block}.preset-editor>button{width:100%}.brand{gap:16px}.brand-title{flex:1 1 auto}.brand-images{gap:10px}.brand-image{width:80px;height:88px}.wired-name{max-width:170px}@media(min-width:941px){.topbar,.has-header-temperature .topbar{grid-template-columns:minmax(0,1.25fr) minmax(250px,380px) minmax(0,.75fr)}}@media(max-width:520px){.brand-image{width:54px;height:60px}.brand{gap:10px}.brand-images{gap:7px}}
/* Titelbilder flankieren den Messwert; die Mitte bleibt für Name und Temperatur frei. */
.header-temperature{display:grid;grid-template-columns:minmax(0,1fr);gap:12px;padding:14px 18px;min-height:116px}.header-temperature-main{grid-column:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;min-width:0}.header-temperature.has-header-images{grid-template-columns:80px minmax(0,1fr) 80px}.has-header-images .header-temperature-main{grid-column:2}#header-image-left{grid-column:1;grid-row:1}#header-image-right{grid-column:3;grid-row:1}.header-temperature-main{grid-row:1}.header-image-slot{display:flex;justify-content:center;align-items:center;min-width:0}.header-image-slot .brand-image{width:80px;height:88px}.header-temperature h2{overflow-wrap:anywhere}.header-image-slot .brand-images{align-items:center}.header-temperature:not(.has-header-images) .header-image-slot{display:none}.brand-title{flex:1 1 auto}@media(max-width:520px){.header-temperature.has-header-images{grid-template-columns:54px minmax(0,1fr) 54px;gap:8px;padding:14px}.header-image-slot .brand-image{width:54px;height:64px}.header-temperature-reading strong{font-size:36px}.header-temperature-reading>span{font-size:14px}}
/* Kompakter Kopf: Titel/Verbindung links, Messwert mittig, Fühlerwahl/Status rechts. */
#grill{gap:18px}.topbar,.has-header-temperature .topbar{align-items:start}.header-brand-column{grid-column:1;grid-row:1;display:flex;flex-direction:column;align-items:flex-start;gap:14px;min-width:0;padding-top:5px}.header-brand-column .top-actions{justify-content:flex-start;gap:10px}.header-status{grid-column:3;grid-row:1;gap:10px;align-self:start}.header-temperature{align-self:start}.probe-section-heading{margin-bottom:12px}.header-status .wired-toolbar{justify-content:flex-end;margin:0;width:100%}.wired-selector{position:relative;min-width:0;width:100%;margin:0;padding:0;border:0}.wired-selector:not(.is-menu){max-width:100%}.wired-selector-toggle{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:38px;padding:8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:10px;list-style:none;color:var(--text);font-size:12px;font-weight:550}.wired-selector-toggle::-webkit-details-marker{display:none}.wired-selector-toggle>span{color:var(--muted)}.wired-selector.is-menu[open]>.wired-selector-toggle>span{transform:rotate(180deg)}.wired-selector.is-menu:not([open])>.wired-toolbar{display:none}.wired-selector.is-menu[open]>.wired-toolbar{position:absolute;z-index:30;right:0;top:calc(100% + 8px);display:grid;grid-template-columns:repeat(2,minmax(0,1fr));width:min(340px,calc(100vw - 40px));padding:10px;border:1px solid var(--border);border-radius:12px;background:var(--surface);box-shadow:0 12px 32px #0005}.wired-selector .wired-toggle{min-width:0}.wired-selector.is-menu .wired-name{min-width:0;max-width:100%}.wired-selector:not(.is-menu) .wired-name{max-width:140px}@media(max-width:940px){.header-brand-column{grid-column:1;grid-row:1}.header-status{grid-column:2;grid-row:1}.header-brand-column .top-actions{grid-column:auto;grid-row:auto}.header-temperature{grid-column:1/-1;grid-row:2}}@media(max-width:300px){.header-status{grid-column:1;grid-row:2;justify-self:stretch;align-items:flex-end}.header-temperature{grid-row:3}.header-brand-column{grid-column:1;grid-row:1}}@media(pointer:coarse){.wired-selector-toggle{min-height:44px}}
.wired-selector.is-multi-row>.wired-toolbar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.wired-selector.is-multi-row .wired-name{min-width:0;max-width:100%}.wired-selector.is-multi-row .wired-toggle{justify-content:flex-start}
````

## WLANThermo/tile.js

````javascript
'use strict';
(() => {
  const $ = id => document.getElementById(id);
  let state = {}, selected = 0, pending = null, pendingAction = '', timeout = null;
  const probes = new Map(), pitmasters = new Map(), wiredButtons = new Map();
  const fmt = value => typeof value === 'number' && Number.isFinite(value) ? new Intl.NumberFormat('de-DE', {maximumFractionDigits:1}).format(value) : '—';
  const node = (tag, cls, text) => { const el = document.createElement(tag); if (cls) el.className = cls; if (text !== undefined) el.textContent = text; return el; };
  let noticeTimer = null, noticeGeneration = 0, wiredMenuMode = false;
  const announce = (message, error = false) => {
    if (noticeTimer !== null) clearTimeout(noticeTimer);
    const generation = ++noticeGeneration;
    $('notice').textContent = message; $('notice').className = error ? 'error' : ''; $('notice').hidden = !message;
    if (message) noticeTimer = setTimeout(() => { if (generation === noticeGeneration) { $('notice').hidden = true; $('notice').textContent = ''; noticeTimer = null; } }, error ? 8000 : 4000);
  };
  const fraction = ch => state.config?.forecast !== false && Number.isFinite(ch.forecast?.progress) ? ch.forecast.progress : ch.valid && Number.isFinite(ch.max) && ch.max > 0 ? Math.max(0, Math.min(1, ch.temperature / ch.max)) : 0;
  const aboveMaximum = ch => ch.valid && Number.isFinite(ch.max) && ch.temperature > ch.max;
  const forecastIsRunning = ch => ch.valid && ch.forecast?.status === 'estimated' && ch.temperature < ch.max;
  const describe = ch => !ch.valid ? 'Kein aktueller Messwert' : !Number.isFinite(ch.max) ? 'Keine Obergrenze verfügbar' : ch.temperature > ch.max ? 'Obergrenze überschritten' : ch.temperature === ch.max ? 'Obergrenze erreicht' : 'Noch ' + fmt(ch.max - ch.temperature) + ' ' + state.unit + ' bis zur Obergrenze';
  const forecastText = forecast => {
    if (!forecast) return 'Temperaturverlauf wird gesammelt …';
    if (forecast.status === 'estimated' && Number.isFinite(forecast.seconds)) {
      if (forecast.seconds < 60) return 'Noch weniger als 1 Min.';
      const minutes = Math.max(1, Math.round(forecast.seconds / 60));
      const duration = minutes >= 60 ? Math.floor(minutes / 60) + ' Std. ' + (minutes % 60) + ' Min.' : minutes + ' Min.';
      return 'Noch ca. ' + duration;
    }
    return ({reached:'Obergrenze erreicht', learning:'Temperaturverlauf wird gesammelt …', stalled:'Temperaturplateau – keine Restzeitschätzung', cooling:'Temperatur fällt – keine Restzeitschätzung', unstable:'Schwankender Verlauf – keine verlässliche Restzeit', distant:'Ziel zu weit entfernt – noch keine verlässliche Restzeit', stale:'Messverlauf nicht aktuell', no_target:'Keine Obergrenze verfügbar', disabled:''})[forecast.status] || 'Noch keine verlässliche Restzeit';
  };
  function forecastElements(number) {
    const el = node('details', 'forecast'); el.setAttribute('aria-label', 'Restzeitschätzung und Temperaturverlauf');
    const summary = node('summary', 'forecast-summary'), title = node('span', 'forecast-title'), eta = node('span', 'forecast-eta'), chevron = node('span', 'forecast-chevron', '⌄');
    chevron.setAttribute('aria-hidden', 'true'); summary.append(title, eta, chevron);
    const content = node('div', 'forecast-content'), detail = node('p', 'forecast-detail');
    const svgNode = tag => document.createElementNS('http://www.w3.org/2000/svg', tag);
    const chart = svgNode('svg'); chart.setAttribute('viewBox', '0 0 320 104'); chart.setAttribute('role', 'img'); chart.classList.add('forecast-chart');
    const target = svgNode('line'), actual = svgNode('polyline'), predicted = svgNode('line');
    target.setAttribute('class', 'forecast-target'); actual.setAttribute('class', 'forecast-actual'); predicted.setAttribute('class', 'forecast-predicted');
    chart.append(target, actual, predicted);
    const legend = node('div', 'forecast-legend', 'Messverlauf · Trend gestrichelt · Obergrenze punktiert');
    const times = node('div', 'forecast-times'), startTime = node('span'), endTime = node('span'); times.append(startTime, endTime);
    const reset = node('button', 'forecast-reset', 'Verlauf neu starten'); reset.type = 'button'; reset.setAttribute('aria-label', 'Temperaturverlauf für Kanal ' + number + ' neu starten');
    reset.addEventListener('click', () => send('forecastReset', {number}));
    content.append(detail, chart, legend, times, reset); el.append(summary, content);
    return {el, summary, title, eta, content, detail, chart, target, actual, predicted, legend, times, startTime, endTime, reset};
  }
  function renderForecast(ui, ch, cfg) {
    const forecast = ch.forecast, enabled = cfg.forecast !== false;
    ui.el.hidden = !enabled;
    if (!enabled) return;
    const compact = {learning:'Restzeit wird ermittelt',stalled:'Temperaturplateau',cooling:'Temperatur fällt',unstable:'Schätzung unsicher',distant:'Restzeit noch offen',stale:'Kein aktueller Trend',no_target:'Kein Zielwert',reached:'Obergrenze erreicht'};
    ui.title.textContent = forecast?.status === 'estimated' ? forecastText(forecast).replace('Noch ca. ', 'ca. ').replace('Noch weniger als ', '< ') : compact[forecast?.status] || 'Restzeit wird ermittelt';
    ui.summary.setAttribute('aria-label', forecastText(forecast) + '. Verlauf und Details ein- oder ausklappen.');
    const estimated = forecast?.status === 'estimated' && Number.isFinite(forecast.eta);
    ui.eta.textContent = estimated ? 'gegen ' + new Date(forecast.eta * 1000).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'}) : 'Details';
    const details = [forecastText(forecast)];
    if (estimated) details.push('Ziel voraussichtlich gegen ' + new Date(forecast.eta * 1000).toLocaleTimeString('de-DE', {hour:'2-digit', minute:'2-digit'}) + ' · Trend-Schätzung');
    if (Number.isFinite(forecast?.rate)) details.push((forecast.rate > 0 ? '+' : '') + new Intl.NumberFormat('de-DE', {maximumFractionDigits:2}).format(forecast.rate) + ' ' + state.unit + '/min');
    if (!forecast || forecast.status === 'learning') details.push('Mindestens 3 Minuten und 6 gültige Messpunkte erforderlich.');
    ui.detail.textContent = details.join(' · ');
    ui.reset.hidden = !cfg.controls; ui.reset.disabled = Boolean(pending) || !state.online;
    const points = (forecast?.points || []).filter(p => Array.isArray(p) && Number.isFinite(p[0]) && Number.isFinite(p[1]));
    const visible = cfg.forecastChart !== false && points.length >= 2 && Number.isFinite(ch.max);
    ui.chart.hidden = ui.legend.hidden = ui.times.hidden = !visible;
    ui.chart.style.display = visible ? 'block' : 'none';
    ui.chart.setAttribute('aria-hidden', String(!visible));
    if (!visible) return;
    const first = points[0], last = points[points.length - 1];
    const horizon = estimated ? Math.min(forecast.seconds, Math.max(300, last[0] - first[0])) : 0;
    const end = last[0] + horizon;
    const nextValue = estimated ? Math.min(ch.max, last[1] + forecast.rate * horizon / 60) : last[1];
    const values = points.map(p => p[1]).concat([ch.max, nextValue]);
    const bottom = Math.min(...values), top = Math.max(...values), pad = Math.max(0.5, (top - bottom) * 0.12);
    const x = time => 8 + 304 * (time - first[0]) / Math.max(1, end - first[0]);
    const y = value => 96 - 88 * (value - bottom + pad) / Math.max(1, top - bottom + 2 * pad);
    ui.actual.setAttribute('points', points.map(p => x(p[0]).toFixed(2) + ',' + y(p[1]).toFixed(2)).join(' '));
    for (const [key, val] of Object.entries({x1:8,x2:312,y1:y(ch.max),y2:y(ch.max)})) ui.target.setAttribute(key, val);
    ui.predicted.hidden = !estimated;
    ui.predicted.setAttribute('visibility', estimated ? 'visible' : 'hidden');
    if (estimated) for (const [key, val] of Object.entries({x1:x(last[0]),y1:y(last[1]),x2:x(end),y2:y(nextValue)})) ui.predicted.setAttribute(key, val);
    ui.chart.setAttribute('aria-label', 'Gemessener Temperaturverlauf bis ' + fmt(ch.temperature) + ' ' + state.unit + ', Obergrenze ' + fmt(ch.max) + ' ' + state.unit + (estimated ? ', gestrichelte Trendfortschreibung' : ''));
    const clock = time => new Date(time * 1000).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
    ui.startTime.textContent = clock(first[0]); ui.endTime.textContent = clock(end) + (estimated ? ' · Trend' : '');
  }
  function send(action, values = {}) {
    if (pending) return;
    if (typeof window.requestAction !== 'function') { announce('Bedienung ist nur in der verbundenen Symcon-Visualisierung verfügbar.', true); return; }
    const requestId = 'wlt_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
    pending = requestId;
    pendingAction = action;
    announce(action === 'refresh' ? 'Messwerte werden abgerufen …' : action === 'visibility' ? 'Fühleranzeige wird gespeichert …' : 'Änderung wird am Gerät geprüft …');
    render();
    timeout = setTimeout(() => { pending = null; pendingAction = ''; announce('Keine Bestätigung erhalten. Vor erneutem Bedienen bitte aktualisieren.', true); render(); }, state.config?.actionTimeout || 35000);
    try { window.requestAction('TileAction', JSON.stringify({requestId, action, ...values})); }
    catch (error) { clearTimeout(timeout); pending = null; pendingAction = ''; announce('Anfrage konnte nicht gesendet werden.', true); render(); }
  }
  function field(label, type, min, max) {
    const wrapper = node('label', 'field'); wrapper.append(node('span', '', label));
    const input = node('input'); input.type = type; input.step = '1'; input.min = String(min); input.max = String(max); input.required = true;
    wrapper.append(input); return {wrapper, input};
  }
  function probe(ch) {
    const el = node('article', 'probe'); el.tabIndex = 0; el.setAttribute('aria-label', 'Kanal ' + ch.number);
    const head = node('div', 'probe-head'), names = node('div'), name = node('h3');
    names.append(name); head.append(names, node('span', 'probe-status'));
    const value = node('div', 'probe-value'), label = node('div', 'probe-label'), rail = node('div', 'rail'), bar = node('span'), limits = node('div', 'rail-labels'), high = node('span');
    rail.append(bar); limits.append(high);
    const details = node('details', 'probe-limit'), summary = node('summary', 'limit-summary'), caption = node('span', 'limit-caption', 'Obergrenze'), chevron = node('span', 'limit-chevron', '⌄'), form = node('form', 'limits-form');
    chevron.setAttribute('aria-hidden', 'true'); summary.append(caption, high, chevron); limits.hidden = true;
    const max = field('Obergrenze', 'number', -30, 999), submit = node('button', '', 'Obergrenze übernehmen'); submit.type = 'submit';
    form.append(max.wrapper, submit); details.append(summary, form);
    const forecast = forecastElements(ch.number);
    const presetBox = node('div', 'core-presets'), presetSummary = node('label', 'preset-summary'), presetValue = node('strong', 'preset-value', 'Wählen …'), presetName = node('span', 'preset-name'), presetChevron = node('span', 'preset-chevron', '⌄');
    presetChevron.setAttribute('aria-hidden', 'true'); presetSummary.append(node('span', 'preset-caption', 'Vorgaben'), presetValue, presetName, presetChevron);
    const presetEditor = node('div', 'preset-editor'), presetSelect = node('select'), presetApply = node('button', '', 'Übernehmen');
    presetSelect.className = 'preset-direct-select'; presetSummary.append(presetSelect); presetApply.type = 'button'; presetEditor.append(presetApply); presetBox.append(presetSummary, presetEditor);
    presetSelect.setAttribute('aria-label', 'Kerntemperatur-Vorlage für Kanal ' + ch.number);
    const presetKey = item => JSON.stringify([item.name, item.temperature]);
    const selectedPreset = () => (state.config?.corePresets || []).find(item => presetKey(item) === presetSelect.value);
    presetSelect.addEventListener('change', () => { const ui = probes.get(ch.number); if (ui) ui.presetDirty = true; render(); });
    presetApply.addEventListener('click', () => {
      const item = selectedPreset();
      if (item && !presetApply.disabled && state.canWrite && state.config?.controls) send('channel', {number:ch.number, max:item.temperature, preset:item});
    });
    el.append(head, value, label, rail, limits, forecast.el, details, presetBox);
    summary.addEventListener('click', event => { if (pending || !state.canWrite || !state.config?.controls) event.preventDefault(); });
    const choose = () => { if (state.config?.hero && !state.config?.primary) { selected = ch.number; render(); } };
    head.addEventListener('click', choose); value.addEventListener('click', choose);
    el.addEventListener('keydown', event => { if (event.target === el && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); choose(); } });
    form.addEventListener('submit', event => { event.preventDefault(); if (form.reportValidity()) send('channel', {number:ch.number, max:Number(max.input.value)}); });
    return {el, name, value, label, bar, rail, high, details, forecast, max:max.input, submit, presetBox, presetSelect, presetApply, presetKey, selectedPreset, presetSignature:null, presetValue, presetName, presetEditor, presetDirty:false};
  }
  function pitmaster(pm) {
    const el = node('article', 'pitmaster'), heading = node('div', 'pm-heading'), title = node('h3', '', 'Pitmaster ' + pm.id), meta = node('span', 'pm-meta'); heading.append(title, meta);
    const form = node('form', 'pm-fields'), label = node('label', 'field'); label.append(node('span', '', 'Betriebsart'));
    const mode = node('select'); for (const [value, caption] of [['off','Aus'],['auto','Automatisch'],['manual','Manuell']]) { const option = node('option', '', caption); option.value = value; mode.append(option); }
    label.append(mode);
    const target = field('Solltemperatur', 'number', 0, 999), output = field('Stellwert in %', 'number', 0, 100), submit = node('button', 'submit', 'Am Gerät übernehmen'); submit.type = 'submit';
    form.append(label, target.wrapper, output.wrapper, submit); el.append(heading, form);
    const sync = () => { target.wrapper.hidden = mode.value !== 'auto'; output.wrapper.hidden = mode.value !== 'manual'; target.input.disabled = mode.disabled || mode.value !== 'auto'; output.input.disabled = mode.disabled || mode.value !== 'manual'; };
    mode.addEventListener('change', () => { form.dataset.dirty = 'true'; sync(); });
    form.addEventListener('input', () => { form.dataset.dirty = 'true'; });
    form.addEventListener('submit', event => { event.preventDefault(); if (!form.reportValidity()) return; const values = {id:pm.id, mode:mode.value}; if (mode.value === 'auto') values.target = Number(target.input.value); if (mode.value === 'manual') values.output = Number(output.input.value); send('pitmaster', values); });
    return {el, meta, form, mode, target:target.input, targetLabel:target.wrapper.firstChild, output:output.input, submit, sync};
  }
  function render() {
    const cfg = state.config || {}, active = state.enabled !== false, editable = active && state.canWrite && !pending;
    const preservingVisibility = Boolean(pending && pendingAction === 'visibility');
    document.documentElement.dataset.theme = cfg.theme || 'auto';
    document.documentElement.style.setProperty('--accent', /^#[0-9a-f]{6}$/i.test(cfg.accent || '') ? cfg.accent : '#f48941');
    document.body.classList.toggle('compact', Boolean(cfg.compact)); document.body.classList.toggle('no-icons', cfg.icons === false); document.body.classList.toggle('no-details', cfg.details === false); document.body.classList.toggle('no-animation', cfg.animations === false); document.body.classList.toggle('disabled', !active); document.body.classList.toggle('busy', Boolean(pending));
    $('refresh').disabled = !active || !state.canRefresh || Boolean(pending);
    $('connection').textContent = !active ? 'Kachel deaktiviert' : !state.active ? 'Deaktiviert' : state.online ? 'Verbunden' : state.status >= 200 ? 'Fehler' : 'Standby';
    $('connection').classList.toggle('online', Boolean(active && state.online));
    const channels = active && state.online ? (state.channels || []).filter(ch => ch.valid === true && Number.isFinite(ch.temperature) && ch.temperature !== 999) : [];
    const wired = active && state.online ? state.wiredChannels || [] : [];
    for (const [number, ui] of wiredButtons) if (!wired.some(ch => ch.number === number)) { ui.button.remove(); wiredButtons.delete(number); }
    for (const ch of wired) {
      let ui = wiredButtons.get(ch.number);
      if (!ui) {
        const button = node('button', 'wired-toggle'), name = node('span', 'wired-name'); button.type = 'button';
        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); icon.setAttribute('viewBox','0 0 24 24'); icon.setAttribute('aria-hidden','true'); icon.setAttribute('class','wired-toggle-icon icon');
        const eye = document.createElementNS('http://www.w3.org/2000/svg', 'path'); eye.setAttribute('d','M2 12s3-6 10-6 10 6 10 6-3 6-10 6S2 12 2 12Z');
        const pupil = document.createElementNS('http://www.w3.org/2000/svg', 'circle'); pupil.setAttribute('cx','12'); pupil.setAttribute('cy','12'); pupil.setAttribute('r','2.5');
        const strike = document.createElementNS('http://www.w3.org/2000/svg', 'path'); strike.setAttribute('d','M3 3l18 18'); icon.append(eye,pupil,strike);
        button.append(icon,name);
        button.addEventListener('click', () => { const current = (state.wiredChannels || []).find(item => item.number === ch.number); if (current && !button.disabled) send('visibility',{number:ch.number,visible:!current.visible}); });
        ui = {button,name,strike}; wiredButtons.set(ch.number,ui); $('wired-toolbar').append(button);
      }
      ui.name.textContent = ch.name;
      ui.button.setAttribute('aria-pressed',String(ch.visible));
      ui.button.setAttribute('aria-label','Kanal ' + ch.number + ' · ' + ch.name + (ch.visible ? ' ausblenden' : ' einblenden'));
      ui.button.setAttribute('title',ch.name + (ch.visible ? ' ausblenden' : ' einblenden'));
      ui.button.classList.toggle('is-hidden',!ch.visible); ui.strike.setAttribute('visibility',ch.visible ? 'hidden' : 'visible'); ui.button.disabled = Boolean(pending);
    }
    $('wired-toolbar').hidden = wired.length === 0;
    $('wired-selector').hidden = wired.length === 0;
    const useWiredMenu = wired.length >= 5;
    if (useWiredMenu !== wiredMenuMode) { $('wired-selector').open = !useWiredMenu; wiredMenuMode = useWiredMenu; }
    else if (!useWiredMenu) $('wired-selector').open = true;
    $('wired-selector').classList.toggle('is-menu', useWiredMenu);
    $('wired-selector').classList.toggle('is-multi-row', !useWiredMenu && wired.length >= 3);
    $('wired-selector-toggle').hidden = !useWiredMenu;

    const headerChannel = channels.find(ch => ch.number === cfg.headerChannel);
    const showHeader = cfg.headerEnabled === true && Boolean(headerChannel);
    $('tile-title').textContent = cfg.title || 'Grillstation';
    const images = cfg.brandImages || ['',''], stacked = cfg.brandLayout === 'stacked';
    const showImage = (id, source) => { const image = $(id); image.hidden = !source; if (source) { if (image.src !== source) image.src = source; } else image.removeAttribute('src'); };
    showImage('brand-image-1', images[0]); showImage('brand-image-2-stack', stacked ? images[1] : ''); showImage('brand-image-2-after', stacked ? '' : images[1]);
    $('brand-images').hidden = !images[0] && !(stacked && images[1]);
    $('brand-default').hidden = Boolean(images[0] || images[1]);
    const leading = $('brand-images'), trailing = $('brand-image-2-after');
    if (showHeader) {
      if (leading.parentElement !== $('header-image-left')) $('header-image-left').append(leading);
      if (trailing.parentElement !== $('header-image-right')) $('header-image-right').append(trailing);
    } else {
      if (leading.parentElement !== $('brand')) $('brand').insertBefore(leading, $('brand-title'));
      if (trailing.parentElement !== $('brand')) $('brand').append(trailing);
    }
    $('header-temperature').classList.toggle('has-header-images', Boolean(images[0] || images[1]));


    $('grill').setAttribute('aria-label', 'WLANThermo · ' + (cfg.title || 'Grillstation'));
    $('header-temperature').hidden = !showHeader;
    document.body.classList.toggle('has-header-temperature', showHeader);
    $('header-temperature-name').textContent = cfg.headerName || 'Grilltemperatur';
    $('header-temperature').setAttribute('aria-label', cfg.headerName || 'Grilltemperatur');
    $('header-temperature-value').textContent = fmt(headerChannel?.temperature);
    $('header-temperature-unit').textContent = state.unit || '°C';
    if (!channels.some(ch => ch.number === selected)) selected = cfg.primary > 0 ? (channels.some(ch => ch.number === cfg.primary) ? cfg.primary : 0) : channels[0]?.number || 0;
    const main = channels.find(ch => ch.number === (cfg.primary > 0 ? cfg.primary : selected));
    $('hero').hidden = !cfg.hero || !main;
    $('hero').classList.toggle('target-alert', Boolean(main?.targetAlert));
    $('hero').classList.toggle('target-blink', Boolean(main?.targetAlert && cfg.alertBlink));
    document.body.classList.toggle('has-hero', !$('hero').hidden);
    $('empty-state').hidden = !active || channels.length > 0 || wired.length > 0;
    $('empty-state').textContent = state.online ? 'Keine gültigen Fühlerwerte verfügbar. Nicht angeschlossene Fühler werden automatisch ausgeblendet.' : 'Keine aktuellen Fühlerwerte – Gerät nicht erreichbar oder noch kein erfolgreicher Abruf.';
    $('hero-channel').textContent = main ? 'KANAL ' + main.number + (main.wireless ? ' · BLUETOOTH' : ' · FÜHLER') : 'TEMPERATUR';
    $('hero-name').textContent = main?.displayName || (main?.sensorRole ? main.probeName + ' · ' + (main.sensorRole === 'core' ? 'Kerntemperatur' : 'Garraumtemperatur') : main?.name || (state.online ? 'Keine Fühler ausgewählt' : 'Noch keine Messwerte'));
    $('hero-temperature').textContent = fmt(main?.valid ? main.temperature : null); $('hero-unit').textContent = state.unit || '°C';
    const mainAmbient = main?.sensorRole === 'ambient';
    $('hero-state').textContent = !active ? 'Kachel in der Instanz deaktiviert.' : mainAmbient ? 'Garraumtemperatur · Messwert' : main ? describe(main) : 'Auf einen erfolgreichen Datenabruf warten.';
    $('hero-state').classList.toggle('warning', !main?.valid || Boolean(main && !mainAmbient && aboveMaximum(main)));
    $('hero-forecast').hidden = !main || mainAmbient || cfg.forecast === false;
    const mainEstimated = main?.forecast?.status === 'estimated' && Number.isFinite(main.forecast.seconds);
    $('hero-forecast').classList.toggle('estimated', mainEstimated);
    $('hero-forecast-value').textContent = main ? forecastText(main.forecast) : '';
    $('hero-forecast-eta').hidden = !mainEstimated || !Number.isFinite(main?.forecast?.eta);
    $('hero-forecast-eta').textContent = mainEstimated && Number.isFinite(main.forecast.eta) ? 'Obergrenze voraussichtlich gegen ' + new Date(main.forecast.eta * 1000).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'}) : '';
    $('hero-range').textContent = main ? 'Obergrenze ' + fmt(main.max) + ' ' + state.unit : '';
    $('hero-range').hidden = mainAmbient;
    $('gauge-range').textContent = main ? fmt(main.max) + ' ' + state.unit : '—';
    $('gauge').hidden = !main || mainAmbient || !Number.isFinite(main.max) || (main.max <= 0 && !Number.isFinite(main.forecast?.progress));
    $('gauge').style.setProperty('--progress', ((main ? fraction(main) : 0) * 360) + 'deg');
    $('gauge').setAttribute('aria-label', main && cfg.forecast !== false && Number.isFinite(main.forecast?.progress) ? 'Temperaturfortschritt seit Start: ' + Math.round(main.forecast.progress * 100) + ' Prozent' : main ? 'Temperatur im Verhältnis zur Obergrenze: ' + describe(main) : 'Keine Obergrenze verfügbar');
    const overview = channels.filter(ch => !showHeader || ch.number !== headerChannel.number);
    const cores = new Map(overview.filter(ch => ch.sensorRole === 'core' && ch.pairKey).map(ch => [ch.pairKey, ch]));
    const ambientSensors = new Map(overview.filter(ch => ch.sensorRole === 'ambient' && ch.pairKey).map(ch => [ch.pairKey, ch]));
    const cards = overview.filter(ch => !(ch.sensorRole === 'ambient' && ch.pairKey && cores.has(ch.pairKey)));
    for (const [number, ui] of probes) if (!cards.some(ch => ch.number === number)) { ui.el.remove(); probes.delete(number); }
    cards.forEach((ch, index) => {
      const ambient = ch.sensorRole === 'core' && ch.pairKey ? ambientSensors.get(ch.pairKey) : null;
      const ambientOnly = ch.sensorRole === 'ambient';
      let ui = probes.get(ch.number); if (!ui) { ui = probe(ch); probes.set(ch.number, ui); $('channels').insertBefore(ui.el, $('channels').children[index] || null); }
      ui.el.tabIndex = cfg.hero && !cfg.primary ? 0 : -1;
      ui.el.className = 'probe' + (ambient ? ' paired-probe' : '') + (cfg.hero && !cfg.primary ? ' choose' : '') + (cfg.hero && (main?.number === ch.number || (ambient && main?.number === ambient.number)) ? ' selected' : '') + (!ch.valid ? ' invalid' : !ambientOnly && aboveMaximum(ch) ? ' warning' : '');
      ui.name.textContent = ch.displayName || ch.probeName || ch.name;
      ui.el.setAttribute('aria-label', (ch.displayName || ch.probeName || ch.name) + ', Kanal ' + ch.number + (ambient ? ' und ' + ambient.number : ''));
      ui.value.classList.toggle('paired-values', Boolean(ambient));
      if (ch.sensorRole) {
        const measurement = (sensor, caption) => {
          const block = node('div', 'measurement'), title = node('span', 'measurement-label', caption), reading = node('div', 'measurement-reading');
          reading.append(document.createTextNode(fmt(sensor.temperature)), node('small', '', state.unit)); block.append(title, reading); return block;
        };
        ui.value.replaceChildren(measurement(ch, ambientOnly ? 'Garraumtemperatur' : 'Kerntemperatur'));
        if (ambient) ui.value.append(measurement(ambient, 'Garraumtemperatur'));
      } else ui.value.replaceChildren(document.createTextNode(fmt(ch.valid ? ch.temperature : null)), node('small', '', state.unit));
      ui.el.classList.toggle('target-alert', Boolean(ch.targetAlert));
      ui.el.classList.toggle('target-blink', Boolean(ch.targetAlert && cfg.alertBlink));
      ui.rail.classList.toggle('is-running', forecastIsRunning(ch));
      ui.label.textContent = describe(ch); ui.bar.style.width = fraction(ch) * 100 + '%'; ui.rail.setAttribute('aria-label', Number.isFinite(ch.forecast?.progress) ? 'Temperaturfortschritt seit Start: ' + Math.round(ch.forecast.progress * 100) + ' Prozent' : 'Temperatur im Verhältnis zur Obergrenze'); ui.rail.hidden = !Number.isFinite(ch.max) || (ch.max <= 0 && !Number.isFinite(ch.forecast?.progress)); ui.high.textContent = fmt(ch.max) + ' ' + state.unit;
      renderForecast(ui.forecast, ch, cfg);
      if (ambientOnly) { ui.label.textContent = 'Garraumtemperatur · Messwert'; ui.rail.hidden = true; ui.high.textContent = ''; ui.forecast.el.hidden = true; }
      ui.details.hidden = ambientOnly || !Number.isFinite(ch.max);
      ui.details.classList.toggle('read-only', !editable);
      ui.details.children[0].setAttribute('aria-disabled', String(!editable));
      ui.details.children[0].setAttribute('aria-label', 'Obergrenze ' + fmt(ch.max) + ' ' + state.unit + (editable ? ', zum Ändern öffnen' : ', schreibgeschützt'));
      ui.details.children[1].hidden = !editable && !preservingVisibility;
      if (!editable && !preservingVisibility) ui.details.open = false;
      // Offene Eingaben werden von eintreffenden Messwerten nicht überschrieben.
      if (!ui.details.open) { ui.max.value = ch.max ?? ''; }
      ui.max.disabled = ui.submit.disabled = !editable;
      const presets = cfg.corePresets || [];
      const presetSignature = JSON.stringify(presets);
      if (ui.presetSignature !== presetSignature) {
        const choice = ui.presetSelect.value;
        const placeholder = node('option', '', 'Vorlage wählen …'); placeholder.value = '';
        ui.presetSelect.replaceChildren(placeholder);
        for (const item of presets) { const option = node('option', '', item.name + ' · ' + fmt(item.temperature) + ' ' + state.unit); option.value = ui.presetKey(item); ui.presetSelect.append(option); }
        ui.presetSelect.value = presets.some(item => ui.presetKey(item) === choice) ? choice : '';
        ui.presetSignature = presetSignature;
      }
      if (!ui.presetDirty) { ui.presetSelect.value = ch.selectedPreset && presets.some(item => ui.presetKey(item) === ui.presetKey(ch.selectedPreset)) ? ui.presetKey(ch.selectedPreset) : ''; }
      const displayedPreset = ui.selectedPreset();
      ui.presetValue.textContent = displayedPreset ? fmt(displayedPreset.temperature) + ' ' + state.unit : 'Wählen …';
      ui.presetName.textContent = displayedPreset?.name || ''; ui.presetName.hidden = !displayedPreset;
      ui.presetBox.classList.toggle('has-selection', Boolean(displayedPreset));
      ui.presetBox.classList.toggle('read-only', !editable);
      ui.presetSelect.setAttribute('aria-disabled', String(!editable));
      ui.presetEditor.hidden = (!editable && !preservingVisibility) || !ui.presetDirty || !displayedPreset;
      ui.presetBox.hidden = ambientOnly || cfg.controls === false || presets.length === 0;
      ui.el.classList.toggle('has-presets', !ui.presetBox.hidden);
      ui.presetSelect.disabled = !editable;
      ui.presetApply.disabled = !editable || !ui.selectedPreset();
    });
    $('channels-section').hidden = cards.length === 0 && wired.length === 0;
    $('wired-empty').hidden = cards.length > 0 || wired.length === 0;
    const pms = active ? state.pitmasters || [] : [];
    for (const [id, ui] of pitmasters) if (!pms.some(pm => pm.id === id)) { ui.el.remove(); pitmasters.delete(id); }
    pms.forEach(pm => {
      let ui = pitmasters.get(pm.id); if (!ui) { ui = pitmaster(pm); pitmasters.set(pm.id, ui); $('pitmasters').append(ui.el); }
      ui.meta.textContent = 'Kanal ' + pm.channel + ' · ' + fmt(pm.value) + ' %';
      if (ui.form.dataset.dirty !== 'true') { ui.mode.value = pm.typ; ui.target.value = pm.set; ui.output.value = pm.value; }
      ui.targetLabel.textContent = 'Solltemperatur in ' + state.unit; ui.mode.disabled = !editable; ui.submit.disabled = !editable; ui.submit.hidden = cfg.controls === false; ui.sync();
    });
    $('pitmasters-section').hidden = pms.length === 0;
    $('device').textContent = state.device || 'Mini V3';
    $('telemetry').textContent = [Number.isFinite(state.battery) ? 'Akku ' + fmt(state.battery) + ' %' : '', Number.isFinite(state.rssi) ? 'WLAN ' + fmt(state.rssi) + ' dBm' : ''].filter(Boolean).join(' · ');
    $('updated').textContent = state.lastUpdate ? 'Stand ' + new Date(state.lastUpdate * 1000).toLocaleTimeString('de-DE', {hour:'2-digit', minute:'2-digit', second:'2-digit'}) : 'Noch kein erfolgreicher Abruf';
    $('bluetooth').hidden = typeof state.bluetooth !== 'boolean'; $('bluetooth').textContent = 'Bluetooth ' + (state.bluetooth ? 'an' : 'aus'); $('bluetooth').setAttribute('aria-pressed', String(Boolean(state.bluetooth))); $('bluetooth').disabled = !editable || Boolean(state.bluetoothError);
    $('device-error').textContent = [state.error, state.notificationError, cfg.details ? state.bluetoothError : ''].filter(Boolean).join(' · '); $('device-error').hidden = !$('device-error').textContent;
  }
  window.handleMessage = data => {
    try { if (typeof data === 'string') data = JSON.parse(data); if (!data || typeof data !== 'object' || Array.isArray(data)) return;
      if (data.enabled === false) { state = {...state, enabled:false}; if (data.response?.requestId === pending) announce(data.response.message, !data.response.ok); clearTimeout(timeout); pending = null; pendingAction = ''; render(); return; }
      if (data.version !== 1) return;
      if (data.config && !Object.prototype.hasOwnProperty.call(data.config, 'brandImages')) data.config.brandImages = state.config?.brandImages || ['',''];
      if (data.config?.primary !== state.config?.primary) selected = data.config?.primary || 0;
      state = data;
      if (data.response && data.response.requestId === pending) {
        const preserveEditors = pendingAction === 'visibility'; pendingAction = '';
        clearTimeout(timeout); pending = null; announce(data.response.message, !data.response.ok);
        if (data.response.ok && !preserveEditors) { for (const ui of pitmasters.values()) delete ui.form.dataset.dirty; for (const ui of probes.values()) { ui.details.open = false; ui.presetDirty = false; } }
      }
      render();
    } catch (error) { announce('Kacheldaten konnten nicht verarbeitet werden.', true); }
  };
  $('wired-selector').addEventListener('keydown', event => { if (event.key === 'Escape' && wiredMenuMode && $('wired-selector').open) { $('wired-selector').open = false; $('wired-selector-toggle').focus(); } });
  $('refresh').addEventListener('click', () => send('refresh'));
  $('bluetooth').addEventListener('click', () => send('bluetooth', {enabled:!state.bluetooth}));
  try { window.handleMessage(JSON.parse($('initial-state').textContent)); } catch (error) { announce('Initiale Kacheldaten fehlen.', true); }
})();
````

## libs/Api.php

````php
<?php

declare(strict_types=1);

/** Network reachability failure; does not imply a broken Symcon instance. */
final class WLTUnavailableException extends RuntimeException
{
}

/** Local WLANThermo ESP32 HTTP API. No Symcon dependencies. */
final class WLTApi
{
    public static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public static function object(string $body): array
    {
        $object = json_decode($body, false, 64, JSON_THROW_ON_ERROR);
        if (!$object instanceof stdClass) {
            throw new RuntimeException('JSON-Objekt erwartet.');
        }
        return json_decode($body, true, 64, JSON_THROW_ON_ERROR);
    }

    public static function baseURL(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }
        $p = parse_url($url);
        if ($p === false || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || empty($p['host']) ||
            isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment']) || !empty($p['path'])) {
            throw new InvalidArgumentException('Nur IP/Hostname mit optionalem http(s):// und Port eingeben, ohne Pfad oder Zugangsdaten.');
        }
        return $url;
    }

    public static function request(string $base, string $path, ?array $payload, string $user, string $password, int $timeout): string
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP-cURL wird benötigt.');
        }
        $ch = curl_init(self::baseURL($base) . $path);
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/json, text/plain', 'Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2];
        if ($password !== '') {
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            $options[CURLOPT_USERPWD] = $user . ':' . $password;
        }
        if ($payload !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = self::encode($payload);
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);
        if ($body === false) {
            // DNS, connection refused, timeout, empty reply and interrupted IO:
            // a switched-off device cannot be distinguished from a network outage.
            if (in_array($error, [5, 6, 7, 28, 52, 55, 56], true)) {
                throw new WLTUnavailableException('WLANthermo nicht erreichbar (ausgeschaltet oder Netzwerkverbindung unterbrochen; cURL ' . $error . ').');
            }
            // TLS/certificate and other transport configuration errors remain faults.
            throw new RuntimeException('HTTP-Verbindung fehlgeschlagen (cURL ' . $error . ').');
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('HTTP ' . $code . ($code === 401 ? ': Admin-Zugangsdaten prüfen.' : ' beim Abruf von ' . $path));
        }
        return $body;
    }

    public static function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/pass|token|secret|user_key|chat_id|ssid/i', (string) $key)) {
                $data[$key] = '[ausgeblendet]';
            } elseif (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }
        return $data;
    }

    public static function finiteNumber(mixed $v, float $min, float $max): float
    {
        if ((!is_int($v) && !is_float($v)) || !is_finite((float) $v) || $v < $min || $v > $max) {
            throw new InvalidArgumentException('Zahl außerhalb des erlaubten Bereichs: ' . $min . ' … ' . $max);
        }
        return (float) $v;
    }

    public static function integer(mixed $v, int $min, int $max): int
    {
        if (!is_int($v) || $v < $min || $v > $max) {
            throw new InvalidArgumentException('Ganzzahl außerhalb des erlaubten Bereichs.');
        }
        return $v;
    }

    public static function text(mixed $v, int $length): string
    {
        if (!is_string($v) || preg_match('//u', $v) !== 1 || preg_match_all('/./us', $v) > $length || preg_match('/[\x00-\x1f]/', $v)) {
            throw new InvalidArgumentException('Ungültiger Text (maximal ' . $length . ' Zeichen).');
        }
        return $v;
    }

    public static function item(array $items, string $key, mixed $id): array
    {
        foreach ($items as $item) {
            if (is_array($item) && isset($item[$key]) && (string) $item[$key] === (string) $id) {
                return $item;
            }
        }
        throw new InvalidArgumentException('Gerät/Kanal/Profil nicht vorhanden.');
    }

    public static function verify(array $actual, array $expected): void
    {
        foreach ($expected as $key => $v) {
            if (!array_key_exists($key, $actual)) {
                throw new RuntimeException('Rücklesen fehlgeschlagen: ' . $key);
            }
            $a = $actual[$key];
            if (is_bool($v) && in_array($a, [0, 1], true)) { $a = (bool) $a; }
            $matches = is_float($v) || is_int($v) ? is_numeric($a) && abs((float) $a - $v) < 0.011 : $a === $v;
            if (!$matches) {
                throw new RuntimeException('Gerät hat den Wert nicht übernommen: ' . $key);
            }
        }
    }
}
````

## libs/VariableManagement.php

````php
<?php

declare(strict_types=1);

trait WLTVariableManagement
{
    private function defaultVariableEnabled(string $ident): bool
    {
        $mode = $this->ReadPropertyString('VariableMode');
        if ($mode === 'automatic') { $mode = $this->ReadAttributeString('VariableDefaults') ?: 'none'; }
        return $mode === 'all' || ($mode === 'recommended' && $this->recommendedVariable($ident));
    }

    private function recommendedVariable(string $ident): bool
    {
        return $ident === 'Online' || preg_match('/^CH\d+_(Temperature|Valid)$/', $ident) === 1;
    }

    public function PrepareVariableSelection(string $Mode): void
    {
        if (!in_array($Mode, ['all', 'none', 'recommended'], true)) { throw new InvalidArgumentException('Ungültige Auswahl.'); }
        $rows = $this->selectionRows();
        foreach ($rows as &$row) { $row['Enabled'] = $Mode === 'all' || ($Mode === 'recommended' && $this->recommendedVariable($row['Ident'])); }
        unset($row);
        $this->UpdateFormField('VariableSelection', 'values', WLTApi::encode($rows));
        $this->UpdateFormField('VariableCounts', 'caption', 'Auswahl vorbereitet: ' . count(array_filter($rows, static fn(array $row): bool => $row['Enabled'])) . ' Einträge. Erst Übernehmen speichert die Auswahl; Gruppenschalter bleiben maßgeblich.');
    }

    private function variableCounts(): string
    {
        $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
        $selected = $new = $existing = $targets = $unused = 0;
        foreach ($catalog as $ident => $meta) {
            $own = (bool) @$this->GetIDForIdent($ident);
            if ($own) { $existing++; }
            $enabled = $this->selected($ident);
            $target = $this->variableOptions($ident)['TargetID'];
            if ($enabled) {
                $selected++;
                if ($target > 0) { $targets++; }
                elseif (!$own) { $new++; }
            }
            if ($own && (!$enabled || $target > 0)) { $unused++; }
        }
        return sprintf('%d bekannte Felder · %d ausgewählt (inkl. %d vorhandener Ziele) · %d eigene Variablen vorhanden · %d zusätzlich anzulegen · %d eigene Variablen ungenutzt. Ausgeblendete Variablen zählen weiterhin zur Lizenz.', count($catalog), $selected, $targets, $existing, $new, $unused);
    }

    /** Löschen ausschließlich nach gesonderter, ausdrücklicher Bestätigung. */
    public function RemoveDisabledVariables(bool $Confirmed): int
    {
        if (!$Confirmed) { throw new InvalidArgumentException('Zum Löschen zuerst die gesonderte Bestätigung aktivieren.'); }
        $this->selectionOverrides();
        $key = 'WLT_' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($key, 1000)) { throw new RuntimeException('Instanz ist beschäftigt.'); }
        try {
            $catalog = json_decode($this->ReadAttributeString('VariableCatalog'), true);
            $idents = array_unique(array_merge(array_keys($catalog), json_decode($this->ReadAttributeString('Dynamic'), true)));
            $removed = [];
            foreach ($idents as $ident) {
                if ((!$this->selected($ident) || $this->variableOptions($ident)['TargetID'] > 0) && @$this->GetIDForIdent($ident)) {
                    $this->UnregisterVariable($ident);
                    $removed[] = $ident;
                }
            }
            foreach (['HiddenVariables', 'AppliedVariableOptions'] as $attribute) {
                $values = json_decode($this->ReadAttributeString($attribute), true);
                $this->WriteAttributeString($attribute, WLTApi::encode(array_diff_key($values, array_flip($removed))));
            }
            $this->ReloadForm();
            return count($removed);
        } finally { IPS_SemaphoreLeave($key); }
    }

    private function validateTarget(int $id, ?int $type): void
    {
        if (!IPS_VariableExists($id)) { throw new InvalidArgumentException('Ausgewählte Zielvariable ' . $id . ' existiert nicht.'); }
        $object = IPS_GetObject($id);
        if ($object['ParentID'] === $this->InstanceID || $object['ObjectIsReadOnly']) {
            throw new InvalidArgumentException('Zielvariable muss eine beschreibbare externe Variable sein.');
        }
        if ($type !== null && IPS_GetVariable($id)['VariableType'] !== $type) {
            throw new InvalidArgumentException('Datentyp der Zielvariable ' . $id . ' passt nicht zum Datenfeld.');
        }
    }

    private function synchronizeReferences(): void
    {
        foreach ($this->GetReferenceList() as $id) { $this->UnregisterReference($id); }
        foreach (json_decode($this->ReadPropertyString('VariableSelection'), true) as $row) {
            if ($this->selected($row['Ident']) && ($row['TargetID'] ?? 0) > 0) { $this->RegisterReference($row['TargetID']); }
        }
    }
}
````

## libs/Visualization.php

````php
<?php

declare(strict_types=1);

trait WLTVisualization
{
    private function registerVisualization(): void
    {
        $this->RegisterPropertyBoolean('TileEnabled', true);
        $this->RegisterAttributeString('TileWiredVisibility', '{}');
        $this->RegisterPropertyString('TileTitle', 'Grillstation');
        $this->RegisterPropertyBoolean('TileHeaderEnabled', true);
        $this->RegisterPropertyInteger('TileHeaderChannel', 1);
        $this->RegisterPropertyString('TileHeaderName', 'Grilltemperatur');
        $this->RegisterPropertyBoolean('TileHero', false);
        $this->RegisterPropertyBoolean('TileFullscreen', false);
        $this->RegisterPropertyString('TileTheme', 'auto');
        $this->RegisterPropertyInteger('TileAccent', 16025665);
        $this->RegisterPropertyBoolean('TileCompact', false);
        $this->RegisterPropertyBoolean('TileDetails', true);
        $this->RegisterPropertyBoolean('TileIcons', true);
        $this->RegisterPropertyBoolean('TileAnimations', true);
        $this->RegisterPropertyBoolean('TileControls', true);
        $this->RegisterPropertyBoolean('TilePitmaster', true);
        $this->RegisterPropertyInteger('TilePrimary', 0);
        $this->RegisterPropertyString('TileChannels', '[]');
        $this->RegisterPropertyString('TileMeaterPairs', '[]');
        $this->RegisterPropertyString('TileCorePresets', '[{"Name":"Rumpsteak","Temperature":57},{"Name":"Pulled Pork","Temperature":94}]');
        $this->RegisterAttributeString('TilePresetChoices', '{}');
        $this->RegisterPropertyString('TileBrandImage1', '');
        $this->RegisterPropertyString('TileBrandImage2', '');
        $this->RegisterPropertyBoolean('TileBrandImage1Visible', true);
        $this->RegisterPropertyBoolean('TileBrandImage2Visible', true);
        $this->RegisterPropertyString('TileBrandLayout', 'split');
        $this->RegisterPropertyBoolean('ForecastEnabled', true);
        $this->RegisterPropertyBoolean('ForecastChart', true);
        $this->RegisterPropertyInteger('ForecastWindow', 10);
    }

    private function validateVisualization(): void
    {
        if (!in_array($this->ReadPropertyString('TileTheme'), ['auto', 'light', 'dark'], true)) {
            throw new InvalidArgumentException('Ungültiger Designmodus der Kachel.');
        }
        WLTApi::integer($this->ReadPropertyInteger('TileAccent'), 0, 0xffffff);
        WLTApi::integer($this->ReadPropertyInteger('TilePrimary'), 0, 255);
        WLTApi::integer($this->ReadPropertyInteger('TileHeaderChannel'), 1, 255);
        foreach (['TileTitle', 'TileHeaderName'] as $property) {
            if (strlen($this->ReadPropertyString($property)) > 240) { throw new InvalidArgumentException('Kacheltitel dürfen höchstens 240 Bytes lang sein.'); }
        }
        WLTApi::integer($this->ReadPropertyInteger('ForecastWindow'), 5, 60);
        $this->tileChannelSelection();
        $this->meaterPairSelection();
        $this->tileChannelNames();
        $this->corePresets('C');
        $this->brandImages();
        if (!in_array($this->ReadPropertyString('TileBrandLayout'), ['split','stacked'], true)) { throw new InvalidArgumentException('Ungültige Bildanordnung.'); }
    }

    private function brandImages(): array
    {
        $result = [];
        foreach ([1,2] as $slot) {
            $encoded = $this->ReadPropertyString('TileBrandImage' . $slot);
            if ($encoded === '') { $result[] = ''; continue; }
            if (strlen($encoded) > 350000) { throw new InvalidArgumentException('Titelbilder dürfen höchstens 256 KiB groß sein.'); }
            $raw = base64_decode($encoded, true);
            $info = $raw === false ? false : @getimagesizefromstring($raw);
            if (!$info || strlen($raw) > 262144 || !in_array($info['mime'] ?? '', ['image/png','image/jpeg','image/gif','image/webp'], true) || $info[0] > 2048 || $info[1] > 2048) {
                throw new InvalidArgumentException('Titelbild: PNG/JPEG/GIF/WebP, maximal 256 KiB und 2048 × 2048 Pixel.');
            }
            $result[] = $this->ReadPropertyBoolean('TileBrandImage' . $slot . 'Visible') ? 'data:' . $info['mime'] . ';base64,' . base64_encode($raw) : '';
        }
        return $result;
    }

    private function presetSource(array $channel): string
    {
        return hash('sha256', WLTApi::encode([$this->ReadPropertyString('Host'), $channel['name'] ?? '', $channel['typ'] ?? null, $channel['fixed'] ?? null]));
    }

    private function rememberedPreset(array $channel, string $unit): ?array
    {
        $choices = json_decode($this->ReadAttributeString('TilePresetChoices'), true) ?: [];
        $choice = $choices[$channel['number']] ?? null;
        if (!$choice || ($choice['source'] ?? '') !== $this->presetSource($channel)) { return null; }
        foreach ($this->corePresets('C') as $preset) {
            if ($preset['name'] === $choice['name'] && $preset['temperature'] === $choice['celsius']) {
                $value = $unit === 'F' ? (int) round($choice['celsius'] * 9 / 5 + 32) : $choice['celsius'];
                return isset($channel['max']) && (float) $channel['max'] === (float) $value ? ['name'=>$choice['name'], 'temperature'=>$value] : null;
            }
        }
        return null;
    }

    /** Vorlagen werden in Celsius gespeichert; Anzeige und Schreibwert folgen der Geräteeinheit. */
    private function corePresets(string $unit): array
    {
        $rows = json_decode($this->ReadPropertyString('TileCorePresets'), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($rows) || !array_is_list($rows) || count($rows) > 100) { throw new InvalidArgumentException('Kerntemperatur-Sammlung: höchstens 100 Einträge als Liste.'); }
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['Name'] ?? null) || trim($row['Name']) === '' || strlen($row['Name']) > 240) { throw new InvalidArgumentException('Kerntemperatur-Vorlage benötigt einen Namen mit höchstens 240 Bytes.'); }
            $value = WLTApi::integer($row['Temperature'] ?? null, -30, 300);
            $result[] = ['name' => trim($row['Name']), 'temperature' => $unit === 'F' ? (int) round($value * 9 / 5 + 32) : $value];
        }
        return $result;
    }

    private function tileChannelNames(): array
    {
        $this->tileChannelSelection();
        $rows = json_decode($this->ReadPropertyString('TileChannels'), true, 64, JSON_THROW_ON_ERROR);
        $result = [];
        foreach ($rows as $row) {
            $name = $row['DisplayName'] ?? '';
            if (!is_string($name) || strlen($name) > 240) { throw new InvalidArgumentException('Fühler-Anzeigenamen dürfen höchstens 240 Bytes lang sein.'); }
            if (trim($name) !== '') { $result[$row['Number']] = trim($name); }
        }
        return $result;
    }

    private function meaterPairSelection(): array
    {
        $rows = json_decode($this->ReadPropertyString('TileMeaterPairs'), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($rows) || !array_is_list($rows)) { throw new InvalidArgumentException('MEATER-Zuordnung muss eine Liste sein.'); }
        $used = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['Name'] ?? null) || trim($row['Name']) === '') { throw new InvalidArgumentException('MEATER benötigt einen Anzeigenamen.'); }
            foreach (['Core', 'Ambient'] as $key) {
                $number = WLTApi::integer($row[$key] ?? null, 1, 255);
                if (isset($used[$number])) { throw new InvalidArgumentException('Ein Kanal darf nur einem MEATER zugeordnet sein.'); }
                $used[$number] = true;
            }
        }
        return $rows;
    }

    /** Die HTTP-Kanalliste enthält keine MAC-Adresse/Messstellen-ID. Keine Paarung nach Nachbarschaft. */
    private function meaterMetadata(array $data, array $settings): array
    {
        $channels = [];
        foreach ($data['channel'] ?? [] as $channel) {
            if ($this->channelIsWireless($channel, $settings)) { $channels[$channel['number']] = $channel; }
        }
        $meta = []; $candidates = [];
        foreach ($channels as $number => $channel) {
            if (preg_match('/^(.*meater.*?)\s*[-_ ]*\s*(Kerntemperatur|Garraumtemperatur|KT|GT)$/iu', trim($channel['name']), $match)) {
                $base = trim($match[1], " -_");
                $role = in_array(strtolower($match[2]), ['kt', 'kerntemperatur'], true) ? 'core' : 'ambient';
                $key = strtolower($base);
                $candidates[$key]['name'] = $base;
                $candidates[$key][$role][] = $number;
                // Auch ein allein verfügbarer Garraumsensor bleibt eine reine Anzeige.
                $meta[$number] = ['sensorRole' => $role, 'probeName' => $base];
            }
        }
        foreach ($candidates as $candidate) {
            if (count($candidate['core'] ?? []) === 1 && count($candidate['ambient'] ?? []) === 1) {
                foreach (['core', 'ambient'] as $role) {
                    $number = $candidate[$role][0];
                    $meta[$number]['pairKey'] = 'meater_' . $candidate['core'][0];
                }
            }
        }
        $rows = $this->meaterPairSelection();
        $explicit = [];
        foreach ($rows as $row) { $explicit[$row['Core']] = $explicit[$row['Ambient']] = true; }
        // Explizite Zuordnung löst auch die bisherige automatische Paarung ihres Partners auf.
        $blocked = [];
        foreach ($explicit as $number => $_) { if (isset($meta[$number]['pairKey'])) { $blocked[$meta[$number]['pairKey']] = true; } }
        foreach ($meta as &$item) { if (isset($blocked[$item['pairKey'] ?? ''])) { unset($item['pairKey']); } }
        unset($item);
        foreach ($rows as $row) {
            foreach (['core' => 'Core', 'ambient' => 'Ambient'] as $role => $field) {
                if (isset($channels[$row[$field]])) {
                    $meta[$row[$field]] = ['sensorRole' => $role, 'probeName' => trim($row['Name']), 'pairKey' => 'meater_' . $row['Core']];
                }
            }
        }
        return $meta;
    }

    private function tileChannelSelection(): array
    {
        $rows = json_decode($this->ReadPropertyString('TileChannels'), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($rows) || !array_is_list($rows)) { throw new InvalidArgumentException('Kachelauswahl muss eine Liste sein.'); }
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['Number'], $row['Enabled']) || !is_int($row['Number']) ||
                $row['Number'] < 1 || $row['Number'] > 255 || !is_bool($row['Enabled']) || isset($result[$row['Number']])) {
                throw new InvalidArgumentException('Ungültiger oder doppelter Kanal in der Kachelauswahl.');
            }
            $result[$row['Number']] = $row['Enabled'];
        }
        return $result;
    }

    private function tileChannelRows(): array
    {
        $choices = $this->tileChannelSelection();
        $displayNames = $this->tileChannelNames();
        $notify = array_column(json_decode($this->ReadPropertyString('TileChannels'), true), 'Notify', 'Number');
        $names = [];
        foreach ($this->cached('Data')['channel'] ?? [] as $ch) { $names[$ch['number']] = $ch['name']; }
        $rows = [];
        foreach (array_unique(array_merge(array_keys($names), array_keys($choices))) as $number) {
            $rows[] = ['Number' => $number, 'Name' => $names[$number] ?? 'Zeitweise nicht vorhanden', 'Enabled' => $choices[$number] ?? true, 'DisplayName' => $displayNames[$number] ?? '', 'Notify' => $notify[$number] ?? true];
        }
        return $rows;
    }

    private function populatePremiumForm(array &$elements, string $counts): void
    {
        foreach ($elements as &$element) {
            if (($element['name'] ?? '') === 'PushoverState') { $element['caption'] = 'Pushover: ' . ($this->GetNotificationError() ?: ($this->ReadPropertyBoolean('PushoverEnabled') ? 'aktiviert; Versand erfolgt bei der nächsten passenden Messung.' : 'ausgeschaltet.')); }
            if (($element['name'] ?? '') === 'VariableCounts') { $element['caption'] = $counts; }
            if (($element['name'] ?? '') === 'TileChannels') {
                try { $element['values'] = $this->tileChannelRows(); } catch (Throwable $e) { $element['values'] = []; }
            }
            if (isset($element['items'])) { $this->populatePremiumForm($element['items'], $counts); }
        }
        unset($element);
    }

    private function channelIsWireless(array $channel, array $settings): bool
    {
        if ((int) ($channel['typ'] ?? -1) === 16) { return true; }
        foreach ($settings['sensors'] ?? [] as $sensor) {
            if ((int) $sensor['type'] === (int) ($channel['typ'] ?? -1) && stripos($sensor['name'], 'Bluetooth') !== false) { return true; }
        }
        return false;
    }

    private function channelHasValidTemperature(array $channel, array $settings): bool
    {
        $value = $channel['temp'] ?? null;
        // connected ist bei kabelgebundenen Fühlern kein Verfügbarkeitsmerkmal.
        return is_numeric($value) && is_finite((float) $value) && (float) $value !== 999.0 &&
            (!$this->channelIsWireless($channel, $settings) || !empty($channel['connected']));
    }

    private function wiredHiddenChannels(): array
    {
        $saved = json_decode($this->ReadAttributeString('TileWiredVisibility'), true) ?: [];
        return ($saved['host'] ?? '') === $this->ReadPropertyString('Host') && is_array($saved['hidden'] ?? null) ? $saved['hidden'] : [];
    }

    private function wiredChannelVisible(int $number): bool
    {
        // Die separat konfigurierte Kopftemperatur bleibt von der Übersichtsauswahl unabhängig.
        if ($this->ReadPropertyBoolean('TileHeaderEnabled') && $this->ReadPropertyInteger('TileHeaderChannel') === $number) { return true; }
        return !($this->wiredHiddenChannels()[$number] ?? false);
    }

    private function setWiredChannelVisibility(int $number, bool $visible): void
    {
        $key = 'WLT_' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($key, 1000)) { throw new RuntimeException('Instanz ist beschäftigt. Bitte erneut versuchen.'); }
        try {
            $channel = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
            if ($this->channelIsWireless($channel, $this->cached('Settings'))) { throw new InvalidArgumentException('Die Fühlerleiste gilt nur für kabelgebundene Fühler.'); }
            if (!($this->tileChannelSelection()[$number] ?? true)) { throw new InvalidArgumentException('Dieser Kanal ist in der Modulkonfiguration ausgeblendet.'); }
            if ($this->ReadPropertyBoolean('TileHeaderEnabled') && $number === $this->ReadPropertyInteger('TileHeaderChannel')) { throw new InvalidArgumentException('Die Kopftemperatur wird separat konfiguriert.'); }
            $hidden = $this->wiredHiddenChannels();
            if ($visible) { unset($hidden[$number]); } else { $hidden[$number] = true; }
            $this->WriteAttributeString('TileWiredVisibility', WLTApi::encode(['host'=>$this->ReadPropertyString('Host'),'hidden'=>$hidden]));
        } finally { IPS_SemaphoreLeave($key); }
    }

    /** Nur benötigte, freigegebene Daten an die Kachel übertragen. */
    private function visualizationData(): array
    {
        $data = $this->cached('Data');
        $settings = $this->cached('Settings');
        $online = $this->ReadPropertyBoolean('Active') && (bool) $this->stateValue('Online', false);
        try { $choices = $this->tileChannelSelection(); } catch (Throwable $e) { $choices = []; }
        $channels = []; $wiredChannels = [];
        try { $meater = $this->meaterMetadata($data, $settings); }
        catch (Throwable $e) { $meater = []; $online = false; }
        try { $names = $this->tileChannelNames(); $presets = $this->corePresets($data['system']['unit'] ?? 'C'); $images = $this->brandImages(); }
        catch (Throwable $e) { $names = []; $presets = []; $images = ['','']; $online = false; }
        foreach ($data['channel'] ?? [] as $ch) {
            if (!($choices[$ch['number']] ?? true)) { continue; }
            $wireless = $this->channelIsWireless($ch, $settings);
            $valid = $online && $this->channelHasValidTemperature($ch, $settings);
            if (!$valid) { continue; }
            if (!$wireless) {
                $visible = $this->wiredChannelVisible((int) $ch['number']);
                if (!$this->ReadPropertyBoolean('TileHeaderEnabled') || $ch['number'] !== $this->ReadPropertyInteger('TileHeaderChannel')) {
                    $wiredChannels[] = ['number'=>$ch['number'],'name'=>$names[$ch['number']] ?? (string) $ch['name'],'visible'=>$visible];
                }
                if (!$visible) { continue; }
            }
            $channels[] = ['number' => $ch['number'], 'name' => (string) $ch['name'], 'displayName' => $names[$ch['number']] ?? '', 'temperature' => $valid ? (float) $ch['temp'] : null,
                'targetAlert' => $this->ReadPropertyBoolean('TargetWarningEnabled') ? $this->targetAlertLevel($ch, $data, $settings) : '', 'valid' => $valid, 'wireless' => $wireless, 'max' => $ch['max'] ?? null, 'selectedPreset' => $this->rememberedPreset($ch, $data['system']['unit'] ?? 'C'),
                'forecast' => $this->channelForecast($ch, ($data['system']['unit'] ?? 'C')),
                'alarm' => $ch['alarm'] ?? 0, 'outOfRange' => $valid && isset($ch['max']) && $ch['temp'] > $ch['max']] + ($meater[$ch['number']] ?? []);
        }
        $bt = $this->cached('BluetoothData');
        return ['version' => 1, 'enabled' => $this->ReadPropertyBoolean('TileEnabled'), 'online' => $online,
            'active' => $this->ReadPropertyBoolean('Active'), 'status' => $this->GetStatus(), 'lastUpdate' => $this->stateValue('LastUpdate', 0),
            'notificationError' => $this->GetNotificationError(), 'error' => $this->GetLastError(), 'bluetoothError' => $this->stateValue('BluetoothError', ''),
            'unit' => ($data['system']['unit'] ?? 'C') === 'F' ? '°F' : '°C', 'channels' => $channels, 'wiredChannels'=>$wiredChannels,
            'pitmasters' => $this->ReadPropertyBoolean('TilePitmaster') ? array_values(array_map(static fn(array $pm): array =>
                array_intersect_key($pm, array_flip(['id', 'typ', 'set', 'value', 'channel'])), $data['pitmaster']['pm'] ?? [])) : [],
            'bluetooth' => isset($bt['enabled']) ? (bool) $bt['enabled'] : null,
            'device' => (string) ($settings['system']['host'] ?? 'Mini V3'),
            'battery' => $this->ReadPropertyBoolean('TileDetails') ? ($data['system']['soc'] ?? null) : null,
            'rssi' => $this->ReadPropertyBoolean('TileDetails') ? ($data['system']['rssi'] ?? null) : null,
            'canRefresh' => $this->ReadPropertyBoolean('Active'),
            'canWrite' => $online && $this->ReadPropertyBoolean('TileControls') && $this->ReadPropertyBoolean('AllowWrite'),
            'config' => ['alertBlink' => $this->ReadPropertyBoolean('TargetWarningBlink'), 'brandImages' => $images, 'brandLayout' => $this->ReadPropertyString('TileBrandLayout'), 'corePresets' => $presets, 'title' => trim($this->ReadPropertyString('TileTitle')) ?: 'Grillstation',
                'headerEnabled' => $this->ReadPropertyBoolean('TileHeaderEnabled'), 'headerChannel' => $this->ReadPropertyInteger('TileHeaderChannel'),
                'headerName' => trim($this->ReadPropertyString('TileHeaderName')) ?: 'Grilltemperatur',
                'theme' => in_array($this->ReadPropertyString('TileTheme'), ['auto', 'light', 'dark'], true) ? $this->ReadPropertyString('TileTheme') : 'auto',
                'accent' => sprintf('#%06x', max(0, min(0xffffff, $this->ReadPropertyInteger('TileAccent')))),
                'compact' => $this->ReadPropertyBoolean('TileCompact'), 'details' => $this->ReadPropertyBoolean('TileDetails'),
                'icons' => $this->ReadPropertyBoolean('TileIcons'), 'animations' => $this->ReadPropertyBoolean('TileAnimations'),
                'actionTimeout' => max(35000, $this->ReadPropertyInteger('Timeout') * 7000 + 2000) + ($this->ReadPropertyBoolean('PushoverEnabled') ? 6000 : 0),
                'hero' => $this->ReadPropertyBoolean('TileHero'),
                'forecast' => $this->ReadPropertyBoolean('ForecastEnabled'), 'forecastChart' => $this->ReadPropertyBoolean('ForecastChart'),
                'controls' => $this->ReadPropertyBoolean('TileControls'), 'primary' => $this->ReadPropertyInteger('TilePrimary')]];
    }

    public function GetVisualizationTile(): string
    {
        if (!$this->ReadPropertyBoolean('TileEnabled')) { return ''; }
        $json = json_encode($this->visualizationData(), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        return strtr(file_get_contents(__DIR__ . '/../WLANThermo/module.html'), [
            '/*WLT_CSS*/' => file_get_contents(__DIR__ . '/../WLANThermo/tile.css'),
            '/*WLT_JS*/' => file_get_contents(__DIR__ . '/../WLANThermo/tile.js'),
            '__WLT_INITIAL__' => $json]);
    }

    private function pushVisualization(?array $response = null): void
    {
        if (!$this->ReadPropertyBoolean('TileEnabled')) {
            $this->UpdateVisualizationValue(WLTApi::encode(['enabled' => false, 'response' => $response]));
            return;
        }
        $data = $this->visualizationData();
        if ($response !== null) { $data['response'] = $response; }
        $encoded = WLTApi::encode($data);
        $signature = hash('sha256', $encoded);
        if ($response !== null || $signature !== $this->GetBuffer('VisualizationLast')) {
            $imageSignature = hash('sha256', WLTApi::encode($data['config']['brandImages']));
            if ($imageSignature === $this->GetBuffer('VisualizationImages')) { unset($data['config']['brandImages']); }
            // Native Symcon-Werte unterstützen keine PHP-Arrays: JSON-Text übertragen.
            if ($this->UpdateVisualizationValue(WLTApi::encode($data))) {
                $this->SetBuffer('VisualizationLast', $signature);
                $this->SetBuffer('VisualizationImages', $imageSignature);
            }
        }
    }

    private function visualizationAction(mixed $value): void
    {
        $requestID = '';
        $adjusted = false;
        try {
            if (!is_string($value) || strlen($value) > 2048) { throw new InvalidArgumentException('Ungültige Kachelanfrage.'); }
            $request = WLTApi::object($value);
            if (!isset($request['requestId']) || !is_string($request['requestId']) || !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $request['requestId'])) {
                throw new InvalidArgumentException('Ungültige Anfragekennung.');
            }
            $requestID = $request['requestId'];
            $this->validateVisualization();
            if (!$this->ReadPropertyBoolean('TileEnabled')) { throw new RuntimeException('Die Kachel ist deaktiviert.'); }
            if (!$this->ReadPropertyBoolean('Active')) { throw new RuntimeException('Die Instanz ist deaktiviert.'); }
            $action = $request['action'] ?? '';
            $allowed = match ($action) {
                'refresh' => ['requestId', 'action'],
                'visibility' => ['requestId', 'action', 'number', 'visible'],
                'forecastReset' => ['requestId', 'action', 'number'],
                'channel' => ['requestId', 'action', 'number', 'max', 'preset'],
                'pitmaster' => ['requestId', 'action', 'id', 'mode', 'target', 'output'],
                'bluetooth' => ['requestId', 'action', 'enabled'],
                default => throw new InvalidArgumentException('Unbekannte Kachelaktion.')
            };
            if (array_diff(array_keys($request), $allowed)) { throw new InvalidArgumentException('Unbekannte Aktionsparameter.'); }
            if (!in_array($action, ['refresh', 'forecastReset', 'visibility'], true) && (!$this->ReadPropertyBoolean('TileControls') || !$this->ReadPropertyBoolean('AllowWrite') || !$this->stateValue('Online', false))) {
                throw new RuntimeException('Bedienung ist gesperrt oder das Gerät ist nicht erreichbar.');
            }
            if ($action === 'refresh') { $ok = $this->Update(); }
            elseif ($action === 'visibility') {
                $number = WLTApi::integer($request['number'] ?? null, 1, 255);
                if (!is_bool($request['visible'] ?? null)) { throw new InvalidArgumentException('Sichtbarkeit muss ein Schalter sein.'); }
                $this->setWiredChannelVisibility($number, $request['visible']);
                $ok = true;
            }
            elseif ($action === 'forecastReset') {
                if (!$this->ReadPropertyBoolean('TileControls')) { throw new RuntimeException('Kachelbedienung ist gesperrt.'); }
                $number = WLTApi::integer($request['number'] ?? null, 1, 255);
                if (!($this->tileChannelSelection()[$number] ?? true)) { throw new InvalidArgumentException('Dieser Kanal ist ausgeblendet.'); }
                $channel = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
                if (!$this->channelIsWireless($channel, $this->cached('Settings')) && !$this->wiredChannelVisible($number)) { throw new InvalidArgumentException('Dieser Fühler ist in der Übersicht ausgeblendet.'); }
                $this->resetChannelForecast($number);
                $ok = true;
            }
            elseif ($action === 'channel') {
                $number = WLTApi::integer($request['number'] ?? null, 1, 255);
                if (!($this->tileChannelSelection()[$number] ?? true)) { throw new InvalidArgumentException('Dieser Kanal ist in der Kachel ausgeblendet.'); }
                $channel = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
                if (!$this->channelIsWireless($channel, $this->cached('Settings')) && !$this->wiredChannelVisible($number)) { throw new InvalidArgumentException('Dieser Fühler ist in der Übersicht ausgeblendet.'); }
                if (!$this->channelHasValidTemperature($channel, $this->cached('Settings'))) {
                    throw new InvalidArgumentException('Dieser Fühler liefert keinen gültigen Messwert.');
                }
                if (($this->meaterMetadata($this->cached('Data'), $this->cached('Settings'))[$number]['sensorRole'] ?? '') === 'ambient') {
                    throw new InvalidArgumentException('Die MEATER-Garraumtemperatur ist in der Kachel eine reine Messwertanzeige.');
                }
                $chosen = null;
                if (array_key_exists('preset', $request)) {
                    $selection = $request['preset'];
                    if (!is_array($selection) || count($selection) !== 2 || !is_string($selection['name'] ?? null) || !is_int($selection['temperature'] ?? null)) { throw new InvalidArgumentException('Ungültige Vorlagenauswahl.'); }
                    foreach ($this->corePresets($this->cached('Data')['system']['unit'] ?? 'C') as $index => $preset) {
                        if ($selection['name'] === $preset['name'] && $selection['temperature'] === $preset['temperature'] && ($request['max'] ?? null) === $preset['temperature']) { $chosen = $this->corePresets('C')[$index]; break; }
                    }
                    if ($chosen === null) { throw new InvalidArgumentException('Vorlage wurde geändert oder passt nicht zur Obergrenze.'); }
                }
                [$ok, $adjusted] = $this->setVisualizationUpperLimit($number, $request['max'] ?? null);
                if ($ok) {
                    $choices = json_decode($this->ReadAttributeString('TilePresetChoices'), true) ?: [];
                    if ($chosen) {
                        $confirmed = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
                        $choices[$number] = ['name'=>$chosen['name'],'celsius'=>$chosen['temperature'],'source'=>$this->presetSource($confirmed)];
                    } else { unset($choices[$number]); }
                    $this->WriteAttributeString('TilePresetChoices', WLTApi::encode($choices));
                }
            } elseif ($action === 'pitmaster') {
                if (!$this->ReadPropertyBoolean('TilePitmaster')) { throw new InvalidArgumentException('Pitmaster ist in der Kachel ausgeblendet.'); }
                $id = WLTApi::integer($request['id'] ?? null, 0, 255);
                $mode = $request['mode'] ?? null;
                if (!in_array($mode, ['off', 'manual', 'auto'], true)) { throw new InvalidArgumentException('Ungültiger Pitmaster-Modus.'); }
                $changes = ['typ' => $mode];
                if ($mode === 'auto') { $changes['set'] = $request['target'] ?? null; }
                if ($mode === 'manual') { $changes['value'] = $request['output'] ?? null; }
                $ok = $this->SetPitmaster($id, WLTApi::encode($changes));
            } else {
                $this->boolean($request['enabled'] ?? null);
                $ok = $this->SetBluetoothEnabled($request['enabled']);
            }
            $message = $ok ? ($action === 'refresh' ? 'Messwerte aktualisiert.' : ($action === 'forecastReset' ? 'Temperaturverlauf neu gestartet.' : 'Änderung am Gerät bestätigt.')) : ($this->GetLastError() ?: 'Instanz ist beschäftigt. Bitte erneut versuchen.');
            if ($ok && $adjusted) { $message = 'Obergrenze bestätigt; Untergrenze automatisch angepasst.'; }
            if ($ok && $action === 'visibility') { $message = $request['visible'] ? 'Fühler eingeblendet.' : 'Fühler ausgeblendet.'; }
        } catch (Throwable $e) { $ok = false; $message = $e->getMessage(); }
        // Auch Fehler quittieren; keine Erfolgsmeldung vor dem tatsächlichen Rücklesen.
        $this->pushVisualization(['requestId' => $requestID, 'ok' => $ok, 'message' => $message]);
    }
}
````

## libs/Forecast.php

````php
<?php

declare(strict_types=1);

/** Begrenzter Messwertverlauf und konservative lineare Trendprognose. */
final class WLTForecast
{
    public static function append(array $state, string $fingerprint, int $now, float $temperature, int $window, int $gap, float $unitScale): array
    {
        $points = $state['points'] ?? [];
        $last = $points ? $points[count($points) - 1] : null;
        $reset = ($state['fingerprint'] ?? '') !== $fingerprint || !$last || $now < $last[0] ||
            $now - $last[0] > $gap || ($now >= $last[0] && $now - $last[0] <= 60 && abs($temperature - $last[1]) / $unitScale >= 6.0);
        if ($reset) {
            return ['fingerprint' => $fingerprint, 'startedAt' => $now, 'start' => $temperature, 'points' => [[$now, $temperature]]];
        }
        if ($now - $last[0] < 10) { return $state; }
        $points[] = [$now, $temperature];
        $state['points'] = array_slice(array_values(array_filter($points, static fn(array $p): bool => $p[0] >= $now - $window)), -361);
        return $state;
    }

    public static function estimate(array $state, int $now, float $current, mixed $target, float $unitScale, int $gap): array
    {
        $points = $state['points'] ?? [];
        $result = ['status' => 'learning', 'seconds' => null, 'eta' => null, 'rate' => null, 'progress' => null,
            'start' => $state['start'] ?? null, 'startedAt' => $state['startedAt'] ?? null, 'points' => self::chartPoints($points)];
        if ((!is_int($target) && !is_float($target)) || !is_finite((float) $target)) { return array_replace($result, ['status' => 'no_target']); }
        if ($current >= $target) { return array_replace($result, ['status' => 'reached', 'seconds' => 0, 'eta' => $now, 'progress' => 1.0]); }
        if (isset($state['start']) && $target > $state['start']) {
            $result['progress'] = max(0.0, min(1.0, ($current - $state['start']) / ($target - $state['start'])));
        }
        if (count($points) < 6) { return $result; }
        $last = $points[count($points) - 1];
        if ($now < $last[0] || $now - $last[0] > $gap) { return array_replace($result, ['status' => 'stale']); }
        $span = $last[0] - $points[0][0];
        if ($span < 180) { return $result; }
        $fit = self::fit($points);
        $rate = $fit['rate']; // Grad pro Minute, in der Geräteeinheit.
        $result['rate'] = $rate;
        $recentSpan = min(180, max(90, $span / 2));
        $recent = array_values(array_filter($points, static fn(array $p): bool => $p[0] >= $last[0] - $recentSpan));
        if (count($recent) < 3) { $recent = array_slice($points, -3); }
        $recentRate = self::fit($recent)['rate'];
        if ($rate / $unitScale < -0.02 || $recentRate / $unitScale < -0.02) { return array_replace($result, ['status' => 'cooling']); }
        if ($rate / $unitScale <= 0.02 || $recentRate / $unitScale <= 0.02) { return array_replace($result, ['status' => 'stalled']); }
        $rise = ($last[1] - $points[0][1]) / $unitScale;
        if ($rise < 0.3 || $fit['r2'] < 0.8 || $recentRate < $rate * 0.35 || $recentRate > $rate * 2.5) {
            return array_replace($result, ['status' => 'unstable']);
        }
        $seconds = ($target - $current) / $rate * 60;
        if (!is_finite($seconds) || $seconds > 43200) { return array_replace($result, ['status' => 'distant']); }
        return array_replace($result, ['status' => 'estimated', 'seconds' => (int) ceil($seconds), 'eta' => $now + (int) ceil($seconds)]);
    }

    private static function fit(array $points): array
    {
        $n = count($points);
        if ($n < 2) { return ['rate' => 0.0, 'r2' => 0.0]; }
        $origin = $points[0][0];
        $meanX = $meanY = 0.0;
        foreach ($points as [$time, $value]) { $meanX += ($time - $origin) / 60; $meanY += $value; }
        $meanX /= $n; $meanY /= $n;
        $xx = $xy = $yy = 0.0;
        foreach ($points as [$time, $value]) {
            $x = ($time - $origin) / 60 - $meanX; $y = $value - $meanY;
            $xx += $x * $x; $xy += $x * $y; $yy += $y * $y;
        }
        $rate = $xx > 0 ? $xy / $xx : 0.0;
        $r2 = $xx > 0 && $yy > 0 ? $xy * $xy / ($xx * $yy) : 0.0;
        return ['rate' => is_finite($rate) ? $rate : 0.0, 'r2' => is_finite($r2) ? max(0.0, min(1.0, $r2)) : 0.0];
    }

    private static function chartPoints(array $points): array
    {
        if (count($points) <= 48) { return $points; }
        $result = []; $last = count($points) - 1;
        for ($i = 0; $i < 48; $i++) { $result[] = $points[(int) floor($i * $last / 47)]; }
        return $result;
    }
}

trait WLTForecastSupport
{
    protected function forecastTime(): int { return time(); }

    private function forecastGap(): int { return max(120, $this->ReadPropertyInteger('Interval') * 3); }

    private function forecastState(int $number): array
    {
        $value = $this->GetBuffer('Forecast_' . $number);
        $state = $value === '' ? [] : json_decode($value, true);
        return is_array($state) ? $state : [];
    }

    private function recordForecast(array $data, array $settings): void
    {
        $known = json_decode($this->GetBuffer('ForecastChannels') ?: '[]', true) ?: [];
        $active = [];
        if ($this->ReadPropertyBoolean('ForecastEnabled')) {
            $now = $this->forecastTime();
            $unit = ($data['system']['unit'] ?? 'C') === 'F' ? 'F' : 'C';
            foreach ($data['channel'] as $channel) {
                $number = $channel['number'];
                if ($number > 255 || !$this->channelHasValidTemperature($channel, $settings)) { continue; }
                $active[] = $number;
                $fingerprint = sha1(WLTApi::encode([$this->ReadPropertyString('Host'), $unit, $channel['name'], $channel['typ'] ?? null, $channel['fixed'] ?? null]));
                $state = WLTForecast::append($this->forecastState($number), $fingerprint, $now, (float) $channel['temp'],
                    $this->ReadPropertyInteger('ForecastWindow') * 60, $this->forecastGap(), $unit === 'F' ? 1.8 : 1.0);
                $this->SetBuffer('Forecast_' . $number, WLTApi::encode($state));
            }
        }
        foreach (array_diff($known, $active) as $number) { $this->SetBuffer('Forecast_' . $number, ''); }
        $this->SetBuffer('ForecastChannels', WLTApi::encode($active));
    }

    private function channelForecast(array $channel, string $unit): array
    {
        if (!$this->ReadPropertyBoolean('ForecastEnabled')) { return ['status' => 'disabled', 'points' => []]; }
        $result = WLTForecast::estimate($this->forecastState($channel['number']), $this->forecastTime(), (float) $channel['temp'],
            $channel['max'] ?? null, $unit === 'F' ? 1.8 : 1.0, $this->forecastGap());
        if (!$this->ReadPropertyBoolean('ForecastChart')) { $result['points'] = []; }
        return $result;
    }

    private function resetChannelForecast(int $number): void
    {
        if (!IPS_SemaphoreEnter('WLT_' . $this->InstanceID, 1000)) { throw new RuntimeException('Instanz ist beschäftigt.'); }
        try {
            $channel = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
            if (!$this->ReadPropertyBoolean('ForecastEnabled') || !$this->stateValue('Online', false) ||
                !$this->channelHasValidTemperature($channel, $this->cached('Settings'))) {
                throw new RuntimeException('Verlauf kann derzeit nicht gestartet werden.');
            }
            $this->SetBuffer('Forecast_' . $number, '');
            // Erst der nächste echte Geräteabruf liefert den ersten neuen Messpunkt.
        } finally { IPS_SemaphoreLeave('WLT_' . $this->InstanceID); }
    }
}
````

## libs/Notifications.php

````php
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
        if (!$this->channelIsWireless($channel, $settings) && !$this->wiredChannelVisible((int) $number)) { return ''; }
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
````

## README.md

````markdown
# WLANThermo für IP-Symcon

PHP-Modul für **IP-Symcon 9.0**, **WLANThermo Mini V3** und daran angebundene **MEATER-/Bluetooth-Fühler**. Kommunikation lokal über HTTP; ein MQTT-Broker und ein MEATER-Cloud-Konto sind nicht erforderlich. Unabhängiges Community-Modul, kein offizielles Produkt von Symcon, WLANThermo oder MEATER.

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
````

## LICENSE

````
MIT License

Copyright (c) 2026 schrotto and WLANThermo module contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
````

## .github/workflows/ci.yml

````yaml
name: PHP validation
on: [push, pull_request, workflow_dispatch]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: curl
      - name: Syntax
        run: find WLANThermo libs tests -name '*.php' -print0 | xargs -0 -n1 php -l
      - name: Module behavior
        run: php -n tests/run.php
      - name: JSON and module structure
        run: python3 tests/structure.py
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
      - name: Tile JavaScript
        run: |
          node --check WLANThermo/tile.js
          node tests/tile-ui.cjs
      - name: HTTP transport
        run: python3 tests/http_transport.py php
````
