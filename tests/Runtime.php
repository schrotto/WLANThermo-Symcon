<?php

declare(strict_types=1);

$GLOBALS['profiles'] = [];
$GLOBALS['profileSettings'] = [];
$GLOBALS['instances'] = [];
$GLOBALS['externalVariables'] = [];
define('INSTANCE_VISUALIZATION_TYPE_NONE', 0);
define('INSTANCE_VISUALIZATION_TYPE_HTML', 1);
function IPS_SetProperty(int $id, string $name, mixed $value): void { throw new RuntimeException('Module must not change properties itself.'); }
function IPS_ApplyChanges(int $id): void { throw new RuntimeException('Module must not apply properties itself.'); }
function IPS_SemaphoreEnter(string $name, int $wait): bool { return true; }
function IPS_SemaphoreLeave(string $name): void {}
function IPS_VariableProfileExists(string $name): bool { return isset($GLOBALS['profiles'][$name]); }
function IPS_CreateVariableProfile(string $name, int $type): void { $GLOBALS['profiles'][$name] = $type; }
function IPS_SetVariableProfileDigits(string $name, int $digits): void { $GLOBALS['profileSettings'][$name]['digits'] = $digits; }
function IPS_SetVariableProfileValues(string $name, mixed $min, mixed $max, mixed $step): void { $GLOBALS['profileSettings'][$name]['range'] = [$min, $max, $step]; }
function IPS_SetVariableProfileText(string $name, string $prefix, string $suffix): void {}
function IPS_SetVariableProfileAssociation(string $name, mixed $value, string $label, string $icon, int $color): void {}

foreach (['VALUE_PRESENTATION', 'VALUE_INPUT', 'SLIDER', 'SWITCH', 'ENUMERATION', 'DATE_TIME'] as $kind) {
    define('VARIABLE_PRESENTATION_' . $kind, $kind);
}
function testObject(int $id): array {
    foreach ($GLOBALS['instances'] as $instance) {
        $ident = array_search($id, $instance->variableIDs, true);
        if ($ident !== false) { return [$instance, $ident]; }
    }
    throw new RuntimeException('Unknown object');
}
function IPS_SetName(int $id, string $name): bool { [$m, $ident] = testObject($id); $m->variables[$ident]['name'] = $name; return true; }
function IPS_SetPosition(int $id, int $pos): bool { [$m, $ident] = testObject($id); $m->variables[$ident]['pos'] = $pos; return true; }
function IPS_SetHidden(int $id, bool $hidden): bool { [$m, $ident] = testObject($id); $m->variables[$ident]['hidden'] = $hidden; return true; }
function IPS_GetObject(int $id): array {
    if (isset($GLOBALS['externalVariables'][$id])) { return ['ObjectIsHidden' => false, 'ObjectIsReadOnly' => $GLOBALS['externalVariables'][$id]['readonly'] ?? false, 'ParentID' => 0]; }
    [$m, $ident] = testObject($id); return ['ObjectIsHidden' => $m->variables[$ident]['hidden'] ?? false, 'ObjectIsReadOnly' => true, 'ParentID' => $m->InstanceID];
}
function IPS_VariableExists(int $id): bool { if (isset($GLOBALS['externalVariables'][$id])) return true; try { testObject($id); return true; } catch (Throwable $e) { return false; } }
function IPS_GetVariable(int $id): array { if (isset($GLOBALS['externalVariables'][$id])) return ['VariableType' => $GLOBALS['externalVariables'][$id]['type']]; [$m, $ident] = testObject($id); return ['VariableType' => $m->variables[$ident]['type']]; }
function SetValue(int $id, mixed $value): bool { $GLOBALS['externalVariables'][$id]['value'] = $value; return true; }

/** Small contract stub, not a replacement for a real Symcon runtime test. */
class IPSModuleStrict
{
    public int $InstanceID = 12345;
    public array $properties = [];
    public array $variables = [];
    public array $variableIDs = [];
    public int $nextVariableID = 100;
    public array $values = [];
    public array $actions = [];
    public array $buffers = [];
    public array $attributes = [];
    public array $timers = [];
    public int $status = 0;
    public int $visualizationType = 0;
    public array $visualizationMessages = [];
    public array $visualizationPayloads = [];
    public array $references = [];
    public array $formUpdates = [];
    public function Create(): void {}
    public function ApplyChanges(): void {}
    public function RequestAction(string $Ident, mixed $Value): void {}
    public function GetConfigurationForm(): string { return '{}'; }
    protected function RegisterPropertyBoolean(string $key, bool $v): void { $this->properties[$key] ??= $v; }
    protected function RegisterPropertyString(string $key, string $v): void { $this->properties[$key] ??= $v; }
    protected function RegisterPropertyInteger(string $key, int $v): void { $this->properties[$key] ??= $v; }
    protected function ReadPropertyBoolean(string $key): bool { return $this->properties[$key]; }
    protected function ReadPropertyString(string $key): string { return $this->properties[$key]; }
    protected function ReadPropertyInteger(string $key): int { return $this->properties[$key]; }
    protected function RegisterAttributeString(string $key, string $v): void { $this->attributes[$key] ??= $v; }
    protected function ReadAttributeString(string $key): string { return $this->attributes[$key]; }
    protected function WriteAttributeString(string $key, string $v): void { $this->attributes[$key] = $v; }
    protected function RegisterTimer(string $key, int $v, string $script): void { $this->timers[$key] = $v; }
    protected function SetTimerInterval(string $key, int $v): void { $this->timers[$key] = $v; }
    protected function RegisterVariableBoolean(string $id, string $name, string $profile, int $pos): bool { return $this->MaintainVariable($id, $name, 0, $profile, $pos, true); }
    protected function RegisterVariableInteger(string $id, string $name, string $profile, int $pos): bool { return $this->MaintainVariable($id, $name, 1, $profile, $pos, true); }
    protected function RegisterVariableString(string $id, string $name, string $profile, int $pos): bool { return $this->MaintainVariable($id, $name, 3, $profile, $pos, true); }
    protected function MaintainVariable(string $id, string $name, int $type, array|string $profile, int $pos, bool $keep): bool {
        if (!$keep) { $this->UnregisterVariable($id); return false; }
        if (isset($this->variables[$id]) && $this->variables[$id]['type'] !== $type) { throw new RuntimeException('Variable type changed: ' . $id); }
        $new = !isset($this->variables[$id]);
        if ($new) { $this->variableIDs[$id] = $this->nextVariableID++; }
        $this->variables[$id] = $new ? compact('name', 'type', 'profile', 'pos') : array_replace($this->variables[$id], ['profile' => $profile]);
        $this->values[$id] ??= [false, 0, 0.0, ''][$type];
        return $new;
    }
    protected function UnregisterVariable(string $id): void { unset($this->variables[$id], $this->values[$id], $this->actions[$id], $this->variableIDs[$id]); }
    protected function SetValue(string $id, mixed $value): void {
        if (!isset($this->variables[$id])) { throw new RuntimeException('Unregistered variable: ' . $id); }
        $expected = [0 => 'boolean', 1 => 'integer', 2 => 'double', 3 => 'string'][$this->variables[$id]['type']];
        if (gettype($value) !== $expected) { throw new RuntimeException('Wrong value type: ' . $id); }
        $this->values[$id] = $value;
    }
    protected function GetValue(string $id): mixed { return $this->values[$id]; }
    protected function GetIDForIdent(string $id): int { return $this->variableIDs[$id] ?? 0; }
    protected function EnableAction(string $id): void { $this->actions[$id] = true; }
    protected function DisableAction(string $id): void { $this->actions[$id] = false; }
    protected function SetBuffer(string $id, string $value): void { $this->buffers[$id] = $value; }
    protected function GetBuffer(string $id): string { return $this->buffers[$id] ?? ''; }
    protected function GetStatus(): int { return $this->status; }
    protected function SetVisualizationType(int $type): bool { $this->visualizationType = $type; return true; }
    protected function UpdateVisualizationValue(mixed $value): bool {
        // Die native Symcon-Wertgrenze akzeptiert keine PHP-Arrays/Objekte.
        if (!is_scalar($value)) { throw new TypeError('Cannot auto-convert value for parameter Value (Type is not supported)'); }
        $this->visualizationPayloads[] = $value;
        $this->visualizationMessages[] = is_string($value) ? json_decode($value, true, 64, JSON_THROW_ON_ERROR) : $value;
        return true;
    }
    protected function GetReferenceList(): array { return $this->references; }
    protected function RegisterReference(int $id): void { $this->references[] = $id; }
    protected function UnregisterReference(int $id): void { $this->references = array_values(array_diff($this->references, [$id])); }
    protected function UpdateFormField(string $name, string $field, string $value): void { $this->formUpdates[$name][$field] = $value; }
    protected function SetStatus(int $status): void { $this->status = $status; }
    protected function SetSummary(string $text): void {}
    protected function SendDebug(string $message, mixed $data, int $format): void {}
    protected function ReloadForm(): void {}
}
