<?php

declare(strict_types=1);

trait WLTVisualization
{
    private function registerVisualization(): void
    {
        $this->RegisterPropertyBoolean('TileEnabled', true);
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
    }

    private function validateVisualization(): void
    {
        if (!in_array($this->ReadPropertyString('TileTheme'), ['auto', 'light', 'dark'], true)) {
            throw new InvalidArgumentException('Ungültiger Designmodus der Kachel.');
        }
        WLTApi::integer($this->ReadPropertyInteger('TileAccent'), 0, 0xffffff);
        WLTApi::integer($this->ReadPropertyInteger('TilePrimary'), 0, 255);
        $this->tileChannelSelection();
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
        $names = [];
        foreach ($this->cached('Data')['channel'] ?? [] as $ch) { $names[$ch['number']] = $ch['name']; }
        $rows = [];
        foreach (array_unique(array_merge(array_keys($names), array_keys($choices))) as $number) {
            $rows[] = ['Number' => $number, 'Name' => $names[$number] ?? 'Zeitweise nicht vorhanden', 'Enabled' => $choices[$number] ?? true];
        }
        return $rows;
    }

    private function populatePremiumForm(array &$elements, string $counts): void
    {
        foreach ($elements as &$element) {
            if (($element['name'] ?? '') === 'VariableCounts') { $element['caption'] = $counts; }
            if (($element['name'] ?? '') === 'TileChannels') {
                try { $element['values'] = $this->tileChannelRows(); } catch (Throwable $e) { $element['values'] = []; }
            }
            if (isset($element['items'])) { $this->populatePremiumForm($element['items'], $counts); }
        }
        unset($element);
    }

    /** Nur benötigte, freigegebene Daten an die Kachel übertragen. */
    private function visualizationData(): array
    {
        $data = $this->cached('Data');
        $settings = $this->cached('Settings');
        $online = $this->ReadPropertyBoolean('Active') && (bool) $this->stateValue('Online', false);
        try { $choices = $this->tileChannelSelection(); } catch (Throwable $e) { $choices = []; }
        $channels = [];
        foreach ($data['channel'] ?? [] as $ch) {
            if (!($choices[$ch['number']] ?? true)) { continue; }
            $wireless = (int) ($ch['typ'] ?? -1) === 16;
            foreach ($settings['sensors'] ?? [] as $sensor) {
                if ((int) $sensor['type'] === (int) ($ch['typ'] ?? -1) && stripos($sensor['name'], 'Bluetooth') !== false) { $wireless = true; }
            }
            $valid = $online && is_numeric($ch['temp']) && is_finite((float) $ch['temp']) && (float) $ch['temp'] !== 999.0 && (!$wireless || !empty($ch['connected']));
            $channels[] = ['number' => $ch['number'], 'name' => (string) $ch['name'], 'temperature' => $valid ? (float) $ch['temp'] : null,
                'valid' => $valid, 'wireless' => $wireless, 'min' => $ch['min'] ?? null, 'max' => $ch['max'] ?? null,
                'alarm' => $ch['alarm'] ?? 0, 'outOfRange' => $valid && isset($ch['min'], $ch['max']) && ($ch['temp'] < $ch['min'] || $ch['temp'] > $ch['max'])];
        }
        $bt = $this->cached('BluetoothData');
        return ['version' => 1, 'enabled' => $this->ReadPropertyBoolean('TileEnabled'), 'online' => $online,
            'active' => $this->ReadPropertyBoolean('Active'), 'status' => $this->GetStatus(), 'lastUpdate' => $this->stateValue('LastUpdate', 0),
            'error' => $this->GetLastError(), 'bluetoothError' => $this->stateValue('BluetoothError', ''),
            'unit' => ($data['system']['unit'] ?? 'C') === 'F' ? '°F' : '°C', 'channels' => $channels,
            'pitmasters' => $this->ReadPropertyBoolean('TilePitmaster') ? array_values(array_map(static fn(array $pm): array =>
                array_intersect_key($pm, array_flip(['id', 'typ', 'set', 'value', 'channel'])), $data['pitmaster']['pm'] ?? [])) : [],
            'bluetooth' => isset($bt['enabled']) ? (bool) $bt['enabled'] : null,
            'device' => (string) ($settings['system']['host'] ?? 'Mini V3'),
            'battery' => $this->ReadPropertyBoolean('TileDetails') ? ($data['system']['soc'] ?? null) : null,
            'rssi' => $this->ReadPropertyBoolean('TileDetails') ? ($data['system']['rssi'] ?? null) : null,
            'canRefresh' => $this->ReadPropertyBoolean('Active'),
            'canWrite' => $online && $this->ReadPropertyBoolean('TileControls') && $this->ReadPropertyBoolean('AllowWrite'),
            'config' => ['theme' => in_array($this->ReadPropertyString('TileTheme'), ['auto', 'light', 'dark'], true) ? $this->ReadPropertyString('TileTheme') : 'auto',
                'accent' => sprintf('#%06x', max(0, min(0xffffff, $this->ReadPropertyInteger('TileAccent')))),
                'compact' => $this->ReadPropertyBoolean('TileCompact'), 'details' => $this->ReadPropertyBoolean('TileDetails'),
                'icons' => $this->ReadPropertyBoolean('TileIcons'), 'animations' => $this->ReadPropertyBoolean('TileAnimations'),
                'actionTimeout' => max(35000, $this->ReadPropertyInteger('Timeout') * 7000 + 2000),
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
        if ($response !== null || $encoded !== $this->GetBuffer('VisualizationLast')) {
            // Native Symcon-Werte unterstützen keine PHP-Arrays: JSON-Text übertragen.
            if ($this->UpdateVisualizationValue($encoded)) {
                $this->SetBuffer('VisualizationLast', $encoded);
            }
        }
    }

    private function visualizationAction(mixed $value): void
    {
        $requestID = '';
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
                'channel' => ['requestId', 'action', 'number', 'min', 'max'],
                'pitmaster' => ['requestId', 'action', 'id', 'mode', 'target', 'output'],
                'bluetooth' => ['requestId', 'action', 'enabled'],
                default => throw new InvalidArgumentException('Unbekannte Kachelaktion.')
            };
            if (array_diff(array_keys($request), $allowed)) { throw new InvalidArgumentException('Unbekannte Aktionsparameter.'); }
            if ($action !== 'refresh' && (!$this->ReadPropertyBoolean('TileControls') || !$this->ReadPropertyBoolean('AllowWrite') || !$this->stateValue('Online', false))) {
                throw new RuntimeException('Bedienung ist gesperrt oder das Gerät ist nicht erreichbar.');
            }
            if ($action === 'refresh') { $ok = $this->Update(); }
            elseif ($action === 'channel') {
                $number = WLTApi::integer($request['number'] ?? null, 1, 255);
                if (!($this->tileChannelSelection()[$number] ?? true)) { throw new InvalidArgumentException('Dieser Kanal ist in der Kachel ausgeblendet.'); }
                $ok = $this->SetChannel($number, WLTApi::encode(['min' => $request['min'] ?? null, 'max' => $request['max'] ?? null]));
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
            $message = $ok ? ($action === 'refresh' ? 'Messwerte aktualisiert.' : 'Änderung am Gerät bestätigt.') : ($this->GetLastError() ?: 'Instanz ist beschäftigt. Bitte erneut versuchen.');
        } catch (Throwable $e) { $ok = false; $message = $e->getMessage(); }
        // Auch Fehler quittieren; keine Erfolgsmeldung vor dem tatsächlichen Rücklesen.
        $this->pushVisualization(['requestId' => $requestID, 'ok' => $ok, 'message' => $message]);
    }
}
