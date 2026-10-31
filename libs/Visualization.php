<?php

declare(strict_types=1);

trait WLTVisualization
{
    private function registerVisualization(): void
    {
        $this->RegisterPropertyBoolean('TileEnabled', true);
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

    /** Nur benötigte, freigegebene Daten an die Kachel übertragen. */
    private function visualizationData(): array
    {
        $data = $this->cached('Data');
        $settings = $this->cached('Settings');
        $online = $this->ReadPropertyBoolean('Active') && (bool) $this->stateValue('Online', false);
        try { $choices = $this->tileChannelSelection(); } catch (Throwable $e) { $choices = []; }
        $channels = [];
        try { $meater = $this->meaterMetadata($data, $settings); }
        catch (Throwable $e) { $meater = []; $online = false; }
        try { $names = $this->tileChannelNames(); $presets = $this->corePresets($data['system']['unit'] ?? 'C'); $images = $this->brandImages(); }
        catch (Throwable $e) { $names = []; $presets = []; $images = ['','']; $online = false; }
        foreach ($data['channel'] ?? [] as $ch) {
            if (!($choices[$ch['number']] ?? true)) { continue; }
            $wireless = $this->channelIsWireless($ch, $settings);
            $valid = $online && $this->channelHasValidTemperature($ch, $settings);
            if (!$valid) { continue; }
            $channels[] = ['number' => $ch['number'], 'name' => (string) $ch['name'], 'displayName' => $names[$ch['number']] ?? '', 'temperature' => $valid ? (float) $ch['temp'] : null,
                'targetAlert' => $this->ReadPropertyBoolean('TargetWarningEnabled') ? $this->targetAlertLevel($ch, $data, $settings) : '', 'valid' => $valid, 'wireless' => $wireless, 'max' => $ch['max'] ?? null, 'selectedPreset' => $this->rememberedPreset($ch, $data['system']['unit'] ?? 'C'),
                'forecast' => $this->channelForecast($ch, ($data['system']['unit'] ?? 'C')),
                'alarm' => $ch['alarm'] ?? 0, 'outOfRange' => $valid && isset($ch['max']) && $ch['temp'] > $ch['max']] + ($meater[$ch['number']] ?? []);
        }
        $bt = $this->cached('BluetoothData');
        return ['version' => 1, 'enabled' => $this->ReadPropertyBoolean('TileEnabled'), 'online' => $online,
            'active' => $this->ReadPropertyBoolean('Active'), 'status' => $this->GetStatus(), 'lastUpdate' => $this->stateValue('LastUpdate', 0),
            'notificationError' => $this->GetNotificationError(), 'error' => $this->GetLastError(), 'bluetoothError' => $this->stateValue('BluetoothError', ''),
            'unit' => ($data['system']['unit'] ?? 'C') === 'F' ? '°F' : '°C', 'channels' => $channels,
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
                'forecastReset' => ['requestId', 'action', 'number'],
                'channel' => ['requestId', 'action', 'number', 'max', 'preset'],
                'pitmaster' => ['requestId', 'action', 'id', 'mode', 'target', 'output'],
                'bluetooth' => ['requestId', 'action', 'enabled'],
                default => throw new InvalidArgumentException('Unbekannte Kachelaktion.')
            };
            if (array_diff(array_keys($request), $allowed)) { throw new InvalidArgumentException('Unbekannte Aktionsparameter.'); }
            if (!in_array($action, ['refresh', 'forecastReset'], true) && (!$this->ReadPropertyBoolean('TileControls') || !$this->ReadPropertyBoolean('AllowWrite') || !$this->stateValue('Online', false))) {
                throw new RuntimeException('Bedienung ist gesperrt oder das Gerät ist nicht erreichbar.');
            }
            if ($action === 'refresh') { $ok = $this->Update(); }
            elseif ($action === 'forecastReset') {
                if (!$this->ReadPropertyBoolean('TileControls')) { throw new RuntimeException('Kachelbedienung ist gesperrt.'); }
                $number = WLTApi::integer($request['number'] ?? null, 1, 255);
                if (!($this->tileChannelSelection()[$number] ?? true)) { throw new InvalidArgumentException('Dieser Kanal ist ausgeblendet.'); }
                $this->resetChannelForecast($number);
                $ok = true;
            }
            elseif ($action === 'channel') {
                $number = WLTApi::integer($request['number'] ?? null, 1, 255);
                if (!($this->tileChannelSelection()[$number] ?? true)) { throw new InvalidArgumentException('Dieser Kanal ist in der Kachel ausgeblendet.'); }
                $channel = WLTApi::item($this->cached('Data')['channel'] ?? [], 'number', $number);
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
        } catch (Throwable $e) { $ok = false; $message = $e->getMessage(); }
        // Auch Fehler quittieren; keine Erfolgsmeldung vor dem tatsächlichen Rücklesen.
        $this->pushVisualization(['requestId' => $requestID, 'ok' => $ok, 'message' => $message]);
    }
}
