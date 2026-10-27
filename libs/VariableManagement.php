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
