<?php

declare(strict_types=1);
require __DIR__ . '/Runtime.php';
require __DIR__ . '/../WLANThermo/module.php';

class TestDevice extends WLANThermo
{
    public array $deviceData;
    public array $deviceSettings;
    public array $bt;
    public array $writes = [];
    public bool $offline = false;
    public bool $reject = false;
    public bool $ignore = false;
    public bool $btUnavailable = false;
    public function __construct()
    {
        $this->deviceData = json_decode(file_get_contents(__DIR__ . '/fixtures/data.json'), true);
        $this->deviceSettings = json_decode(file_get_contents(__DIR__ . '/fixtures/settings.json'), true);
        $this->bt = json_decode(file_get_contents(__DIR__ . '/fixtures/bluetooth.json'), true);
        $GLOBALS['instances'][$this->InstanceID] = $this;
        $this->Create();
        $this->properties['Host'] = '192.0.2.20';
        $this->properties['ExposeRaw'] = true;
        $this->ApplyChanges();
    }
    protected function transport(string $path, ?array $payload = null): string
    {
        if ($this->offline) { throw new RuntimeException('HTTP-Verbindung fehlgeschlagen.'); }
        if ($path === '/getbluetooth' && $this->btUnavailable) { return '<html>404</html>'; }
        if ($payload === null) {
            return WLTApi::encode(match ($path) { '/data' => $this->deviceData, '/settings' => $this->deviceSettings, '/getbluetooth' => $this->bt, default => throw new RuntimeException($path) });
        }
        $this->writes[] = [$path, $payload];
        if ($this->reject) { return 'false'; }
        if (!$this->ignore) {
            switch ($path) {
                case '/setchannels':
                    foreach ($this->deviceData['channel'] as &$ch) {
                        if ($ch['number'] === $payload['number']) { $ch = array_replace($ch, $payload); }
                    }
                    unset($ch); break;
                case '/setpitmaster': $this->deviceData['pitmaster']['pm'] = $payload; break;
                case '/setsystem':
                    $this->deviceSettings['system'] = array_replace($this->deviceSettings['system'], $payload);
                    $this->deviceData['system']['unit'] = $this->deviceSettings['system']['unit']; break;
                case '/setIoT': $this->deviceSettings['iot'] = array_replace($this->deviceSettings['iot'], $payload); break;
                case '/setpid':
                    foreach ($payload as $p) {
                        foreach ($this->deviceSettings['pid'] as &$profile) {
                            if ($profile['id'] === $p['id']) { $profile = array_replace($profile, $p); }
                        }
                        unset($profile);
                    }
                    break;
                case '/setbluetooth':
                    $this->bt['enabled'] = $payload['enabled'];
                    foreach ($payload['devices'] as $device) {
                        foreach ($this->bt['devices'] as &$d) {
                            if ($d['address'] === $device['address']) { $d['selected'] = $device['selected']; }
                        }
                        unset($d);
                    }
                    break;
                default: throw new RuntimeException($path);
            }
        }
        return $path === '/setbluetooth' ? 'false' : 'true'; // real firmware BLE return bug
    }
}

$count = 0;
function check(bool $condition, string $message): void {
    global $count;
    $count++;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    echo 'OK ' . $count . ': ' . $message . PHP_EOL;
}
function rejects(callable $work): bool { try { $work(); return false; } catch (Throwable $e) { return true; } }

$m = new TestDevice();
check($m->status === 102 && $m->values['Online'], 'Polling creates active instance');
check($m->values['CH13_Temperature'] === 60.3 && $m->values['CH14_Temperature'] === 118.4, 'MEATER core and ambient channels');
check($m->values['CH1_Valid'], 'Wired connected=false is not a disconnected Bluetooth probe');
check(!str_contains($m->GetSnapshot(), 'super-secret') && !str_contains($m->GetSnapshot(), 'cloud-secret'), 'Credentials redacted from snapshot and buffers');
check($m->SetChannel(1, '{"min":20.0,"max":150.0,"name":"BBQ"}'), 'Change channel and verify readback');
check($m->values['CH1_Min'] === 20.0, 'Confirmed limits published');
$n = count($m->writes);
check(!$m->SetChannel(1, '{"min":200.0,"max":100.0}') && count($m->writes) === $n, 'Reject inverted limits before POST');
check(!$m->SetChannel(13, '{"typ":0}'), 'Protect fixed Bluetooth probe type');
check(!$m->SetChannel(1, '{"temp":50.0}'), 'Reject changes to measured temperature');
check(!$m->SetChannel(1, '{"alarm":4}'), 'Reject unsupported alarm mode');
check(!$m->SetChannel(1, '{"max":"90"}'), 'Reject string instead of number');
check(!$m->SetChannel(1, '{"name":"Zu langer Name"}'), 'Enforce channel name length');
check($m->SetPitmaster(1, '{"typ":"auto","set":130.0}'), 'Change second pitmaster');
[$path,$payload] = end($m->writes);
check($path === '/setpitmaster' && count($payload) === 2 && $payload[0]['typ'] === 'off' && $payload[1]['set'] === 130.0, 'Full ordered pitmaster payload preserves PM 0');
check(!$m->SetPitmaster(1, '{"value":50}'), 'Reject manual output in auto mode');
check($m->SetPitmaster(1, '{"typ":"manual","value":50}'), 'Manual mode and output together');
check($m->SetBluetoothChannel('D0:D9:4F:88:DE:CE', 1, false) && $m->bt['devices'][0]['selected'] === 1, 'MEATER selection bitmask, case-insensitive MAC');
check($m->bt['devices'][2]['selected'] === 0, 'Multiple MEATER devices remain independent');
check($m->SetBluetoothSelection('b8:1f:5e:30:29:0b', 63), 'All six MEATER Pro channels selected dynamically');
check(!$m->SetBluetoothSelection('b8:1f:5e:30:29:0b', 64), 'Reject mask beyond reported count');
check($m->SetBluetoothEnabled(false) && !$m->values['BluetoothEnabled'], 'Bluetooth boolean uses readback despite firmware false response');
$m->ignore = true;
check(!$m->SetChannel(1, '{"max":160.0}'), 'Reject acknowledged but unapplied changes');
check(!$m->SetBluetoothEnabled(true), 'Reject unchanged BLE state even with unreliable POST reply');
$m->ignore = false;
$m->reject = true;
check(!$m->SetChannel(1, '{"max":160.0}'), 'Detect false response');
$m->reject = false;
check($m->SetSystem('{"unit":"F"}') && $m->variables['CH13_Temperature']['profile'] === 'WLT.TemperatureF', 'Fahrenheit profile follows device unit');
check($m->SetPID(0, '{"Kp":4.5,"opl":true}'), 'PID parameter write');
check(!$m->SetPID(0, '{"DCmmin":90.0,"DCmmax":30.0}'), 'Reject inverted PID bounds');
$m->deviceData['channel'][1]['connected'] = false;
$m->deviceData['channel'][0]['temp'] = 999.0;
check($m->Update() && !$m->values['CH13_Valid'] && !$m->values['CH1_Valid'], 'Disconnected BLE and 999 sentinel become invalid');
array_pop($m->deviceData['channel']);
check($m->Update() && !$m->values['CH14_Present'] && !$m->actions['CH14_Min'], 'Removed channel retained but marked absent and disabled');
$m->offline = true;
check(!$m->Update() && !$m->values['Online'] && !$m->values['CH13_Valid'], 'Offline invalidates measurements');
$m->offline = false;
check($m->Update() && $m->values['Online'], 'Automatic recovery');
$m->btUnavailable = true;
check($m->Update() && $m->values['Online'] && $m->values['BluetoothError'] !== '', 'Optional BLE failure keeps primary data available');
check(!$m->actions['BluetoothEnabled'], 'Disable Bluetooth action after optional failure');
$m->btUnavailable = false;
$m->properties['AllowWrite'] = false;
$n = count($m->writes);
check(!$m->SetChannel(1, '{"max":180.0}') && count($m->writes) === $n, 'Read-only mode prevents POST');
$m->properties['Active'] = false;
$m->ApplyChanges();
check($m->status === 104 && $m->timers['Poll'] === 0 && !$m->values['Online'], 'Deactivate stops timer');
$m->properties['Active'] = true;
$m->properties['Host'] = 'http://user:pass@host/path';
$m->ApplyChanges();
check($m->status === 201, 'Invalid host sets configuration error');
check(rejects(fn() => WLTApi::object('[]')), 'Reject non-object JSON');
check(rejects(fn() => WLTApi::object('<html>Error</html>')), 'Reject HTML as JSON');
check(rejects(fn() => WLTApi::baseURL('http://example.org/?x=1')), 'Reject query in base URL');
check(WLTApi::baseURL('192.0.2.2:8080') === 'http://192.0.2.2:8080', 'Host and port normalization');
$m = new TestDevice();
$form = json_decode($m->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
check(count(array_filter($form['actions'], fn($a) => str_starts_with($a['caption'] ?? '', 'Bluetooth:'))) === 3, 'Form separates devices including identical MEATER names');
check(count(end($form['actions'])['items']) === 3, 'Two MEATER sensor checkboxes');
check($m->SetIoT('{"PMQhost":"192.0.2.5","PMQpass":"new-secret","PMQon":true}'), 'MQTT settings with verified secret');
check(!str_contains($m->GetSnapshot(), 'new-secret'), 'New MQTT password stays redacted');
check(!$m->SetIoT('{"PMQport":0}'), 'Reject invalid MQTT port');
file_put_contents(__DIR__ . '/dynamic-form.generated.json', $m->GetConfigurationForm());
$GLOBALS['eventDevice'] = $m;
function WLT_SetBluetoothChannel(int $id, string $address, int $index, bool $value): bool {
    return $GLOBALS['eventDevice']->SetBluetoothChannel($address, $index, $value);
}
$checkbox = $form['actions'][count($form['actions']) - 1]['items'][1];
${$checkbox['name']} = true;
$id = $m->InstanceID;
eval($checkbox['onChange']);
check($m->bt['devices'][2]['selected'] === 1, 'Form checkbox event uses named field value and correct MEATER MAC');
WLTApi::verify(['opl' => 1], ['opl' => true]);
check(true, 'Firmware numeric booleans accepted in readback');
// Version 1.1: whole-degree controls, selectable variables and upgrade behavior.
$v = new TestDevice();
check($v->variables['CH1_Min']['profile'] === 'WLT.SetpointC' && $v->variables['PM0_Set']['profile'] === 'WLT.SetpointC', 'Whole-degree profiles on limits and pitmaster setpoint');
check($GLOBALS['profileSettings']['WLT.SetpointC']['digits'] === 0 && $GLOBALS['profileSettings']['WLT.SetpointC']['range'][2] === 1, 'Setpoint display has zero decimals and one-degree increments');
check($v->values['CH13_Temperature'] === 60.3 && $v->variables['CH13_Temperature']['profile'] === 'WLT.TemperatureC', 'Measured temperatures retain decimal precision');
$n = count($v->writes);
check(!$v->SetChannel(1, '{"min":20.5}') && count($v->writes) === $n, 'Fractional alarm limit rejected before writing');
check(!$v->SetPitmaster(0, '{"typ":"auto","set":120.5}') && count($v->writes) === $n, 'Fractional pitmaster target rejected before writing');
check($v->SetChannel(1, '{"min":20,"max":140}'), 'Integer alarm limits accepted');
$v->RequestAction('CH1_Min', 21);
check($v->values['CH1_Min'] === 21.0, 'Whole-degree UI input preserves existing Float variable type');
$keptID = $v->variableIDs['CH1_Temperature'];
$oldID = $v->variableIDs['CH1_Color'];
$v->SetVariableEnabled('CH1_Color', false);
check(!isset($v->variables['CH1_Color']) && $v->variableIDs['CH1_Temperature'] === $keptID, 'Deselect deletes only chosen variable; retained IDs unchanged');
check($v->Update() && !isset($v->variables['CH1_Color']) && !isset($v->actions['CH1_Color']), 'Polling does not recreate deselected variables or actions');
$v->SetVariableEnabled('CH1_Color', true);
check(isset($v->variables['CH1_Color']) && $v->variableIDs['CH1_Color'] !== $oldID, 'Re-enable recreates selected variable with new ID');
$v->properties['ShowSettings'] = false;
$v->properties['ShowSystem'] = false;
$v->properties['ShowBluetoothDetails'] = false;
$v->properties['ShowChannelDetails'] = false;
$v->properties['ShowPitmaster'] = false;
$v->properties['ShowDevice'] = false;
$v->ApplyChanges();
check($v->status === 102 && !array_filter(array_keys($v->variables), fn($id) => str_starts_with($id, 'SET_') || str_starts_with($id, 'SYS_') || str_starts_with($id, 'BLE_') || str_starts_with($id, 'PM')), 'Group checkboxes remove large optional API and pitmaster groups');
check(isset($v->variables['CH13_Temperature'], $v->variables['CH1_Min']) && !isset($v->variables['Device'], $v->variables['CH1_Color']), 'Main readings and controls remain when detail groups are off');
check($v->SetChannel(1, '{"max":150}') && $v->SetPitmaster(0, '{"typ":"auto","set":125}'), 'API functions work independently of variable groups');
$v->SetVariableEnabled('LastError', false);
$v->SetVariableEnabled('Online', false);
$v->SetVariableEnabled('LastUpdate', false);
check(!isset($v->variables['LastError'], $v->variables['Online'], $v->variables['LastUpdate']), 'Status variables can also be deselected');
check(!$v->SetChannel(1, '{"max":150.5}') && str_contains($v->GetLastError(), 'ganzen Grad'), 'Error remains available without LastError variable');
check($v->Update() && json_decode($v->GetSnapshot(), true)['online'], 'Snapshot and recovery work without status variables');
$v->offline = true;
$v->SetVariableEnabled('CH13_Temperature', false);
check(!isset($v->variables['CH13_Temperature']), 'Deselection removes variables even while device is offline');
$v->offline = false;
$v->SetVariableEnabled('CH13_Temperature', true);
check(isset($v->variables['CH13_Temperature']), 'Re-enabled offline selection returns on next successful read');
// Simulate a 1.0 installation: old profiles and tracking, no new catalog.
$v->variables['CH1_Min']['profile'] = 'WLT.TemperatureC';
$keptID = $v->variableIDs['CH1_Min'];
$v->attributes['VariableCatalog'] = '{}';
$v->ApplyChanges();
check($v->variableIDs['CH1_Min'] === $keptID && $v->variables['CH1_Min']['profile'] === 'WLT.SetpointC', 'Upgrade updates profile without replacing variable ID');
$v->properties['ShowSettings'] = true;
$v->ApplyChanges();
$v->attributes['VariableCatalog'] = '{}';
$v->properties['ShowSettings'] = false;
$v->offline = true;
$v->ApplyChanges();
check(!array_filter(array_keys($v->variables), fn($id) => str_starts_with($id, 'SET_')), 'Legacy tracking removes deselected groups offline without catalog');
$v->offline = false;
$v->properties['ShowSettings'] = true;
$v->ApplyChanges();
$catalog = json_decode($v->attributes['VariableCatalog'], true);
$vf = json_decode($v->GetConfigurationForm(), true);
function collectVariableChecks(array $tree): array {
    $out = [];
    foreach ($tree as $node) {
        if (($node['type'] ?? '') === 'CheckBox' && str_starts_with($node['name'] ?? '', 'VAR_')) { $out[] = $node; }
        if (isset($node['items'])) { $out = array_merge($out, collectVariableChecks($node['items'])); }
    }
    return $out;
}
$checks = collectVariableChecks($vf['actions']);
check(count($checks) === count($catalog), 'Every known variable gets its own checkbox');
function WLT_SetVariableEnabled(int $id, string $ident, bool $enabled): void { $GLOBALS['instances'][$id]->SetVariableEnabled($ident, $enabled); }
$checkBox = current(array_filter($checks, fn($c) => str_contains($c['caption'], '[CH1_Max]')));
$id = $v->InstanceID;
${$checkBox['name']} = false;
eval($checkBox['onChange']);
check(!isset($v->variables['CH1_Max']), 'Selection checkbox event saves config and removes selected variable');
check($v->SetSystem('{"unit":"F"}') && $v->variables['CH1_Min']['profile'] === 'WLT.SetpointF', 'Fahrenheit targets also use whole degrees');
$v->properties['DisabledVariables'] = WLTApi::encode(array_keys(json_decode($v->attributes['VariableCatalog'], true)));
$v->ApplyChanges();
check($v->status === 102 && count($v->variables) === 0 && json_decode($v->GetSnapshot(), true)['online'], 'All variables may be deselected while module continues collecting data');
$m->deviceData['channel'][] = $m->deviceData['channel'][0];
check(!$m->Update(), 'Reject duplicate channel numbers');
echo $count . " assertions passed.\n";
